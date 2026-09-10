<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BuyerPaymentController;
use App\Http\Controllers\CropController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SeasonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسارات المصادقة (تسجيل الدخول، إنشاء حساب، استعادة كلمة المرور)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| مسارات النظام المحمية (تتطلب تسجيل الدخول)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // التنبيهات والإشعارات الذكية
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');

    // التقارير
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');

    // أرشيف المواسم وإغلاقها (قبل resource حتى لا يبتلع المسار archive)
    Route::get('/seasons/archive', [SeasonController::class, 'archive'])->name('seasons.archive');
    Route::post('/seasons/{season}/close', [SeasonController::class, 'close'])->name('seasons.close');
    Route::post('/seasons/{season}/reopen', [SeasonController::class, 'reopen'])->name('seasons.reopen');

    // سندات القبض ودفعات التجار
    Route::get('/buyer-payments', [BuyerPaymentController::class, 'index'])->name('buyer-payments.index');
    Route::post('/buyer-payments', [BuyerPaymentController::class, 'store'])->name('buyer-payments.store');
    Route::get('/buyer-payments/{buyerPayment}/receipt', [BuyerPaymentController::class, 'receipt'])->name('buyer-payments.receipt');
    Route::delete('/buyer-payments/{buyerPayment}', [BuyerPaymentController::class, 'destroy'])->name('buyer-payments.destroy');

    // الإعدادات (النسخ الاحتياطي لكامل قاعدة البيانات مخصص لمدير النظام)
    Route::get('/settings', [BackupController::class, 'index'])->name('settings.index');
    Route::get('/settings/backup/download', [BackupController::class, 'download'])
        ->middleware('admin')
        ->name('backup.download');

    // لوحة تحكم مدير النظام: إدارة حسابات المزارعين
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::post('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::resource('users', AdminUserController::class);
    });

    // هذه المتحكمات لا تملك صفحة عرض مفردة (show)، والتفاصيل تُعرض ضمن صفحة الموسم
    Route::resource('crops', CropController::class)->except(['show']);
    Route::resource('fields', FieldController::class)->except(['show']);
    Route::resource('seasons', SeasonController::class);
    Route::resource('expenses', ExpenseController::class)->except(['show']);
    Route::resource('sales', SaleController::class)->except(['show']);
});
