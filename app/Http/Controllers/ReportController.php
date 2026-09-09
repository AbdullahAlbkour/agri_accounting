<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Crop;
use App\Models\Season;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $buyers = Sale::select('buyer_name')->distinct()->pluck('buyer_name');
        $categories = ExpenseCategory::all();
        
        $searchQuery = trim($request->input('search_query'));

        $matchesSearch = function($text, $query) {
            if (!$query) return true;
            $cleanText = str_replace(['ال', 'أ', 'إ', 'آ', 'ة', 'ه'], ['', 'ا', 'ا', 'ا', 'ه', 'ه'], $text);
            $cleanQuery = str_replace(['ال', 'أ', 'إ', 'آ', 'ة', 'ه'], ['', 'ا', 'ا', 'ا', 'ه', 'ه'], $query);
            return mb_strpos(mb_strtolower($cleanText), mb_strtolower($cleanQuery)) !== false;
        };

        $selectedBuyer = $request->input('buyer_name');
        $buyerSales = [];
        $buyerTotals = ['total_price' => 0, 'paid_amount' => 0, 'remaining_amount' => 0];

        if ($selectedBuyer) {
            $query = Sale::with('season.crop', 'season.field')->where('buyer_name', $selectedBuyer);
            $sales = $query->latest()->get();

            if ($searchQuery) {
                $buyerSales = $sales->filter(function($sale) use ($searchQuery, $matchesSearch) {
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
        }

        $selectedCategory = $request->input('expense_category_id');
        $categoryExpenses = [];
        $totalCategoryExpenseUSD = 0;

        if ($selectedCategory) {
            $query = Expense::with(['season.crop', 'season.field', 'category'])->where('expense_category_id', $selectedCategory);
            $expenses = $query->latest()->get();

            if ($searchQuery) {
                $categoryExpenses = $expenses->filter(function($exp) use ($searchQuery, $matchesSearch) {
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

        $cropsAnalytics = Crop::with(['seasons.expenses.category', 'seasons.sales', 'seasons.field'])->get()->map(function ($crop) use ($searchQuery, $matchesSearch) {
            
            $filteredSeasons = $crop->seasons->filter(function($season) use ($searchQuery, $matchesSearch, $crop) {
                if (!$searchQuery) return true;
                $seasonName = $season->name ?? '';
                $cropName = $crop->name ?? '';
                $fieldName = $season->field->name ?? '';
                
                return $matchesSearch($seasonName, $searchQuery) || 
                       $matchesSearch($cropName, $searchQuery) || 
                       $matchesSearch($fieldName, $searchQuery);
            });

            $totalSales = 0;
            $totalExpenses = 0;

            foreach ($filteredSeasons as $season) {
                $totalSales += $season->totalSalesUSD();
                $totalExpenses += $season->totalExpensesUSD();
            }

            return [
                'name' => $crop->name,
                'seasons_count' => $filteredSeasons->count(),
                'total_sales' => $totalSales,
                'total_expenses' => $totalExpenses,
                'net_profit' => $totalSales - $totalExpenses,
            ];
        })->filter(function($item) {
            return $item['seasons_count'] > 0;
        });

        return view('reports.index', compact(
            'buyers', 'categories', 'searchQuery',
            'selectedBuyer', 'buyerSales', 'buyerTotals',
            'selectedCategory', 'categoryExpenses', 'totalCategoryExpenseUSD',
            'cropsAnalytics'
        ));
    }

    public function exportExcel(Request $request)
    {
        $searchQuery = trim($request->input('search_query'));
        $selectedBuyer = $request->input('buyer_name');
        
        $matchesSearch = function($text, $query) {
            if (!$query) return true;
            $cleanText = str_replace(['ال', 'أ', 'إ', 'آ', 'ة', 'ه'], ['', 'ا', 'ا', 'ا', 'ه', 'ه'], $text);
            $cleanQuery = str_replace(['ال', 'أ', 'إ', 'آ', 'ة', 'ه'], ['', 'ا', 'ا', 'ا', 'ه', 'ه'], $query);
            return mb_strpos(mb_strtolower($cleanText), mb_strtolower($cleanQuery)) !== false;
        };

        $buyerSales = [];
        if ($selectedBuyer) {
            $sales = Sale::with('season.crop', 'season.field')->where('buyer_name', $selectedBuyer)->latest()->get();
            $buyerSales = $searchQuery ? $sales->filter(function($s) use ($searchQuery, $matchesSearch) {
                return $matchesSearch($s->season->name ?? '', $searchQuery) || $matchesSearch($s->season->crop->name ?? '', $searchQuery);
            }) : $sales;
        }

        $cropsAnalytics = Crop::with(['seasons.expenses.category', 'seasons.sales', 'seasons.field'])->get()->map(function ($crop) use ($searchQuery, $matchesSearch) {
            $filteredSeasons = $crop->seasons->filter(function($season) use ($searchQuery, $matchesSearch, $crop) {
                return $matchesSearch($season->name ?? '', $searchQuery) || $matchesSearch($crop->name ?? '', $searchQuery);
            });
            $totalSales = 0;
            $totalExpenses = 0;
            foreach ($filteredSeasons as $season) {
                $totalSales += $season->totalSalesUSD();
                $totalExpenses += $season->totalExpensesUSD();
            }
            return [
                'name' => $crop->name,
                'seasons_count' => $filteredSeasons->count(),
                'total_sales' => $totalSales,
                'total_expenses' => $totalExpenses,
                'net_profit' => $totalSales - $totalExpenses,
            ];
        })->filter(function($item) {
            return $item['seasons_count'] > 0;
        });

        // تم تغيير الصيغة لتصبح xls إكسل حقيقي
        $fileName = 'agricultural-report-' . date('Y-m-d') . '.xls';
        
        $headers = [
            "Content-type"        => "application/vnd.ms-excel; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($cropsAnalytics, $selectedBuyer, $buyerSales) {
            // تصميم كود جدولي يقرأه الإكسل مباشرة مع دعم الاتجاه واللغة
            echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head><meta charset="utf-8"></head>';
            echo '<body dir="rtl" style="font-family: Arial, sans-serif;">';
            
            echo '<h2 style="color: #2e7d32;">تقرير أداء المحاصيل الزراعية</h2>';
            echo '<table border="1" cellpadding="5" cellspacing="0">';
            echo '<thead style="background-color: #f2f2f2;"><tr><th>اسم المحصول</th><th>عدد المواسم</th><th>إجمالي الإيرادات ($)</th><th>إجمالي المصاريف ($)</th><th>صافي الربح ($)</th></tr></thead>';
            echo '<tbody>';
            foreach ($cropsAnalytics as $item) {
                echo '<tr>';
                echo '<td>' . $item['name'] . '</td>';
                echo '<td>' . $item['seasons_count'] . '</td>';
                echo '<td>' . number_format($item['total_sales'], 2) . '</td>';
                echo '<td>' . number_format($item['total_expenses'], 2) . '</td>';
                echo '<td style="color:'. ($item['net_profit'] >= 0 ? 'green' : 'red') .'">' . number_format($item['net_profit'], 2) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';

            if (!empty($selectedBuyer)) {
                echo '<br><h2 style="color: #2e7d32;">كشف حساب التاجر: ' . $selectedBuyer . '</h2>';
                echo '<table border="1" cellpadding="5" cellspacing="0">';
                echo '<thead style="background-color: #f2f2f2;"><tr><th>التاريخ</th><th>الموسم</th><th>الكمية</th><th>سعر الوحدة</th><th>الإجمالي</th><th>الواصل</th><th>المتبقي</th></tr></thead>';
                echo '<tbody>';
                foreach ($buyerSales as $sale) {
                    echo '<tr>';
                    echo '<td>' . $sale->date . '</td>';
                    echo '<td>' . ($sale->season->name ?? '') . '</td>';
                    echo '<td>' . $sale->quantity . ' ' . $sale->unit . '</td>';
                    echo '<td>' . number_format($sale->unit_price, 2) . ' ' . $sale->currency . '</td>';
                    echo '<td>' . number_format($sale->total_price, 2) . ' ' . $sale->currency . '</td>';
                    echo '<td>' . number_format($sale->paid_amount, 2) . ' ' . $sale->currency . '</td>';
                    echo '<td>' . number_format($sale->remaining_amount, 2) . ' ' . $sale->currency . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
            
            echo '</body></html>';
        };

        return response()->stream($callback, 200, $headers);
    }
}