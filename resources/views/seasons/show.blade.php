@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-wheat-awn text-success me-2"></i>تفاصيل وتقرير: {{ $season->name }}</h4>
        <p class="text-muted mb-0">المحصول: <strong>{{ $season->crop->name ?? '-' }}</strong> | الأرض: <strong>{{ $season->field->name ?? '-' }}</strong></p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary px-4 rounded-pill no-print">
        <i class="fa-solid fa-print me-1"></i> طباعة التقرير
    </button>
</div>

<!-- بطاقات الملخص المالي للموسم -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card bg-danger text-white p-3">
            <h6>إجمالي مصاريف الموسم</h6>
            <h3>${{ number_format($season->totalExpensesUSD(), 2) }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card bg-success text-white p-3">
            <h6>إجمالي إيرادات الموسم</h6>
            <h3>${{ number_format($season->totalSalesUSD(), 2) }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card {{ $season->netProfitUSD() >= 0 ? 'bg-primary' : 'bg-warning text-dark' }} text-white p-3">
            <h6>صافي الربح / الخسارة</h6>
            <h3>${{ number_format($season->netProfitUSD(), 2) }}</h3>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- جدول المصاريف -->
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white py-3 fw-bold text-danger">
                <i class="fa-solid fa-receipt me-1"></i> مصاريف هذا الموسم
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>البند</th>
                            <th>المبلغ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($season->expenses as $expense)
                        <tr>
                            <td>{{ $expense->date }}</td>
                            <td>{{ $expense->category->name ?? 'عام' }}</td>
                            <td class="font-monospace text-danger">{{ number_format($expense->amount, 2) }} {{ $expense->currency }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-3 text-muted">لا توجد مصاريف مسجلة لهذا الموسم</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- جدول المبيعات -->
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white py-3 fw-bold text-success">
                <i class="fa-solid fa-hand-holding-dollar me-1"></i> مبيعات وإنتاج هذا الموسم
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>المشتري</th>
                            <th>الكمية</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($season->sales as $sale)
                        <tr>
                            <td>{{ $sale->date }}</td>
                            <td>{{ $sale->buyer_name }}</td>
                            <td>{{ $sale->quantity }} {{ $sale->unit }}</td>
                            <td class="font-monospace text-success">{{ number_format($sale->total_price, 2) }} {{ $sale->currency }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-3 text-muted">لا توجد مبيعات مسجلة لهذا الموسم</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection