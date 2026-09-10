<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /**
     * عرض صفحة "نسيت كلمة المرور".
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * إرسال رابط إعادة تعيين كلمة المرور إلى بريد المستخدم.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'الرجاء إدخال البريد الإلكتروني المسجّل.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني. تفقّد صندوق الوارد (وملف الرسائل غير المرغوبة).');
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->with('error', 'تم إرسال رابط مؤخراً. انتظر قليلاً قبل إعادة المحاولة.');
        }

        return back()->withInput()->with('error', 'لا يوجد حساب مسجّل بهذا البريد الإلكتروني.');
    }
}
