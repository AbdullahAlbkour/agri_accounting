<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NormalizesNumbers;
use App\Models\BuyerPayment;
use App\Models\Sale;
use App\Services\BuyerAccountService;
use Illuminate\Http\Request;

class BuyerPaymentController extends Controller
{
    use NormalizesNumbers;

    public function __construct(private BuyerAccountService $accounts) {}

    /**
     * سجل سندات القبض مع أرصدة التجار.
     */
    public function index(Request $request)
    {
        $query = BuyerPayment::with(['sale.season.crop', 'season']);

        if ($buyer = $request->input('buyer_name')) {
            $query->where('buyer_name', $buyer);
        }

        if ($from = $request->input('from_date')) {
            $query->whereDate('date', '>=', $from);
        }

        if ($to = $request->input('to_date')) {
            $query->whereDate('date', '<=', $to);
        }

        $payments = $query->latest('date')->latest('id')->paginate(15)->withQueryString();

        $buyers = Sale::select('buyer_name')->distinct()->pluck('buyer_name')
            ->merge(BuyerPayment::select('buyer_name')->distinct()->pluck('buyer_name'))
            ->unique()
            ->sort()
            ->values();

        $balances = $this->accounts->allBuyersBalances();

        $totalPaymentsUSD = BuyerPayment::all()->reduce(fn ($carry, $p) => $carry + $p->amountUSD(), 0.0);

        return view('buyer_payments.index', compact('payments', 'buyers', 'balances', 'totalPaymentsUSD'));
    }

    /**
     * تسجيل دفعة (سند قبض) جديدة من تاجر.
     */
    public function store(Request $request)
    {
        $request->merge([
            'amount' => $this->normalizeNumber($request->input('amount')),
            'exchange_rate' => $this->normalizeNumber($request->input('exchange_rate')) ?: 1,
        ]);

        $validated = $request->validate([
            'buyer_name' => 'required|string|max:255',
            'sale_id' => 'nullable|exists:sales,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,TRY,SYP',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ], [
            'buyer_name.required' => 'اسم التاجر مطلوب.',
            'amount.required' => 'مبلغ الدفعة مطلوب.',
            'amount.min' => 'مبلغ الدفعة يجب أن يكون أكبر من صفر.',
            'date.required' => 'تاريخ الدفعة مطلوب.',
        ]);

        $sale = null;

        if (! empty($validated['sale_id'])) {
            // الفاتورة تُجلب ضمن نطاق المستخدم الحالي لمنع الوصول لبيانات مزارع آخر
            $sale = Sale::find($validated['sale_id']);

            if (! $sale) {
                return back()->withInput()->with('error', 'الفاتورة المحددة غير موجودة ضمن حسابك.');
            }

            // التأكد من أن الفاتورة المختارة تعود لنفس التاجر
            if ($sale->buyer_name !== $validated['buyer_name']) {
                return back()->withInput()->with('error', 'الفاتورة المختارة لا تعود للتاجر المحدد.');
            }
        }

        $payment = BuyerPayment::create([
            'user_id' => $sale?->user_id ?? $request->user()->id,
            'buyer_name' => $validated['buyer_name'],
            'sale_id' => $sale?->id,
            'season_id' => $sale?->season_id,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'exchange_rate' => $validated['exchange_rate'] ?? 1,
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $payment->update(['receipt_number' => 'REC-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT)]);

        $remaining = $this->accounts->remainingForBuyerUSD($payment->buyer_name);

        return redirect()
            ->to($request->input('redirect_to') ?: route('buyer-payments.index'))
            ->with('success', 'تم تسجيل سند القبض '.$payment->receipt_number.' بنجاح. الرصيد المتبقي على التاجر: $'.number_format($remaining, 2));
    }

    /**
     * طباعة سند القبض.
     */
    public function receipt(BuyerPayment $buyerPayment)
    {
        $buyerPayment->load(['sale.season.crop', 'season']);

        $summary = $this->accounts->summary($buyerPayment->buyer_name);

        return view('buyer_payments.receipt', compact('buyerPayment', 'summary'));
    }

    /**
     * حذف سند قبض (يُعاد المبلغ تلقائياً إلى رصيد الديون).
     */
    public function destroy(Request $request, BuyerPayment $buyerPayment)
    {
        $buyerPayment->delete();

        return redirect()
            ->to($request->input('redirect_to') ?: route('buyer-payments.index'))
            ->with('success', 'تم حذف سند القبض وإعادة احتساب رصيد التاجر.');
    }
}
