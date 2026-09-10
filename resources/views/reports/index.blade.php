@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-chart-line text-success me-2"></i>التقارير المالية والتحليلات الزراعية</h4>
        <p class="text-muted mb-0">تقارير أداء المحاصيل، كشوفات حسابات التجار، وتحليلات المصاريف المجمعة</p>
    </div>
   <div class="d-flex gap-2 no-print">
        <a href="{{ route('reports.export.excel', request()->all()) }}" class="btn btn-outline-success px-3 rounded-pill fw-bold">
            <i class="fa-solid fa-file-excel me-1"></i> تصدير Excel
        </a>
        <button onclick="window.print()" class="btn btn-outline-danger px-3 rounded-pill fw-bold">
            <i class="fa-solid fa-file-pdf me-1"></i> حفظ PDF / طباعة
        </button>
    </div>
</div>

<!-- شريط الفلترة المجمع الذكي -->
<div class="card stat-card p-3 mb-4 bg-light border">
    <form action="{{ route('reports.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-md-9">
            <label class="form-label fw-semibold">فلترة التقارير حسب نوع أو اسم الموسم (مثال: شتوي، صيفي، أو جزء من اسم الموسم)</label>
            <input type="text" name="search_query" class="form-control" value="{{ request('search_query') }}" placeholder="اكتب كلمة مفتاحية مثل: شتوي، صيفي، قمح...">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-dark w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> تجميع وعرض النتائج</button>
        </div>
    </form>
</div>

