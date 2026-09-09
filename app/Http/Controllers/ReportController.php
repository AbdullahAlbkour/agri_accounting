<?php

namespace App\Http\Controllers;

use App\Models\BuyerPayment;
use App\Models\Crop;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Sale;
use App\Models\Season;
use App\Services\BuyerAccountService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private BuyerAccountService $accounts) {}

    /**
     * مطابقة نصية مرنة تتجاهل التشكيل واختلاف الهمزات وأل التعريف.
     */
    protected function matcher(): callable
    {
        return function ($text, $query) {
            if (! $query) {
                return true;
            }
            $cleanText = str_replace(['ال', 'أ', 'إ', 'آ', 'ة', 'ه'], ['', 'ا', 'ا', 'ا', 'ه', 'ه'], $text);
            $cleanQuery = str_replace(['ال', 'أ', 'إ', 'آ', 'ة', 'ه'], ['', 'ا', 'ا', 'ا', 'ه', 'ه'], $query);

            return mb_strpos(mb_strtolower($cleanText), mb_strtolower($cleanQuery)) !== false;
        };
    }

    public function index(Request $request)
    {
        $buyers = Sale::select('buyer_name')->distinct()->pluck('buyer_name')
            ->merge(BuyerPayment::select('buyer_name')->distinct()->pluck('buyer_name'))
            ->unique()
            ->sort()
            ->values();

        $categories = ExpenseCategory::all();

        $searchQuery = trim((string) $request->input('search_query'));

        $matchesSearch = $this->matcher();

        $selectedBuyer = $request->input('buyer_name');
        $buyerSales = collect();
        $buyerPayments = collect();
        $buyerOpenSales = collect();
        $buyerTotals = ['total_price' => 0, 'paid_amount' => 0, 'remaining_amount' => 0];
        $buyerSummary = null;

        if ($selectedBuyer) {
            $sales = Sale::with(['season.crop', 'season.field', 'payments'])
                ->where('buyer_name', $selectedBuyer)
                ->latest()
                ->get();

            if ($searchQuery) {
                $buyerSales = $sales->filter(function ($sale) use ($searchQuery, $matchesSearch) {
                    $seasonName = $sale->season->name ?? '';
                    $cropName = $sale->season->crop->name ?? '';
                    $fieldName = $sale->season->field->name ?? '';

                    return $matchesSearch($seasonName, $searchQuery) ||
                           $matchesSearch($cropName, $searchQuery) ||
                           $matchesSearch($fieldName, $searchQuery);
                });
            } else {
                $buyerSales = $sales;
            }

            $buyerTotals['total_price'] = $buyerSales->sum('total_price');
            $buyerTotals['paid_amount'] = $buyerSales->sum('paid_amount');
            $buyerTotals['remaining_amount'] = $buyerSales->sum('remaining_amount');

            $buyerPayments = BuyerPayment::with('sale.season')
                ->where('buyer_name', $selectedBuyer)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();

            // الرصيد الإجمالي للتاجر يُحتسب دائماً على كامل فواتيره ودفعاته (بالدولار)
            $buyerSummary = $this->accounts->summary($selectedBuyer, $sales, $buyerPayments);

            // الفواتير التي ما زال عليها رصيد (لاختيارها عند تسجيل دفعة)
            $buyerOpenSales = $sales->filter(fn (Sale $s) => $s->netRemainingUSD() > 0.009)->values();
        }

        $selectedCategory = $request->input('expense_category_id');
        $categoryExpenses = collect();
        $totalCategoryExpenseUSD = 0;

        if ($selectedCategory) {
            $expenses = Expense::with(['season.crop', 'season.field', 'category'])
                ->where('expense_category_id', $selectedCategory)
                ->latest()
                ->get();

            if ($searchQuery) {
                $categoryExpenses = $expenses->filter(function ($exp) use ($searchQuery, $matchesSearch) {
                    $seasonName = $exp->season->name ?? '';
                    $cropName = $exp->season->crop->name ?? '';
                    $fieldName = $exp->season->field->name ?? '';

                    return $matchesSearch($seasonName, $searchQuery) ||
                           $matchesSearch($cropName, $searchQuery) ||
                           $matchesSearch($fieldName, $searchQuery);
                });
            } else {
                $categoryExpenses = $expenses;
            }

            $totalCategoryExpenseUSD = $categoryExpenses->reduce(function ($carry, $e) {
                $rate = ($e->exchange_rate && $e->exchange_rate > 0) ? $e->exchange_rate : 1;

                return $carry + (($e->currency === 'USD') ? $e->amount : ($e->amount / $rate));
            }, 0);
        }

        $cropsAnalytics = $this->cropsAnalytics($searchQuery);

        // كشف أرصدة كل التجار بعد خصم سندات القبض
        $buyersBalances = $this->accounts->allBuyersBalances();

        return view('reports.index', compact(
            'buyers', 'categories', 'searchQuery',
            'selectedBuyer', 'buyerSales', 'buyerTotals', 'buyerPayments', 'buyerSummary', 'buyerOpenSales',
            'selectedCategory', 'categoryExpenses', 'totalCategoryExpenseUSD',
            'cropsAnalytics', 'buyersBalances'
        ));
    }

    /**
     * تحليل أداء المحاصيل للمواسم المطابقة للبحث.
     */
    protected function cropsAnalytics(string $searchQuery)
    {
        $matchesSearch = $this->matcher();

        return Crop::with(['seasons.expenses.category', 'seasons.sales', 'seasons.field'])->get()
            ->map(function (Crop $crop) use ($searchQuery, $matchesSearch) {
                $filteredSeasons = $crop->seasons->filter(function (Season $season) use ($searchQuery, $matchesSearch, $crop) {
                    if (! $searchQuery) {
                        return true;
                    }

                    return $matchesSearch($season->name ?? '', $searchQuery) ||
                           $matchesSearch($crop->name ?? '', $searchQuery) ||
                           $matchesSearch($season->field->name ?? '', $searchQuery);
                });

                $totalSales = 0;
                $totalExpenses = 0;
                $totalQuantity = 0;

                foreach ($filteredSeasons as $season) {
                    $totalSales += $season->totalSalesUSD();
                    $totalExpenses += $season->totalExpensesUSD();
                    $totalQuantity += $season->totalQuantityTons();
                }

                return [
                    'name' => $crop->name,
                    'seasons_count' => $filteredSeasons->count(),
                    'quantity_tons' => $totalQuantity,
                    'total_sales' => $totalSales,
                    'total_expenses' => $totalExpenses,
                    'net_profit' => $totalSales - $totalExpenses,
                ];
            })
            ->filter(fn ($item) => $item['seasons_count'] > 0)
            ->values();
    }

    public function exportExcel(Request $request)
    {
        $searchQuery = trim((string) $request->input('search_query'));
        $selectedBuyer = $request->input('buyer_name');
        $matchesSearch = $this->matcher();

        $buyerSales = collect();
        $buyerPayments = collect();
        $buyerSummary = null;

        if ($selectedBuyer) {
            $sales = Sale::with(['season.crop', 'season.field', 'payments'])
                ->where('buyer_name', $selectedBuyer)
                ->latest()
                ->get();

            $buyerSales = $searchQuery
                ? $sales->filter(fn ($s) => $matchesSearch($s->season->name ?? '', $searchQuery) || $matchesSearch($s->season->crop->name ?? '', $searchQuery))
                : $sales;

            $buyerPayments = BuyerPayment::where('buyer_name', $selectedBuyer)->orderByDesc('date')->get();
            $buyerSummary = $this->accounts->summary($selectedBuyer, $sales, $buyerPayments);
        }

        $cropsAnalytics = $this->cropsAnalytics($searchQuery);

        $fileName = 'agricultural-report-'.date('Y-m-d').'.xls';

        $headers = [
            'Content-type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($cropsAnalytics, $selectedBuyer, $buyerSales, $buyerPayments, $buyerSummary) {
            $e = fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

            echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head><meta charset="utf-8"></head>';
            echo '<body dir="rtl" style="font-family: Arial, sans-serif;">';

            echo '<h2 style="color: #2e7d32;">تقرير أداء المحاصيل الزراعية</h2>';
            echo '<table border="1" cellpadding="5" cellspacing="0">';
            echo '<thead style="background-color: #f2f2f2;"><tr><th>اسم المحصول</th><th>عدد المواسم</th><th>الإنتاج (طن)</th><th>إجمالي الإيرادات ($)</th><th>إجمالي المصاريف ($)</th><th>صافي الربح ($)</th></tr></thead>';
            echo '<tbody>';
            foreach ($cropsAnalytics as $item) {
                echo '<tr>';
                echo '<td>'.$e($item['name']).'</td>';
                echo '<td>'.$item['seasons_count'].'</td>';
                echo '<td>'.number_format($item['quantity_tons'], 3).'</td>';
                echo '<td>'.number_format($item['total_sales'], 2).'</td>';
                echo '<td>'.number_format($item['total_expenses'], 2).'</td>';
                echo '<td style="color:'.($item['net_profit'] >= 0 ? 'green' : 'red').'">'.number_format($item['net_profit'], 2).'</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';

            if (! empty($selectedBuyer)) {
                echo '<br><h2 style="color: #2e7d32;">كشف حساب التاجر: '.$e($selectedBuyer).'</h2>';

                if ($buyerSummary) {
                    echo '<table border="1" cellpadding="5" cellspacing="0">';
                    echo '<tr><th>إجمالي المبيعات ($)</th><th>الواصل عند البيع ($)</th><th>سندات القبض اللاحقة ($)</th><th>الرصيد المتبقي ($)</th></tr>';
                    echo '<tr>';
                    echo '<td>'.number_format($buyerSummary['total_sales_usd'], 2).'</td>';
                    echo '<td>'.number_format($buyerSummary['paid_at_sale_usd'], 2).'</td>';
                    echo '<td>'.number_format($buyerSummary['payments_usd'], 2).'</td>';
                    echo '<td style="color:'.($buyerSummary['remaining_usd'] > 0 ? 'red' : 'green').'">'.number_format($buyerSummary['remaining_usd'], 2).'</td>';
                    echo '</tr>';
                    echo '</table><br>';
                }

                echo '<h3>فواتير البيع</h3>';
                echo '<table border="1" cellpadding="5" cellspacing="0">';
                echo '<thead style="background-color: #f2f2f2;"><tr><th>التاريخ</th><th>الموسم</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th><th>الواصل</th><th>المتبقي</th></tr></thead>';
                echo '<tbody>';
                foreach ($buyerSales as $sale) {
                    echo '<tr>';
                    echo '<td>'.$e($sale->date).'</td>';
                    echo '<td>'.$e($sale->season->name ?? '').'</td>';
                    echo '<td>'.$e($sale->quantity).' '.$e($sale->unit).'</td>';
                    echo '<td>'.number_format($sale->unit_price, 2).' '.$e($sale->currency).'</td>';
                    echo '<td>'.number_format($sale->total_price, 2).' '.$e($sale->currency).'</td>';
                    echo '<td>'.number_format($sale->paid_amount, 2).' '.$e($sale->currency).'</td>';
                    echo '<td>'.number_format($sale->remaining_amount, 2).' '.$e($sale->currency).'</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';

                echo '<br><h3>سندات القبض (الدفعات اللاحقة)</h3>';
                echo '<table border="1" cellpadding="5" cellspacing="0">';
                echo '<thead style="background-color: #f2f2f2;"><tr><th>رقم السند</th><th>التاريخ</th><th>المبلغ</th><th>ما يعادله ($)</th><th>ملاحظات</th></tr></thead>';
                echo '<tbody>';
                foreach ($buyerPayments as $payment) {
                    echo '<tr>';
                    echo '<td>'.$e($payment->receipt_number).'</td>';
                    echo '<td>'.$e(optional($payment->date)->format('Y-m-d')).'</td>';
                    echo '<td>'.number_format($payment->amount, 2).' '.$e($payment->currency).'</td>';
                    echo '<td>'.number_format($payment->amountUSD(), 2).'</td>';
                    echo '<td>'.$e($payment->notes).'</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }

            echo '</body></html>';
        };

        return response()->stream($callback, 200, $headers);
    }
}
