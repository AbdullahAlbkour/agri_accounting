<?php

namespace App\Services;

use App\Models\BuyerPayment;
use App\Models\Sale;
use Illuminate\Support\Collection;

/**
 * حسابات التجار: يجمع بين فواتير البيع والدفعات اللاحقة (سندات القبض)
 * لاحتساب الرصيد المتبقي (الديون) بشكل موحّد بالدولار الأمريكي.
 */
class BuyerAccountService
{
    /**
     * ملخّص حساب تاجر معيّن.
     *
     * @param  Collection<int, Sale>|null  $sales  فواتير محددة (عند وجود فلترة)، أو null لكل فواتير التاجر.
     * @param  Collection<int, BuyerPayment>|null  $payments
     * @return array<string, float>
     */
    public function summary(string $buyerName, ?Collection $sales = null, ?Collection $payments = null): array
    {
        $sales ??= Sale::where('buyer_name', $buyerName)->get();
        $payments ??= BuyerPayment::where('buyer_name', $buyerName)->get();

        $totalSalesUSD = $sales->reduce(fn ($carry, $s) => $carry + $s->totalPriceUSD(), 0.0);
        $paidAtSaleUSD = $sales->reduce(fn ($carry, $s) => $carry + $s->paidAmountUSD(), 0.0);
        $paymentsUSD = $payments->reduce(fn ($carry, $p) => $carry + $p->amountUSD(), 0.0);

        $remainingUSD = $totalSalesUSD - $paidAtSaleUSD - $paymentsUSD;

        return [
            'total_sales_usd' => $totalSalesUSD,
            'paid_at_sale_usd' => $paidAtSaleUSD,
            'payments_usd' => $paymentsUSD,
            'total_collected_usd' => $paidAtSaleUSD + $paymentsUSD,
            // الرصيد المتبقي (الدين) لا يقل عن صفر، وأي فائض يظهر كرصيد دائن للتاجر
            'remaining_usd' => max(0, $remainingUSD),
            'credit_usd' => max(0, -$remainingUSD),
        ];
    }

    /**
     * الرصيد المتبقي (الدين) على تاجر معيّن بالدولار بعد خصم كل الدفعات.
     */
    public function remainingForBuyerUSD(string $buyerName): float
    {
        return $this->summary($buyerName)['remaining_usd'];
    }

    /**
     * إجمالي الديون المستحقة على كل التجار بالدولار (بعد خصم سندات القبض).
     */
    public function totalReceivablesUSD(): float
    {
        $salesByBuyer = Sale::all()->groupBy('buyer_name');
        $paymentsByBuyer = BuyerPayment::all()->groupBy('buyer_name');

        return $salesByBuyer->keys()
            ->merge($paymentsByBuyer->keys())
            ->unique()
            ->reduce(function (float $carry, $buyer) use ($salesByBuyer, $paymentsByBuyer) {
                $summary = $this->summary(
                    (string) $buyer,
                    $salesByBuyer->get($buyer, collect()),
                    $paymentsByBuyer->get($buyer, collect())
                );

                return $carry + $summary['remaining_usd'];
            }, 0.0);
    }

    /**
     * كشف مختصر بأرصدة كل التجار (مرتّب تنازلياً حسب حجم الدين).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function allBuyersBalances(): Collection
    {
        $salesByBuyer = Sale::all()->groupBy('buyer_name');
        $paymentsByBuyer = BuyerPayment::all()->groupBy('buyer_name');

        return $salesByBuyer->keys()
            ->merge($paymentsByBuyer->keys())
            ->unique()
            ->map(function ($buyer) use ($salesByBuyer, $paymentsByBuyer) {
                $summary = $this->summary(
                    (string) $buyer,
                    $salesByBuyer->get($buyer, collect()),
                    $paymentsByBuyer->get($buyer, collect())
                );

                return array_merge(['buyer_name' => $buyer], $summary);
            })
            ->sortByDesc('remaining_usd')
            ->values();
    }
}
