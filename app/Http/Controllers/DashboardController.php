<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\Field;
use App\Models\Crop;

class DashboardController extends Controller
{
    public function index()
    {
        $seasonsCount = Season::where('status', 'active')->count();
        $fieldsCount = Field::count();
        $cropsCount = Crop::count();

        // حساب المصاريف بالدولار
        $totalExpensesUSD = Expense::all()->reduce(function ($carry, $e) {
            $rate = ($e->exchange_rate && $e->exchange_rate > 0) ? $e->exchange_rate : 1;
            $amountUSD = ($e->currency === 'USD') ? $e->amount : ($e->amount / $rate);
            return $carry + $amountUSD;
        }, 0);

        // حساب المبيعات بالدولار
        $totalSalesUSD = Sale::all()->reduce(function ($carry, $s) {
            $rate = ($s->exchange_rate && $s->exchange_rate > 0) ? $s->exchange_rate : 1;
            $totalUSD = ($s->currency === 'USD') ? $s->total_price : ($s->total_price / $rate);
            return $carry + $totalUSD;
        }, 0);

        $netProfitUSD = $totalSalesUSD - $totalExpensesUSD;

        // حساب الديون المستحقة بالدولار
        $totalReceivablesUSD = Sale::where('remaining_amount', '>', 0)->get()->reduce(function ($carry, $s) {
            $rate = ($s->exchange_rate && $s->exchange_rate > 0) ? $s->exchange_rate : 1;
            $remainingUSD = ($s->currency === 'USD') ? $s->remaining_amount : ($s->remaining_amount / $rate);
            return $carry + $remainingUSD;
        }, 0);

        $activeSeasons = Season::with(['crop', 'field', 'expenses', 'sales'])
            ->where('status', 'active')
            ->latest()
            ->get();

        $recentExpenses = Expense::with(['season.crop', 'category'])->latest()->take(5)->get();
        $recentSales = Sale::with('season.crop')->latest()->take(5)->get();

        return view('dashboard', compact(
            'seasonsCount',
            'fieldsCount',
            'cropsCount',
            'totalExpensesUSD',
            'totalSalesUSD',
            'netProfitUSD',
            'totalReceivablesUSD',
            'activeSeasons',
            'recentExpenses',
            'recentSales'
        ));
    }
}