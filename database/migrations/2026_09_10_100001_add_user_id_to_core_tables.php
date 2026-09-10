<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الجداول التي تُعزل بياناتها حسب المستخدم (المزارع).
     */
    protected array $tables = [
        'crops',
        'fields',
        'expense_categories',
        'seasons',
        'expenses',
        'sales',
        'buyer_payments',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('user_id')->nullable()->after('id')->index();
            });

            // SQLite لا يدعم إضافة مفتاح أجنبي إلى جدول موجود
            if (DB::getDriverName() !== 'sqlite') {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                });
            }
        }

        // ربط كل البيانات الحالية بحساب مدير النظام حفاظاً عليها
        $admin = DB::table('users')->where('role', 'admin')->orderBy('id')->first()
            ?? DB::table('users')->orderBy('id')->first();

        if ($admin) {
            foreach ($this->tables as $table) {
                DB::table($table)->whereNull('user_id')->update(['user_id' => $admin->id]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            // الترتيب مهم: المفتاح الأجنبي ثم الفهرس ثم العمود (خصوصاً على SQLite)
            if (DB::getDriverName() !== 'sqlite') {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->dropForeign($table.'_user_id_foreign');
                });
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropIndex($table.'_user_id_index');
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('user_id');
            });
        }
    }
};
