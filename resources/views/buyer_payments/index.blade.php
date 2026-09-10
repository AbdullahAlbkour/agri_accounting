@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-money-check-dollar text-success me-2"></i>سندات القبض ودفعات التجار</h4>
        <p class="text-muted mb-0">توثيق الدفعات اللاحقة من التجار وخصمها تلقائياً من الديون المستحقة</p>
    </div>
    <button type="button" class="btn btn-success px-4 py-2 rounded-pill fw-bold no-print" data-bs-toggle="modal" data-bs-target="#newPaymentModal">
        <i class="fa-solid fa-plus me-1"></i> تسجيل دفعة جديدة
    </button>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<!-- أرصدة التجار -->
<div class="card stat-card p-4 mb-4">
    <h5 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-scale-balanced me-2"></i>أرصدة التجار (بالدولار)</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>التاجر</th>
                    <th>إجمالي المبيعات</th>
                    <th>الواصل عند البيع</th>
                    <th>سندات القبض</th>
                    <th>الرصيد المتبقي (الدين)</th>
                    <th class="text-center no-print">كشف الحساب</th>
                </tr>
            </thead>
            <tbody>
                @forelse($balances as $row)
                <tr>
                    <td><strong>{{ $row['buyer_name'] }}</strong></td>
                    <td class="font-monospace">${{ number_format($row['total_sales_usd'], 2) }}</td>
                    <td class="font-monospace text-success">${{ number_format($row['paid_at_sale_usd'], 2) }}</td>
                    <td class="font-monospace text-primary">${{ number_format($row['payments_usd'], 2) }}</td>
                    <td class="font-monospace fw-bold {{ $row['remaining_usd'] > 0 ? 'text-danger' : 'text-success' }}">
                        ${{ number_format($row['remaining_usd'], 2) }}
                        @if($row['credit_usd'] > 0)
                            <span class="badge bg-info-subtle text-info ms-1">رصيد دائن ${{ number_format($row['credit_usd'], 2) }}</span>
                        @endif
                    </td>
                    <td class="text-center no-print">
                        <a href="{{ route('reports.index', ['buyer_name' => $row['buyer_name']]) }}" class="btn btn-sm btn-outline-success">
                            <i class="fa-solid fa-file-invoice-dollar"></i> كشف الحساب
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">لا توجد حسابات تجار بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- سجل سندات القبض -->
<div class="card stat-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-success mb-0"><i class="fa-solid fa-receipt me-2"></i>سجل سندات القبض</h5>
        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
            إجمالي المقبوضات: ${{ number_format($totalPaymentsUSD, 2) }}
        </span>
    </div>

    <form action="{{ route('buyer-payments.index') }}" method="GET" class="row g-3 align-items-end mb-4 no-print">
        <div class="col-md-4">
            <label class="form-label fw-semibold">التاجر</label>
            <select name="buyer_name" class="form-select">
                <option value="">-- كل التجار --</option>
                @foreach($buyers as $buyer)
                    <option value="{{ $buyer }}" {{ request('buyer_name') === $buyer ? 'selected' : '' }}>{{ $buyer }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">من تاريخ</label>
            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">إلى تاريخ</label>
            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-dark w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> فلترة</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>رقم السند</th>
                    <th>التاريخ</th>
                    <th>التاجر</th>
                    <th>المبلغ</th>
                    <th>ما يعادله ($)</th>
                    <th>مرتبطة بفاتورة</th>
                    <th>ملاحظات</th>
                    <th class="text-center no-print" style="width: 120px;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td><span class="badge bg-secondary-subtle text-secondary font-monospace">{{ $payment->receipt_number ?? '-' }}</span></td>
                    <td>{{ $payment->date?->format('Y-m-d') }}</td>
                    <td><strong>{{ $payment->buyer_name }}</strong></td>
                    <td class="font-monospace fw-bold text-success">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td>
                    <td class="font-monospace">${{ number_format($payment->amountUSD(), 2) }}</td>
                    <td>
                        @if($payment->sale)
                            فاتورة #{{ $payment->sale->id }} — {{ $payment->sale->season->name ?? '' }}
                        @else
                            <span class="text-muted">دفعة عامة على الحساب</span>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $payment->notes ?? '-' }}</td>
                    <td class="text-center no-print">
                        <div class="btn-group">
                            <a href="{{ route('buyer-payments.receipt', $payment) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="طباعة السند">
                                <i class="fa-solid fa-print"></i>
                            </a>
                            <form action="{{ route('buyer-payments.destroy', $payment) }}" method="POST" onsubmit="return confirm('سيتم حذف السند وإعادة المبلغ إلى رصيد الديون. هل أنت متأكد؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">لا توجد سندات قبض مسجلة ضمن النطاق المحدد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div class="mt-3">
        {{ $payments->links() }}
    </div>
    @endif
</div>

<!-- نافذة تسجيل دفعة جديدة -->
<div class="modal fade" id="newPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('buyer-payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ route('buyer-payments.index') }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-hand-holding-dollar text-success me-2"></i>تسجيل سند قبض جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">اسم التاجر</label>
                            <input type="text" name="buyer_name" list="buyers_list" class="form-control" value="{{ old('buyer_name') }}" placeholder="اكتب أو اختر اسم التاجر" required>
                            <datalist id="buyers_list">
                                @foreach($buyers as $buyer)
                                    <option value="{{ $buyer }}">
                                @endforeach
                            </datalist>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">تاريخ الدفعة</label>
                            <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-success">المبلغ المقبوض</label>
                            <input type="text" dir="ltr" inputmode="decimal" name="amount" class="form-control text-start font-monospace fw-bold" value="{{ old('amount') }}" placeholder="0.00" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">العملة</label>
                            <select name="currency" class="form-select" required>
                                <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>دولار (USD)</option>
                                <option value="TRY" {{ old('currency') === 'TRY' ? 'selected' : '' }}>ليرة تركية (TRY)</option>
                                <option value="SYP" {{ old('currency') === 'SYP' ? 'selected' : '' }}>ليرة سورية (SYP)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">سعر الصرف مقابل الدولار</label>
                            <input type="text" dir="ltr" inputmode="decimal" name="exchange_rate" class="form-control text-start font-monospace" value="{{ old('exchange_rate', 1) }}" placeholder="1">
                            <small class="text-muted">اتركه 1 إذا كانت الدفعة بالدولار.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">ملاحظات</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="مثال: دفعة نقدية عن فواتير موسم القمح">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i> حفظ السند
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
