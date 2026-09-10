<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->enum('role', ['admin', 'farmer'])->default('farmer')->after('email');
            $table->string('phone')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('phone');
        });

        // أول حساب في النظام هو مدير النظام، ويُمنح اسم مستخدم تلقائياً
        $first = DB::table('users')->orderBy('id')->first();

        if ($first) {
            DB::table('users')->where('id', $first->id)->update(['role' => 'admin']);
        }

        foreach (DB::table('users')->whereNull('username')->get() as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'username' => Str::before($user->email, '@').($user->id > 1 ? $user->id : ''),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'phone', 'is_active']);
        });
    }
};
