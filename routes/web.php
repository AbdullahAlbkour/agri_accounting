<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CropController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\SeasonController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');

Route::resource('crops', CropController::class);
Route::resource('fields', FieldController::class);
Route::resource('seasons', SeasonController::class);
Route::resource('expenses', ExpenseController::class);
Route::resource('sales', SaleController::class);
