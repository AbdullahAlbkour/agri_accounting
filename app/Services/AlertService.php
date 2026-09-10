<?php

namespace App\Services;

use App\Models\BuyerPayment;
use App\Models\Crop;
use App\Models\Sale;
use App\Models\Season;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * التنبيهات الذكية: ديون التجار المتأخرة وتجاوز سقف المصاريف.
 *
 * كل الاستعلامات تمرّ عبر نطاق عزل البيانات، فيرى كل مزارع تنبيهات بياناته فقط
 * بينما يرى مدير النظام تنبيهات كل الحسابات.
 */
class AlertService
{
    /** @var Collection<int, array<string, mixed>>|null */
    protected ?Collection $cached = null;

    /** المستخدم الذي حُسبت له النتيجة المخزّنة مؤقتاً */
    protected int|string|null $cachedFor = null;

    public function __construct(private BuyerAccountService $accounts) {}

    /**
     * حدود التنبيهات المعتمدة (قابلة للضبط من ملف .env).
     *
     * @return array<string, float|int>
     */
    public function thresholds(): array
    {
        return [
            'debt_threshold_usd' => (float) config('agri.alerts.debt_threshold_usd'),
            'debt_idle_days' => (int) config('agri.alerts.debt_idle_days'),
            'expense_ratio' => (float) config('agri.alerts.expense_ratio'),
            'season_idle_days' => (int) config('agri.alerts.season_idle_days'),
        ];
    }

