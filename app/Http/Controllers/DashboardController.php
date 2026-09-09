<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Expense;
use App\Models\Field;
use App\Models\Sale;
use App\Models\Season;
use App\Services\BuyerAccountService;

class DashboardController extends Controller
{
    public function __construct(private BuyerAccountService $accounts) {}

    public function index()
    {
        $seasonsCount = Season::active()->count();
        $closedSeasonsCount = Season::closed()->count();
        $fieldsCount = Field::count();
        $cropsCount = Crop::count();

        $expenses = Expense::with('category')->get();
        $sales = Sale::all();

        // حساب المصاريف بالدولار
        $totalExpensesUSD = $expenses->reduce(function ($carry, $e) {
            $rate = ($e->exchange_rate && $e->exchange_rate > 0) ? $e->exchange_rate : 1;
            $amountUSD = ($e->currency === 'USD') ? $e->amount : ($e->amount / $rate);

            return $carry + $amountUSD;
        }, 0);

        // حساب المبيعات بالدولار
        $totalSalesUSD = $sales->reduce(function ($carry, $s) {
            $rate = ($s->exchange_rate && $s->exchange_rate > 0) ? $s->exchange_rate : 1;
            $totalUSD = ($s->currency === 'USD') ? $s->total_price : ($s->total_price / $rate);

            return $carry + $totalUSD;
        }, 0);

        $netProfitUSD = $totalSalesUSD - $totalExpensesUSD;

        // الديون المستحقة بالدولار بعد خصم سندات القبض (دفعات التجار اللاحقة)
        $totalReceivablesUSD = $this->accounts->totalReceivablesUSD();

        $activeSeasons = Season::with(['crop', 'field', 'expenses', 'sales'])
            ->active()
            ->latest()
            ->get();

        // بيانات المخطط الأول: مقارنة صافي أرباح المحاصيل
        $cropsChart = Crop::with(['seasons.expenses', 'seasons.sales'])->get()
            ->map(function (Crop $crop) {
                $cropSales = $crop->seasons->sum(fn (Season $s) => $s->totalSalesUSD());
                $cropExpenses = $crop->seasons->sum(fn (Season $s) => $s->totalExpensesUSD());

                return [
                    'name' => $crop->name,
                    'sales' => round($cropSales, 2),
                    'expenses' => round($cropExpenses, 2),
                    'net_profit' => round($cropSales - $cropExpenses, 2),
                ];
            })
            ->filter(fn ($row) => $row['sales'] > 0 || $row['expenses'] > 0)
            ->sortByDesc('net_profit')
            ->values();

        // بيانات المخطط الثاني: توزيع نسب المصاريف حسب البنود
        $expensesChart = $expenses
            ->groupBy(fn ($e) => $e->category->name ?? 'غير مصنّف')
            ->map(function ($group, $name) {
                $total = $group->reduce(function ($carry, $e) {
                    $rate = ($e->exchange_rate && $e->exchange_rate > 0) ? $e->exchange_rate : 1;

                    return $carry + (($e->currency === 'USD') ? $e->amount : ($e->amount / $rate));
                }, 0);

                return ['name' => $name, 'total' => round($total, 2)];
            })
            ->filter(fn ($row) => $row['total'] > 0)
            ->sortByDesc('total')
            ->values();

        // أعلى أرصدة الديون على التجار
        $topDebtors = $this->accounts->allBuyersBalances()
            ->filter(fn ($row) => $row['remaining_usd'] > 0)
            ->take(5);

        $recentExpenses = Expense::with(['season.crop', 'category'])->latest()->take(5)->get();
        $recentSales = Sale::with('season.crop')->latest()->take(5)->get();

        return view('dashboard', compact(
            'seasonsCount',
            'closedSeasonsCount',
            'fieldsCount',
            'cropsCount',
            'totalExpensesUSD',
            'totalSalesUSD',
            'netProfitUSD',
            'totalReceivablesUSD',
            'activeSeasons',
            'cropsChart',
            'expensesChart',
            'topDebtors',
            'recentExpenses',
            'recentSales'
        ));
    }
}
