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
                    <td class="font-monospace fw-bold text-success">${{ number_format($analytics['total_sales'], 2) }}</td>
                    <td class="font-monospace fw-bold text-danger">${{ number_format($analytics['total_expenses'], 2) }}</td>
                    <td class="font-monospace fw-bold {{ $analytics['net_profit'] >= 0 ? 'text-primary' : 'text-danger' }}">
                        ${{ number_format($analytics['net_profit'], 2) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-3 text-muted">لا توجد مواسم مطابقة للبحث الحالي.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- كشف حساب تاجر / مشتري -->
<div class="card stat-card p-4 mb-4">
    <h5 class="fw-bold text-success mb-3"><i class="fa-solid fa-user-tie me-2"></i>كشف حساب تاجر / مشتري</h5>
    
    <form action="{{ route('reports.index') }}" method="GET" class="row g-3 align-items-end mb-4">
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
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="p-3 bg-light rounded border">
                <span class="text-muted d-block">إجمالي المبيعات</span>
                <h4 class="fw-bold font-monospace text-dark">${{ number_format($buyerTotals['total_price'], 2) }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-light rounded border">
                <span class="text-muted d-block">إجمالي الواصل (المقبوض)</span>
                <h4 class="fw-bold font-monospace text-success">${{ number_format($buyerTotals['paid_amount'], 2) }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-light rounded border">
                <span class="text-muted d-block">المتبقي (ديون مستحقة)</span>
                <h4 class="fw-bold font-monospace text-danger">${{ number_format($buyerTotals['remaining_amount'], 2) }}</h4>
            </div>
        </div>
    </div>

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
                    <th>المتبقي</th>
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
                    <td class="font-monospace fw-bold {{ $sale->remaining_amount > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ number_format($sale->remaining_amount, 2) }} {{ $sale->currency }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-3 text-muted">لا توجد حركات بيع مسجلة لهذا التاجر ضمن النطاق المختار.</td></tr>
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
@endsection