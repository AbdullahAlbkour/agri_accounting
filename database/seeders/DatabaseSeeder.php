<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * إنشاء حساب المدير الافتراضي للنظام.
     *
     * يمكن تخصيص البيانات عبر متغيرات البيئة:
     * ADMIN_NAME, ADMIN_USERNAME, ADMIN_EMAIL, ADMIN_PASSWORD
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@agri.local');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'مدير النظام'),
                'username' => env('ADMIN_USERNAME', 'admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        $this->command?->info('تم إنشاء/تحديث حساب المدير: '.$email);
    }
}
