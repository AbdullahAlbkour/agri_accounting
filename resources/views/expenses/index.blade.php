@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-receipt text-danger me-2"></i>سجل المصاريف</h4>
        <p class="text-muted mb-0">توثيق ومتابعة كافة المصاريف والتكاليف التشغيلية للمواسم</p>
    </div>
    <a href="{{ route('expenses.create') }}" class="btn btn-danger px-4 py-2 rounded-pill fw-bold">
        <i class="fa-solid fa-plus me-1"></i> إضافة مصروف جديد
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
    <form action="{{ route('expenses.index') }}" method="GET" class="row g-2 align-items-end">
        <div class="col-md-5">
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
            <label class="form-label fw-semibold mb-1">فلترة حسب نوع الموسم</label>
            <select name="season_type" class="form-select">
                <option value="">-- كل الأنواع (صيفي / شتوي / خريفي / ربيعي) --</option>
                @foreach($seasonTypes as $value => $label)
                    <option value="{{ $value }}" {{ request('season_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-dark w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> عرض</button>
            <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary" title="إلغاء الفلترة"><i class="fa-solid fa-rotate-left"></i></a>
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
                    <th>بند الصرف</th>
                    <th>المبلغ المدفوع</th>
                    <th>سعر الصرف</th>
                    <th>المعادل بالدولار</th>
                    <th>ملاحظات</th>
                    <th style="width: 140px;" class="text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $expense->date }}</td>
                    <td>
                        <strong>{{ $expense->season->name ?? '-' }}</strong>
                        @if($expense->season?->type)
                            <span class="badge bg-info-subtle text-info ms-1">{{ $expense->season->typeLabel() }}</span>
                        @endif
                        @if($expense->season && $expense->season->isClosed())
                            <span class="badge bg-secondary-subtle text-secondary ms-1" title="موسم مغلق - الحركات مجمّدة"><i class="fa-solid fa-lock"></i> مغلق</span>
                        @endif
                    </td>
                    <td><span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill">{{ $expense->category->name ?? 'عام' }}</span></td>
                    <td class="font-monospace fw-bold text-danger">{{ number_format($expense->amount, 2) }} {{ $expense->currency }}</td>
                    <td>{{ $expense->exchange_rate ?? 1 }}</td>
                    <td class="font-monospace fw-bold text-dark">
                        ${{ number_format($expense->currency === 'USD' ? $expense->amount : ($expense->amount / ($expense->exchange_rate ?: 1)), 2) }}
                    </td>
                    <td>{{ $expense->notes ?? '-' }}</td>
                    <td class="text-center">
                        @if($expense->season && $expense->season->isClosed())
                            <span class="badge bg-light text-secondary border"><i class="fa-solid fa-lock me-1"></i> مؤرشف</span>
                        @else
                        <div class="btn-group">
                            <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المصروف؟')">
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
                <tr><td colspan="9" class="text-center py-4 text-muted">لا توجد مصاريف مسجلة حتى الآن.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($expenses->hasPages())
    <div class="card-footer bg-white py-3">
        {{ $expenses->links() }}
    </div>
    @endif
</div>
@endsection