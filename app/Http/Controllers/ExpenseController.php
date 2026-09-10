<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsClosedSeasons;
use App\Http\Controllers\Concerns\NormalizesNumbers;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Season;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use GuardsClosedSeasons, NormalizesNumbers;

    public function index(Request $request)
    {
        $query = Expense::with(['season.crop', 'category']);

        if ($seasonId = $request->input('season_id')) {
            $query->where('season_id', $seasonId);
        }

        $expenses = $query->latest()->paginate(15)->withQueryString();
        $seasons = Season::with('crop')->orderBy('name')->get();

        return view('expenses.index', compact('expenses', 'seasons'));
    }

    public function create()
    {
        // المواسم المغلقة لا تظهر: لا يمكن تسجيل مصاريف عليها
        $seasons = Season::active()->with('crop')->get();
        $categories = ExpenseCategory::all();

        return view('expenses.create', compact('seasons', 'categories'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'amount' => $this->normalizeNumber($request->input('amount')),
            'exchange_rate' => $this->normalizeNumber($request->input('exchange_rate')) ?: 1,
        ]);

        $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'category_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,TRY,SYP',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($message = $this->closedSeasonMessageById($request->season_id)) {
            return back()->withInput()->with('error', $message);
        }

        // إيجاد التصنيف أو إنشاؤه تلقائياً
        $category = ExpenseCategory::firstOrCreate([
            'name' => trim($request->category_name),
        ]);

        Expense::create([
            'season_id' => $request->season_id,
            'expense_category_id' => $category->id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'exchange_rate' => $request->exchange_rate ?: 1,
            'date' => $request->date,
            'notes' => $request->notes,
        ]);

        return redirect()->route('expenses.index')->with('success', 'تم تسجيل المصروف بنجاح.');
    }

    public function edit(Expense $expense)
    {
        if ($message = $this->closedSeasonMessage($expense->season)) {
            return redirect()->route('expenses.index')->with('error', $message);
        }

        $seasons = Season::active()->with('crop')->get();
        $categories = ExpenseCategory::all();

        return view('expenses.edit', compact('expense', 'seasons', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        if ($message = $this->closedSeasonMessage($expense->season)) {
            return redirect()->route('expenses.index')->with('error', $message);
        }

        $request->merge([
            'amount' => $this->normalizeNumber($request->input('amount')),
            'exchange_rate' => $this->normalizeNumber($request->input('exchange_rate')) ?: 1,
        ]);

        $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'category_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,TRY,SYP',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // منع نقل المصروف إلى موسم مغلق
        if ($message = $this->closedSeasonMessageById($request->season_id)) {
            return back()->withInput()->with('error', $message);
        }

        $category = ExpenseCategory::firstOrCreate([
            'name' => trim($request->category_name),
        ]);

        $expense->update([
            'season_id' => $request->season_id,
            'expense_category_id' => $category->id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'exchange_rate' => $request->exchange_rate ?: 1,
            'date' => $request->date,
            'notes' => $request->notes,
        ]);

        return redirect()->route('expenses.index')->with('success', 'تم تحديث بيانات المصروف بنجاح.');
    }

    public function destroy(Expense $expense)
    {
        if ($message = $this->closedSeasonMessage($expense->season)) {
            return back()->with('error', $message);
        }

        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'تم حذف المصروف بنجاح.');
    }
}
