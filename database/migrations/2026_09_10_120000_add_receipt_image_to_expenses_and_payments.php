<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // مسار صورة فاتورة الشراء / إيصال الصرف
            $table->string('receipt_image')->nullable()->after('notes');
        });

        Schema::table('buyer_payments', function (Blueprint $table) {
            // مسار صورة سند القبض / الإشعار البنكي أو الحوالة
            $table->string('receipt_image')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('receipt_image');
        });

        Schema::table('buyer_payments', function (Blueprint $table) {
            $table->dropColumn('receipt_image');
        });
    }
};