<!-- تقرير أداء المحاصيل المجمع -->
<div class="card stat-card p-4 mb-4">
    <h5 class="fw-bold text-success mb-3"><i class="fa-solid fa-wheat-awn me-2"></i>تقرير أداء وحسابات المحاصيل (للمواسم المطابقة)</h5>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>اسم المحصول</th>
                    <th>عدد المواسم المطابقة</th>
                    <th>الإنتاج (طن)</th>
                    <th>إجمالي الإيرادات ($)</th>
                    <th>إجمالي المصاريف ($)</th>
                    <th>صافي الربح / الخسارة ($)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cropsAnalytics as $analytics)
                <tr>
                    <td><strong>{{ $analytics['name'] }}</strong></td>
                    <td><span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill">{{ $analytics['seasons_count'] }} مواسم</span></td>
                    <td class="font-monospace">{{ number_format($analytics['quantity_tons'], 3) }}</td>
                    <td class="font-monospace fw-bold text-success">${{ number_format($analytics['total_sales'], 2) }}</td>
                    <td class="font-monospace fw-bold text-danger">${{ number_format($analytics['total_expenses'], 2) }}</td>
                    <td class="font-monospace fw-bold {{ $analytics['net_profit'] >= 0 ? 'text-primary' : 'text-danger' }}">
                        ${{ number_format($analytics['net_profit'], 2) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-3 text-muted">لا توجد مواسم مطابقة للبحث الحالي.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- كشف حساب تاجر / مشتري -->
<div class="card stat-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-success mb-0"><i class="fa-solid fa-user-tie me-2"></i>كشف حساب تاجر / مشتري</h5>
        @if($selectedBuyer)
        <button type="button" class="btn btn-success rounded-pill px-4 fw-bold no-print" data-bs-toggle="modal" data-bs-target="#newPaymentModal">
            <i class="fa-solid fa-hand-holding-dollar me-1"></i> تسجيل دفعة (سند قبض)
        </button>
        @endif
    </div>

    <form action="{{ route('reports.index') }}" method="GET" class="row g-3 align-items-end mb-4 no-print">
        <input type="hidden" name="search_query" value="{{ request('search_query') }}">
        <div class="col-md-6">
            <label class="form-label fw-semibold">اختر اسم التاجر</label>
            <select name="buyer_name" class="form-select" required>
                <option value="">-- اختر التاجر --</option>
                @foreach($buyers as $buyer)
                    <option value="{{ $buyer }}" {{ $selectedBuyer == $buyer ? 'selected' : '' }}>{{ $buyer }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-success w-100 fw-bold">عرض الكشف</button>
        </div>
    </form>

    @if($selectedBuyer)
    <!-- الرصيد الإجمالي للتاجر بالدولار (كامل الفواتير والدفعات) -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="p-3 bg-light rounded border">
                <span class="text-muted d-block">إجمالي المبيعات ($)</span>
                <h4 class="fw-bold font-monospace text-dark">${{ number_format($buyerSummary['total_sales_usd'], 2) }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 bg-light rounded border">
                <span class="text-muted d-block">الواصل عند البيع ($)</span>
                <h4 class="fw-bold font-monospace text-success">${{ number_format($buyerSummary['paid_at_sale_usd'], 2) }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 bg-light rounded border">
                <span class="text-muted d-block">سندات القبض اللاحقة ($)</span>
                <h4 class="fw-bold font-monospace text-primary">${{ number_format($buyerSummary['payments_usd'], 2) }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 bg-light rounded border">
                <span class="text-muted d-block">الرصيد المتبقي (الدين) ($)</span>
                <h4 class="fw-bold font-monospace {{ $buyerSummary['remaining_usd'] > 0 ? 'text-danger' : 'text-success' }}">
                    ${{ number_format($buyerSummary['remaining_usd'], 2) }}
                </h4>
                @if($buyerSummary['credit_usd'] > 0)
                    <span class="badge bg-info-subtle text-info">رصيد دائن للتاجر: ${{ number_format($buyerSummary['credit_usd'], 2) }}</span>
                @endif
            </div>
        </div>
    </div>

    <p class="text-muted small">
        <i class="fa-solid fa-circle-info me-1"></i>
        الأرصدة أعلاه محسوبة على كامل حساب التاجر بالدولار بعد خصم كل سندات القبض، أما الجداول أدناه فتعرض الحركات
        @if($searchQuery) المطابقة للبحث الحالي @else كاملة @endif بعملة كل فاتورة.
    </p>

    <h6 class="fw-bold text-secondary mt-4 mb-2"><i class="fa-solid fa-file-invoice me-1"></i> فواتير البيع</h6>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>التاريخ</th>
                    <th>الموسم</th>
                    <th>الكمية</th>
                    <th>سعر الوحدة</th>
                    <th>الإجمالي</th>
                    <th>الواصل</th>
                    <th>المتبقي بالفاتورة</th>
                    <th>المتبقي بعد الدفعات ($)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($buyerSales as $sale)
                <tr>
                    <td>{{ $sale->date }}</td>
                    <td><strong>{{ $sale->season->name ?? '-' }}</strong></td>
                    <td>{{ $sale->quantity }} {{ $sale->unit }}</td>
                    <td>{{ number_format($sale->unit_price, 2) }} {{ $sale->currency }}</td>
                    <td class="font-monospace fw-bold">{{ number_format($sale->total_price, 2) }} {{ $sale->currency }}</td>
                    <td class="font-monospace text-success">{{ number_format($sale->paid_amount, 2) }} {{ $sale->currency }}</td>
                    <td class="font-monospace {{ $sale->remaining_amount > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ number_format($sale->remaining_amount, 2) }} {{ $sale->currency }}
                    </td>
                    <td class="font-monospace fw-bold {{ $sale->netRemainingUSD() > 0 ? 'text-danger' : 'text-success' }}">
                        ${{ number_format($sale->netRemainingUSD(), 2) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-3 text-muted">لا توجد حركات بيع مسجلة لهذا التاجر ضمن النطاق المختار.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h6 class="fw-bold text-secondary mt-4 mb-2"><i class="fa-solid fa-receipt me-1"></i> سندات القبض (الدفعات اللاحقة)</h6>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>رقم السند</th>
                    <th>التاريخ</th>
                    <th>المبلغ</th>
                    <th>ما يعادله ($)</th>
                    <th>مرتبطة بفاتورة</th>
                    <th>ملاحظات</th>
                    <th class="text-center no-print" style="width: 120px;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($buyerPayments as $payment)
                <tr>
                    <td><span class="badge bg-secondary-subtle text-secondary font-monospace">{{ $payment->receipt_number ?? '-' }}</span></td>
                    <td>{{ $payment->date?->format('Y-m-d') }}</td>
                    <td class="font-monospace fw-bold text-success">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td>
                    <td class="font-monospace">${{ number_format($payment->amountUSD(), 2) }}</td>
                    <td>
                        @if($payment->sale)
                            فاتورة #{{ $payment->sale->id }}
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
                                <input type="hidden" name="redirect_to" value="{{ route('reports.index', request()->all()) }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-3 text-muted">لا توجد دفعات لاحقة مسجلة لهذا التاجر.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</div>

<!-- تقرير المصاريف حسب التصنيف -->
<div class="card stat-card p-4">
    <h5 class="fw-bold text-danger mb-3"><i class="fa-solid fa-receipt me-2"></i>تقرير المصاريف حسب البند / التصنيف</h5>
    
    <form action="{{ route('reports.index') }}" method="GET" class="row g-3 align-items-end mb-4">
        <input type="hidden" name="search_query" value="{{ request('search_query') }}">
        <div class="col-md-6">
            <label class="form-label fw-semibold">اختر بند المصروف</label>
            <select name="expense_category_id" class="form-select" required>
                <option value="">-- اختر البند --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $selectedCategory == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-danger w-100 fw-bold">عرض التقرير</button>
        </div>
    </form>

    @if($selectedCategory)
    <div class="mb-3 p-3 bg-light rounded border">
        <span class="text-muted">إجمالي المصاريف لهذا البند ضمن المواسم المطابقة (محولة إلى الدولار):</span>
        <h4 class="fw-bold font-monospace text-danger">${{ number_format($totalCategoryExpenseUSD, 2) }}</h4>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>التاريخ</th>
                    <th>الموسم</th>
                    <th>بند الصرف</th>
                    <th>المبلغ المدفوع</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categoryExpenses as $exp)
                <tr>
                    <td>{{ $exp->date }}</td>
                    <td><strong>{{ $exp->season->name ?? '-' }}</strong></td>
                    <td><span class="badge bg-danger-subtle text-danger">{{ $exp->category->name ?? '-' }}</span></td>
                    <td class="font-monospace fw-bold text-danger">{{ number_format($exp->amount, 2) }} {{ $exp->currency }}</td>
                    <td>{{ $exp->notes ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-3 text-muted">لا توجد مصاريف مسجلة تحت هذا البند ضمن المواسم المطابقة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</div>

<!-- كشف أرصدة كل التجار -->
<div class="card stat-card p-4 mt-4">
    <h5 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-scale-balanced me-2"></i>أرصدة كل التجار بعد خصم سندات القبض (بالدولار)</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>التاجر</th>
                    <th>إجمالي المبيعات</th>
                    <th>إجمالي المقبوض</th>
                    <th>الرصيد المتبقي (الدين)</th>
                    <th class="text-center no-print">كشف الحساب</th>
                </tr>
            </thead>
            <tbody>
                @forelse($buyersBalances as $row)
                <tr>
                    <td><strong>{{ $row['buyer_name'] }}</strong></td>
                    <td class="font-monospace">${{ number_format($row['total_sales_usd'], 2) }}</td>
                    <td class="font-monospace text-success">${{ number_format($row['total_collected_usd'], 2) }}</td>
                    <td class="font-monospace fw-bold {{ $row['remaining_usd'] > 0 ? 'text-danger' : 'text-success' }}">
                        ${{ number_format($row['remaining_usd'], 2) }}
                    </td>
                    <td class="text-center no-print">
                        <a href="{{ route('reports.index', ['buyer_name' => $row['buyer_name']]) }}" class="btn btn-sm btn-outline-success">عرض</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-3 text-muted">لا توجد حسابات تجار بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($selectedBuyer)
<!-- نافذة تسجيل دفعة جديدة للتاجر المحدد -->
<div class="modal fade" id="newPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('buyer-payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="buyer_name" value="{{ $selectedBuyer }}">
                <input type="hidden" name="redirect_to" value="{{ route('reports.index', request()->all()) }}">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-hand-holding-dollar text-success me-2"></i>
                        تسجيل دفعة من التاجر: {{ $selectedBuyer }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-light border d-flex justify-content-between align-items-center">
                        <span>الرصيد المتبقي حالياً على التاجر</span>
                        <strong class="font-monospace text-danger fs-5">${{ number_format($buyerSummary['remaining_usd'], 2) }}</strong>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">تاريخ الدفعة</label>
                            <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">خصم من فاتورة محددة (اختياري)</label>
                            <select name="sale_id" class="form-select">
                                <option value="">-- دفعة عامة على الحساب --</option>
                                @foreach($buyerOpenSales as $openSale)
                                    <option value="{{ $openSale->id }}">
                                        فاتورة #{{ $openSale->id }} — {{ $openSale->season->name ?? '' }} — متبقي ${{ number_format($openSale->netRemainingUSD(), 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-success">المبلغ المقبوض</label>
                            <input type="text" dir="ltr" inputmode="decimal" name="amount" class="form-control text-start font-monospace fw-bold" value="{{ old('amount') }}" placeholder="0.00" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">العملة</label>
                            <select name="currency" class="form-select" required>
                                <option value="USD">دولار (USD)</option>
                                <option value="TRY">ليرة تركية (TRY)</option>
                                <option value="SYP">ليرة سورية (SYP)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">سعر الصرف مقابل الدولار</label>
                            <input type="text" dir="ltr" inputmode="decimal" name="exchange_rate" class="form-control text-start font-monospace" value="{{ old('exchange_rate', 1) }}" placeholder="1">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">ملاحظات</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="مثال: دفعة نقدية مستلمة في المزرعة">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i> حفظ الدفعة وخصمها من الدين
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
