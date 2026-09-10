<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سند قبض {{ $buyerPayment->receipt_number }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Cairo', sans-serif; background: #f1f5f9; padding: 30px; }
        .receipt { max-width: 760px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 6px 25px rgba(0,0,0,.08); }
        .receipt-header { border-bottom: 3px double #1b5e20; padding-bottom: 16px; margin-bottom: 24px; }
        .amount-box { background: #e8f5e9; border: 2px dashed #2e7d32; border-radius: 12px; padding: 18px; text-align: center; }
        .sign { margin-top: 56px; display: flex; justify-content: space-between; }
        .sign div { width: 40%; border-top: 1px solid #94a3b8; text-align: center; padding-top: 8px; color: #475569; }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt { box-shadow: none; border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="receipt">
    <div class="receipt-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold text-success mb-1"><i class="fa-solid fa-leaf me-2"></i>نظام المحاسبة الزراعية</h4>
            <p class="text-muted mb-0">سند قبض نقدي</p>
        </div>
        <div class="text-start">
            <div class="fw-bold font-monospace fs-5">{{ $buyerPayment->receipt_number }}</div>
            <div class="text-muted">التاريخ: {{ $buyerPayment->date?->format('Y-m-d') }}</div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="p-3 bg-light rounded border">
                <span class="text-muted d-block">استلمنا من السيد/التاجر</span>
                <strong class="fs-5">{{ $buyerPayment->buyer_name }}</strong>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 bg-light rounded border">
                <span class="text-muted d-block">مقابل</span>
                <strong>
                    @if($buyerPayment->sale)
                        فاتورة بيع رقم #{{ $buyerPayment->sale->id }}
                        @if($buyerPayment->sale->season) — موسم {{ $buyerPayment->sale->season->name }} @endif
                    @else
                        دفعة على الحساب الجاري
                    @endif
                </strong>
            </div>
        </div>
    </div>

    <div class="amount-box mb-4">
        <span class="text-muted d-block">المبلغ المقبوض</span>
        <h2 class="fw-bold text-success font-monospace mb-1">{{ number_format($buyerPayment->amount, 2) }} {{ $buyerPayment->currency }}</h2>
        @if($buyerPayment->currency !== 'USD')
            <span class="text-muted">ما يعادل ${{ number_format($buyerPayment->amountUSD(), 2) }} (سعر الصرف: {{ number_format($buyerPayment->exchange_rate, 2) }})</span>
        @endif
    </div>

    <table class="table table-bordered align-middle">
        <tbody>
            <tr>
                <th class="bg-light" style="width: 45%;">إجمالي مبيعات التاجر</th>
                <td class="font-monospace">${{ number_format($summary['total_sales_usd'], 2) }}</td>
            </tr>
            <tr>
                <th class="bg-light">إجمالي المقبوض (واصل البيع + السندات)</th>
                <td class="font-monospace text-success">${{ number_format($summary['total_collected_usd'], 2) }}</td>
            </tr>
            <tr>
                <th class="bg-light">الرصيد المتبقي بعد هذه الدفعة</th>
                <td class="font-monospace fw-bold {{ $summary['remaining_usd'] > 0 ? 'text-danger' : 'text-success' }}">
                    ${{ number_format($summary['remaining_usd'], 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    @if($buyerPayment->notes)
    <div class="alert alert-light border">
        <strong>ملاحظات:</strong> {{ $buyerPayment->notes }}
    </div>
    @endif

    @if($buyerPayment->hasReceipt())
    <div class="border rounded p-3 mb-3">
        <div class="fw-bold mb-2"><i class="fa-solid fa-paperclip me-1"></i> المرفق (صورة السند / الإشعار البنكي)</div>
        @if($buyerPayment->receiptIsPdf())
            <a href="{{ $buyerPayment->receiptUrl() }}" target="_blank" class="btn btn-outline-danger">
                <i class="fa-solid fa-file-pdf me-1"></i> فتح ملف PDF المرفق
            </a>
        @else
            <img src="{{ $buyerPayment->receiptUrl() }}" alt="مرفق السند" class="img-fluid rounded border" style="max-height: 320px;">
            <div class="mt-2 no-print">
                <a href="{{ $buyerPayment->receiptUrl() }}" target="_blank" class="small">فتح المرفق بالحجم الكامل</a>
            </div>
        @endif
    </div>
    @endif

    <div class="sign">
        <div>توقيع المستلم</div>
        <div>توقيع الدافع</div>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-success px-4 rounded-pill fw-bold">
            <i class="fa-solid fa-print me-1"></i> طباعة السند
        </button>
        <a href="{{ route('buyer-payments.index') }}" class="btn btn-outline-secondary px-4 rounded-pill">رجوع</a>
    </div>
</div>

</body>
</html>
