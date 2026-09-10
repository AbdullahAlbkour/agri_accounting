@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-hand-holding-dollar text-success me-2"></i>سجل المبيعات والإنتاج</h4>
        <p class="text-muted mb-0">توثيق تسليم المحاصيل، المقبوضات النقدية، والديون المتبقية</p>
    </div>
    <a href="{{ route('sales.create') }}" class="btn btn-success px-4 py-2 rounded-pill fw-bold">
        <i class="fa-solid fa-plus me-1"></i> تسجيل عملية بيع
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- فلترة حسب الموسم -->
<div class="card stat-card p-3 mb-3 bg-light border no-print">
    <form action="{{ route('sales.index') }}" method="GET" class="row g-2 align-items-end">
        <div class="col-md-8">
            <label class="form-label fw-semibold mb-1">فلترة حسب الموسم الزراعي</label>
            <select name="season_id" class="form-select">
                <option value="">-- كل المواسم --</option>
                @foreach($seasons as $s)
                    <option value="{{ $s->id }}" {{ (string) request('season_id') === (string) $s->id ? 'selected' : '' }}>
                        {{ $s->name }} ({{ $s->crop->name ?? '' }}){{ $s->isClosed() ? ' — مغلق' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-dark w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> عرض</button>
        </div>
    </form>
</div>

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>التاريخ</th>
                    <th>الموسم</th>
                    <th>المشتري / التاجر</th>
                    <th>الكمية</th>
                    <th>سعر الوحدة</th>
                    <th>الإجمالي</th>
                    <th>الواصل (المدفوع)</th>
                    <th>الباقي (دين)</th>
                    <th>الباقي بعد الدفعات ($)</th>
                    <th style="width: 140px;" class="text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $sale->date }}</td>
                    <td>
                        <strong>{{ $sale->season->name ?? '-' }}</strong>
                        @if($sale->season && $sale->season->isClosed())
                            <span class="badge bg-secondary-subtle text-secondary ms-1" title="موسم مغلق - الحركات مجمّدة"><i class="fa-solid fa-lock"></i> مغلق</span>
                        @endif
                    </td>
                    <td>{{ $sale->buyer_name }}</td>
                    <td>{{ $sale->quantity }} {{ $sale->unit }}</td>
                    <td>{{ number_format($sale->unit_price, 2) }} {{ $sale->currency }}</td>
                    <td class="font-monospace fw-bold text-dark">{{ number_format($sale->total_price, 2) }} {{ $sale->currency }}</td>
                    <td class="font-monospace text-success fw-bold">{{ number_format($sale->paid_amount, 2) }} {{ $sale->currency }}</td>
                    <td class="font-monospace fw-bold {{ $sale->remaining_amount > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ number_format($sale->remaining_amount, 2) }} {{ $sale->currency }}
                    </td>
                    <td class="font-monospace fw-bold {{ $sale->netRemainingUSD() > 0 ? 'text-danger' : 'text-success' }}">
                        ${{ number_format($sale->netRemainingUSD(), 2) }}
                    </td>
                    <td class="text-center">
                        @if($sale->season && $sale->season->isClosed())
                            <span class="badge bg-light text-secondary border"><i class="fa-solid fa-lock me-1"></i> مؤرشف</span>
                        @else
                        <div class="btn-group">
                            <a href="{{ route('sales.edit', $sale) }}" class="btn btn-sm btn-outline-primary" title="تعديل الفاتورة">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا السجل؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="11" class="text-center py-4 text-muted">لا توجد عمليات بيع مسجلة حتى الآن.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sales->hasPages())
    <div class="card-footer bg-white py-3">
        {{ $sales->links() }}
    </div>
    @endif
</div>
@endsection