    /**
     * كل التنبيهات مرتّبة حسب الخطورة.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function all(): Collection
    {
        $currentUser = Auth::id();

        // النتيجة تُحفظ لكل مستخدم على حدة حتى لا تُخلط تنبيهات حسابين في نفس الطلب
        if ($this->cached !== null && $this->cachedFor === $currentUser) {
            return $this->cached;
        }

        $this->cachedFor = $currentUser;

        $alerts = $this->debtAlerts()
            ->merge($this->expenseRatioAlerts())
            ->merge($this->idleSeasonAlerts());

        $weight = ['danger' => 0, 'warning' => 1, 'info' => 2];

        return $this->cached = $alerts
            ->sortBy(fn (array $alert) => [$weight[$alert['level']] ?? 3, -($alert['sort_value'] ?? 0)])
            ->values();
    }

    public function count(): int
    {
        return $this->all()->count();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function ofType(string $type): Collection
    {
        return $this->all()->where('type', $type)->values();
    }

    /**
     * تنبيهات ديون التجار: تجاوز حد الدين أو التأخر في السداد.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function debtAlerts(): Collection
    {
        $thresholds = $this->thresholds();

        $lastPaymentPerBuyer = BuyerPayment::all()->groupBy('buyer_name')
            ->map(fn ($payments) => $payments->max(fn ($p) => $p->date ? Carbon::parse($p->date) : null));

        $lastSalePerBuyer = Sale::all()->groupBy('buyer_name')
            ->map(fn ($sales) => $sales->max(fn ($s) => $s->date ? Carbon::parse($s->date) : null));

        return $this->accounts->allBuyersBalances()
            ->filter(fn (array $row) => $row['remaining_usd'] > 0.009)
            ->map(function (array $row) use ($thresholds, $lastPaymentPerBuyer, $lastSalePerBuyer) {
                $buyer = $row['buyer_name'];
                $lastPayment = $lastPaymentPerBuyer[$buyer] ?? null;
                $lastActivity = $lastPayment ?? ($lastSalePerBuyer[$buyer] ?? null);
                $idleDays = $lastActivity ? (int) $lastActivity->startOfDay()->diffInDays(Carbon::now()->startOfDay()) : null;

                $overThreshold = $row['remaining_usd'] >= $thresholds['debt_threshold_usd'];
                $overdue = $idleDays !== null && $idleDays >= $thresholds['debt_idle_days'];

                if (! $overThreshold && ! $overdue) {
                    return null;
                }

                $reasons = [];

                if ($overThreshold) {
                    $reasons[] = 'الدين ($'.number_format($row['remaining_usd'], 2).') تجاوز الحد المسموح ($'
                        .number_format($thresholds['debt_threshold_usd'], 2).')';
                }

                if ($overdue) {
                    $reasons[] = $lastPayment
                        ? 'آخر دفعة منذ '.$idleDays.' يوماً ('.$lastPayment->format('Y-m-d').')'
                        : 'لم تُسجَّل أي دفعة منذ '.$idleDays.' يوماً من تاريخ البيع';
                }

                return [
                    'type' => 'debt',
                    'level' => ($overThreshold && $overdue) ? 'danger' : ($overThreshold ? 'danger' : 'warning'),
                    'icon' => 'fa-user-clock',
                    'title' => 'دين متأخر: '.$buyer,
                    'message' => implode(' — ', $reasons).'.',
                    'amount' => $row['remaining_usd'],
                    'sort_value' => $row['remaining_usd'],
                    'url' => route('reports.index', ['buyer_name' => $buyer]),
                    'action' => 'فتح كشف الحساب',
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * تنبيهات تجاوز سقف المصاريف على مستوى الموسم والمحصول.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function expenseRatioAlerts(): Collection
    {
        $ratioLimit = $this->thresholds()['expense_ratio'];
        $percentLimit = round($ratioLimit * 100);

        $seasonAlerts = Season::with(['crop', 'field', 'expenses', 'sales'])->get()
            ->map(function (Season $season) use ($ratioLimit, $percentLimit) {
                $sales = $season->totalSalesUSD();
                $expenses = $season->totalExpensesUSD();

                if ($sales <= 0 || $expenses <= 0) {
                    return null;
                }

                $ratio = $expenses / $sales;

                if ($ratio < $ratioLimit) {
                    return null;
                }

                $percent = round($ratio * 100, 1);

                return [
                    'type' => 'expense_ratio',
                    'level' => $ratio >= 1 ? 'danger' : 'warning',
                    'icon' => 'fa-money-bill-trend-up',
                    'title' => 'سقف مصاريف الموسم: '.$season->name,
                    'message' => 'المصاريف ($'.number_format($expenses, 2).') تعادل '.$percent
                        .'% من إيراد الموسم ($'.number_format($sales, 2).')، والحد المسموح '.$percentLimit.'%.'
                        .($ratio >= 1 ? ' الموسم يعمل بخسارة حالياً.' : ''),
                    'amount' => $expenses - $sales,
                    'sort_value' => $percent,
                    'url' => route('seasons.show', $season),
                    'action' => 'عرض الموسم',
                ];
            })
            ->filter();

        $cropAlerts = Crop::with(['seasons.expenses', 'seasons.sales'])->get()
            ->map(function (Crop $crop) use ($ratioLimit, $percentLimit) {
                $sales = $crop->seasons->sum(fn (Season $s) => $s->totalSalesUSD());
                $expenses = $crop->seasons->sum(fn (Season $s) => $s->totalExpensesUSD());

                if ($sales <= 0 || $expenses <= 0 || $crop->seasons->count() < 2) {
                    return null;
                }

                $ratio = $expenses / $sales;

                if ($ratio < $ratioLimit) {
                    return null;
                }

                $percent = round($ratio * 100, 1);

                return [
                    'type' => 'expense_ratio',
                    'level' => $ratio >= 1 ? 'danger' : 'warning',
                    'icon' => 'fa-wheat-awn-circle-exclamation',
                    'title' => 'سقف مصاريف المحصول: '.$crop->name,
                    'message' => 'إجمالي مصاريف مواسم هذا المحصول ($'.number_format($expenses, 2).') تعادل '
                        .$percent.'% من إيراداته ($'.number_format($sales, 2).')، والحد المسموح '.$percentLimit.'%.',
                    'amount' => $expenses - $sales,
                    'sort_value' => $percent,
                    'url' => route('reports.index', ['search_query' => $crop->name]),
                    'action' => 'عرض التقرير',
                ];
            })
            ->filter();

        return $seasonAlerts->merge($cropAlerts)->values();
    }

    /**
     * مواسم نشطة صُرف عليها ولم تُسجَّل لها مبيعات منذ مدة طويلة.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function idleSeasonAlerts(): Collection
    {
        $idleDays = $this->thresholds()['season_idle_days'];

        return Season::with(['expenses', 'sales'])->active()->get()
            ->map(function (Season $season) use ($idleDays) {
                $expenses = $season->totalExpensesUSD();

                if ($expenses <= 0 || $season->sales->isNotEmpty() || ! $season->start_date) {
                    return null;
                }

                $age = (int) Carbon::parse($season->start_date)->startOfDay()->diffInDays(Carbon::now()->startOfDay());

                if ($age < $idleDays) {
                    return null;
                }

                return [
                    'type' => 'season_idle',
                    'level' => 'info',
                    'icon' => 'fa-hourglass-half',
                    'title' => 'موسم بلا إيرادات: '.$season->name,
                    'message' => 'مضى '.$age.' يوماً على بدء الموسم وصُرف عليه $'.number_format($expenses, 2)
                        .' دون تسجيل أي مبيعات.',
                    'amount' => $expenses,
                    'sort_value' => $age,
                    'url' => route('seasons.show', $season),
                    'action' => 'عرض الموسم',
                ];
            })
            ->filter()
            ->values();
    }
}
