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
     * محاولة تسجيل الدخول باسم المستخدم (البريد) وكلمة المرور.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'الرجاء إدخال اسم المستخدم أو البريد الإلكتروني.',
            'password.required' => 'الرجاء إدخال كلمة المرور.',
        ]);

        // يسمح بتسجيل الدخول عبر البريد الإلكتروني أو اسم المستخدم
        $field = filter_var($credentials['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        $attempt = Auth::attempt([
            $field => $credentials['email'],
            'password' => $credentials['password'],
        ], $request->boolean('remember'));

        if (! $attempt) {
            throw ValidationException::withMessages([
                'email' => 'بيانات الدخول غير صحيحة. تأكد من اسم المستخدم وكلمة المرور.',
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
