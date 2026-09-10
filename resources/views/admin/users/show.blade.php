@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-user text-success me-2"></i>بيانات الحساب: {{ $user->name }}</h4>
        <p class="text-muted mb-0">
            <span class="font-monospace" dir="ltr">{{ $user->username }}</span> ·
            <span dir="ltr">{{ $user->email }}</span> ·
            {{ $user->roleLabel() }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary px-4 rounded-pill fw-bold">
            <i class="fa-solid fa-pen-to-square me-1"></i> تعديل
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-light px-4 rounded-pill">رجوع</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card stat-card bg-success text-white p-3">
            <h6>إجمالي المبيعات</h6>
            <h4>${{ number_format($summary['sales_usd'], 2) }}</h4>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card bg-danger text-white p-3">
            <h6>إجمالي المصاريف</h6>
            <h4>${{ number_format($summary['expenses_usd'], 2) }}</h4>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card {{ $summary['net_profit_usd'] >= 0 ? 'bg-primary' : 'bg-warning text-dark' }} text-white p-3">
            <h6>صافي الربح</h6>
            <h4>${{ number_format($summary['net_profit_usd'], 2) }}</h4>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card bg-secondary text-white p-3">
            <h6>المحاصيل / الأراضي</h6>
            <h4>{{ $summary['crops'] }} / {{ $summary['fields'] }}</h4>
        </div>
    </div>
</div>

<div class="card stat-card p-4">
    <h5 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-calendar-days me-2"></i>مواسم هذا الحساب ({{ $seasons->count() }})</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>الموسم</th>
                    <th>النوع</th>
                    <th>المحصول</th>
                    <th>الأرض</th>
                    <th>الحالة</th>
                    <th>المصاريف ($)</th>
                    <th>المبيعات ($)</th>
                    <th>صافي الربح ($)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($seasons as $season)
                <tr>
                    <td><strong>{{ $season->name }}</strong></td>
                    <td><span class="badge bg-light text-dark border">{{ $season->typeLabel() }}</span></td>
                    <td>{{ $season->crop->name ?? '-' }}</td>
                    <td>{{ $season->field->name ?? '-' }}</td>
                    <td>
                        @if($season->isClosed())
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3"><i class="fa-solid fa-lock me-1"></i> مؤرشف</span>
                        @else
                            <span class="badge bg-success-subtle text-success rounded-pill px-3">نشط</span>
                        @endif
                    </td>
                    <td class="font-monospace text-danger">${{ number_format($season->totalExpensesUSD(), 2) }}</td>
                    <td class="font-monospace text-success">${{ number_format($season->totalSalesUSD(), 2) }}</td>
                    <td class="font-monospace fw-bold {{ $season->netProfitUSD() >= 0 ? 'text-success' : 'text-danger' }}">
                        ${{ number_format($season->netProfitUSD(), 2) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">لا توجد مواسم مسجّلة لهذا الحساب.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
