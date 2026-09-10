<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Controller;
use App\Models\BuyerPayment;
use App\Models\Crop;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Field;
use App\Models\Sale;
use App\Models\Season;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * لوحة تحكم مدير النظام: إدارة حسابات المزارعين.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (in_array($request->input('role'), [User::ROLE_ADMIN, User::ROLE_FARMER], true)) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->orderByDesc('id')->paginate(15)->withQueryString();

        // إحصائيات بيانات كل مستخدم (بتجاهل عزل البيانات لأن المستخدم مدير)
        $stats = [];

        foreach ($users as $user) {
            $stats[$user->id] = [
                'seasons' => Season::ownedBy($user->id)->count(),
                'crops' => Crop::ownedBy($user->id)->count(),
                'fields' => Field::ownedBy($user->id)->count(),
                'expenses' => Expense::ownedBy($user->id)->count(),
                'sales' => Sale::ownedBy($user->id)->count(),
                'payments' => BuyerPayment::ownedBy($user->id)->count(),
            ];
        }

        $totals = [
            'users' => User::count(),
            'farmers' => User::farmers()->count(),
            'admins' => User::where('role', User::ROLE_ADMIN)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        return view('admin.users.index', compact('users', 'stats', 'totals'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            RegisterController::rules() + ['role' => 'required|in:admin,farmer'],
            RegisterController::messages()
        );

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'تم إنشاء الحساب بنجاح.');
    }

    public function show(User $user)
    {
        $seasons = Season::ownedBy($user->id)->with(['crop', 'field', 'expenses', 'sales'])->latest()->get();

        $summary = [
            'sales_usd' => $seasons->sum(fn (Season $s) => $s->totalSalesUSD()),
            'expenses_usd' => $seasons->sum(fn (Season $s) => $s->totalExpensesUSD()),
            'crops' => Crop::ownedBy($user->id)->count(),
            'fields' => Field::ownedBy($user->id)->count(),
            'payments' => BuyerPayment::ownedBy($user->id)->count(),
        ];

        $summary['net_profit_usd'] = $summary['sales_usd'] - $summary['expenses_usd'];

        return view('admin.users.show', compact('user', 'seasons', 'summary'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|min:3|max:50|regex:/^[A-Za-z0-9_.\-]+$/|unique:users,username,'.$user->id,
            'email' => 'required|email:rfc|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:admin,farmer',
            'is_active' => 'nullable|boolean',
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ], RegisterController::messages());

        // منع المدير من سحب صلاحيته أو إيقاف حسابه بنفسه
        $isSelf = $request->user()->is($user);

        $data = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $isSelf ? $user->role : $validated['role'],
            'is_active' => $isSelf ? true : $request->boolean('is_active'),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم تحديث بيانات الحساب '.$user->name.'.'
                .($isSelf ? ' (لا يمكن تغيير صلاحيتك أو إيقاف حسابك بنفسك)' : ''));
    }

    /**
     * إيقاف/تفعيل الحساب دون حذف بياناته.
     */
    public function toggleActive(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'لا يمكنك إيقاف حسابك الخاص.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active
            ? 'تم تفعيل حساب '.$user->name.'.'
            : 'تم إيقاف حساب '.$user->name.'.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'لا يمكنك حذف حسابك الخاص.');
        }

        $name = $user->name;

        // حذف كل بيانات المزارع المرتبطة بحسابه
        BuyerPayment::ownedBy($user->id)->delete();
        Sale::ownedBy($user->id)->delete();
        Expense::ownedBy($user->id)->delete();
        ExpenseCategory::ownedBy($user->id)->delete();
        Season::ownedBy($user->id)->delete();
        Crop::ownedBy($user->id)->delete();
        Field::ownedBy($user->id)->delete();

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'تم حذف حساب '.$name.' وكل بياناته.');
    }
}
