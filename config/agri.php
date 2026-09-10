<?php

return [

    /*
    |--------------------------------------------------------------------------
    | إعدادات البريد الإلكتروني للنظام
    |--------------------------------------------------------------------------
    |
    | queue_notifications: إرسال رسائل النظام (مثل استعادة كلمة المرور) عبر
    | طابور المهام بدل الإرسال المباشر. يتطلب تشغيل: php artisan queue:work
    |
    */

    'mail' => [
        'queue_notifications' => env('MAIL_QUEUE_NOTIFICATIONS', false),
        'queue_name' => env('MAIL_QUEUE_NAME', 'emails'),
    ],

    /*
    |--------------------------------------------------------------------------
    | إعدادات رفع المرفقات (صور الفواتير والسندات)
    |--------------------------------------------------------------------------
    */

    'attachments' => [
        'disk' => env('ATTACHMENTS_DISK', 'public'),
        'max_size_kb' => (int) env('ATTACHMENTS_MAX_SIZE_KB', 4096),
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'],
        'paths' => [
            'expenses' => 'receipts/expenses',
            'payments' => 'receipts/payments',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | حدود التنبيهات الذكية
    |--------------------------------------------------------------------------
    |
    | debt_threshold_usd : حد الدين الذي يُعتبر تجاوزه تنبيهاً (بالدولار).
    | debt_idle_days     : عدد الأيام دون أي دفعة حتى يُعد التاجر متأخراً.
    | expense_ratio      : نسبة المصاريف من الإيراد التي يُنبَّه عند تجاوزها.
    | season_idle_days   : عمر الموسم النشط بلا مبيعات حتى يُنبَّه على مصاريفه.
    |
    */

    'alerts' => [
        'debt_threshold_usd' => (float) env('ALERT_DEBT_THRESHOLD_USD', 500),
        'debt_idle_days' => (int) env('ALERT_DEBT_IDLE_DAYS', 30),
        'expense_ratio' => (float) env('ALERT_EXPENSE_RATIO', 0.8),
        'season_idle_days' => (int) env('ALERT_SEASON_IDLE_DAYS', 120),
    ],

];
