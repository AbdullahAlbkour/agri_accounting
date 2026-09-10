<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * عرض صفحة إنشاء حساب مزارع جديد.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * إنشاء حساب مزارع جديد وتسجيل دخوله مباشرة.
     */
    public function register(Request $request)
    {
        $validated = $request->validate(self::rules(), self::messages());

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_FARMER,
            'is_active' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'تم إنشاء حسابك بنجاح، أهلاً بك '.$user->name.'.');
    }

    /**
     * قواعد التحقق المشتركة بين التسجيل الذاتي وإنشاء الحسابات من لوحة المدير.
     *
     * @return array<string, mixed>
     */
    public static function rules(?int $ignoreUserId = null): array
    {
        $unique = $ignoreUserId ? ','.$ignoreUserId : '';

        return [
            'name' => 'required|string|max:255',
            'username' => 'required|string|min:3|max:50|regex:/^[A-Za-z0-9_.\-]+$/|unique:users,username'.$unique,
            'email' => 'required|email:rfc|max:255|unique:users,email'.$unique,
            'phone' => 'nullable|string|max:30',
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'الاسم الكامل مطلوب.',
            'username.required' => 'اسم المستخدم مطلوب.',
            'username.unique' => 'اسم المستخدم محجوز، اختر اسماً آخر.',
            'username.regex' => 'اسم المستخدم يجب أن يتكون من أحرف إنجليزية وأرقام و ( _ . - ) فقط.',
            'username.min' => 'اسم المستخدم يجب ألا يقل عن 3 أحرف.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'هذا البريد الإلكتروني مسجّل مسبقاً.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ];
    }
}
