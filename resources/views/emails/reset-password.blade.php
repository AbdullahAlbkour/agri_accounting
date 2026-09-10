<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Cairo','Segoe UI',Tahoma,Arial,sans-serif; color:#1e293b;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 12px;">
    <tr>
        <td align="center">

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="rtl"
                   style="max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 6px 24px rgba(15,23,42,0.08);">

                <!-- ترويسة النظام مع الشعار -->
                <tr>
                    <td style="background:linear-gradient(135deg,#1b5e20 0%,#2e7d32 100%); padding:28px 24px; text-align:center;">
                        <table role="presentation" cellpadding="0" cellspacing="0" align="center">
                            <tr>
                                <td style="width:60px; height:60px; background-color:#ffffff; border-radius:50%; text-align:center; vertical-align:middle; font-size:30px; line-height:60px;">
                                    🌿
                                </td>
                            </tr>
                        </table>
                        <h1 style="margin:14px 0 4px; font-size:20px; color:#ffffff; font-weight:bold;">{{ $appName }}</h1>
                        <p style="margin:0; font-size:13px; color:#c8e6c9;">نظام المحاسبة الزراعية</p>
                    </td>
                </tr>

                <!-- المحتوى -->
                <tr>
                    <td style="padding:28px 26px 8px;">
                        <h2 style="margin:0 0 12px; font-size:18px; color:#1b5e20;">إعادة تعيين كلمة المرور</h2>

                        <p style="margin:0 0 12px; font-size:15px; line-height:1.9;">
                            مرحباً <strong>{{ $user->name }}</strong>،
                        </p>

                        <p style="margin:0 0 18px; font-size:15px; line-height:1.9; color:#475569;">
                            وصلنا طلب لإعادة تعيين كلمة مرور حسابك
                            (<span style="direction:ltr; unicode-bidi:embed; font-family:monospace;">{{ '@'.($user->username ?? '') }}</span>)
                            في نظام المحاسبة الزراعية. اضغط الزر التالي لاختيار كلمة مرور جديدة:
                        </p>

                        <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:6px auto 20px;">
                            <tr>
                                <td style="background-color:#2e7d32; border-radius:999px;">
                                    <a href="{{ $resetUrl }}"
                                       style="display:inline-block; padding:13px 34px; font-size:15px; font-weight:bold; color:#ffffff; text-decoration:none;">
                                        إعادة تعيين كلمة المرور
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                               style="background-color:#fff8e1; border:1px solid #ffe082; border-radius:10px; margin-bottom:18px;">
                            <tr>
                                <td style="padding:12px 14px; font-size:13.5px; line-height:1.8; color:#7c5800;">
                                    ⏱ صلاحية هذا الرابط <strong>{{ $expireMinutes }} دقيقة</strong> من وقت إرسال الرسالة.
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 10px; font-size:13.5px; line-height:1.9; color:#64748b;">
                            إذا لم تطلب إعادة التعيين فتجاهل هذه الرسالة، ولن يطرأ أي تغيير على حسابك.
                        </p>

                        <p style="margin:0 0 6px; font-size:13px; color:#94a3b8;">
                            إن لم يعمل الزر، انسخ الرابط التالي والصقه في المتصفح:
                        </p>
                        <p style="margin:0 0 22px; font-size:12px; direction:ltr; unicode-bidi:embed; word-break:break-all; color:#2e7d32;">
                            <a href="{{ $resetUrl }}" style="color:#2e7d32; text-decoration:underline;">{{ $resetUrl }}</a>
                        </p>
                    </td>
                </tr>

                <!-- تذييل -->
                <tr>
                    <td style="background-color:#f8fafc; border-top:1px solid #e2e8f0; padding:18px 24px; text-align:center;">
                        <p style="margin:0 0 4px; font-size:13px; color:#475569;">تحياتنا، فريق {{ $appName }}</p>
                        <p style="margin:0; font-size:11.5px; color:#94a3b8;">
                            هذه رسالة آلية، الرجاء عدم الرد عليها.
                        </p>
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>

</body>
</html>
