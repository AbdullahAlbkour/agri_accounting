@extends('layouts.app')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card bg-primary text-white p-3">
            <h6><i class="fa-solid fa-sack-dollar me-1"></i> إجمالي الإيرادات</h6>
            <h3>${{ number_format($totalSalesUSD, 2) }}</h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card bg-danger text-white p-3">
            <h6><i class="fa-solid fa-money-bill-transfer me-1"></i> إجمالي المصاريف</h6>
            <h3>${{ number_format($totalExpensesUSD, 2) }}</h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card {{ $netProfitUSD >= 0 ? 'bg-success' : 'bg-warning text-dark' }} text-white p-3">
            <h6><i class="fa-solid fa-scale-balanced me-1"></i> صافي الأرباح</h6>
            <h3>${{ number_format($netProfitUSD, 2) }}</h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card bg-secondary text-white p-3">
            <h6><i class="fa-solid fa-clock-rotate-left me-1"></i> ديون مستحقة لك</h6>
            <h3>${{ number_format($totalReceivablesUSD, 2) }}</h3>
        </div>
    </div>
</div>

<div class="card stat-card mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-success"><i class="fa-solid fa-seedling me-2"></i> المواسم الزراعية النشطة</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>الموسم</th>
                    <th>الأرض</th>
                    <th>المحصول</th>
                    <th>تاريخ البدء</th>
                    <th>إجمالي المصاريف ($)</th>
                    <th>إجمالي المبيعات ($)</th>
                    <th>الربح التقديري ($)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activeSeasons as $season)
                <tr>
                    <td><strong>{{ $season->name }}</strong></td>
                    <td>{{ $season->field->name ?? '-' }}</td>
                    <td>{{ $season->crop->name ?? '-' }}</td>
                    <td>{{ $season->start_date }}</td>
                    <td class="text-danger font-monospace">${{ number_format($season->totalExpensesUSD(), 2) }}</td>
                    <td class="text-success font-monospace">${{ number_format($season->totalSalesUSD(), 2) }}</td>
                    <td class="font-monospace fw-bold {{ $season->netProfitUSD() >= 0 ? 'text-success' : 'text-danger' }}">
                        ${{ number_format($season->netProfitUSD(), 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">لا توجد مواسم زراعية مسجلة حتى الآن</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection