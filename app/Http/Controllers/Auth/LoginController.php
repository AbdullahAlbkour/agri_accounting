<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * عرض شاشة تسجيل الدخول.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * محاولة تسجيل الدخول باسم المستخدم أو البريد الإلكتروني وكلمة المرور.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'الرجاء إدخال اسم المستخدم أو البريد الإلكتروني.',
            'password.required' => 'الرجاء إدخال كلمة المرور.',
        ]);

        // يسمح بتسجيل الدخول عبر البريد الإلكتروني أو اسم المستخدم أو الاسم الكامل
        $identifier = $credentials['login'];
        $fields = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? ['email'] : ['username', 'name'];

        $authenticated = false;

        foreach ($fields as $field) {
            if (Auth::attempt([$field => $identifier, 'password' => $credentials['password']], $request->boolean('remember'))) {
                $authenticated = true;
                break;
            }
        }

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'login' => 'بيانات الدخول غير صحيحة. تأكد من اسم المستخدم وكلمة المرور.',
            ]);
        }

        // الحسابات الموقوفة لا يُسمح لها بالدخول
        if (! Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => 'هذا الحساب موقوف حالياً. يرجى مراجعة مدير النظام.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'مرحباً بك '.Auth::user()->name.'، تم تسجيل الدخول بنجاح.');
    }

    /**
     * تسجيل الخروج وإنهاء الجلسة.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'تم تسجيل الخروج بنجاح.');
    }
}
