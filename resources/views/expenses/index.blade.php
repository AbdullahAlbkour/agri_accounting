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
                    <td><strong>{{ $expense->season->name ?? '-' }}</strong></td>
                    <td><span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill">{{ $expense->category->name ?? 'عام' }}</span></td>
                    <td class="font-monospace fw-bold text-danger">{{ number_format($expense->amount, 2) }} {{ $expense->currency }}</td>
                    <td>{{ $expense->exchange_rate ?? 1 }}</td>
                    <td class="font-monospace fw-bold text-dark">
                        ${{ number_format($expense->currency === 'USD' ? $expense->amount : ($expense->amount / ($expense->exchange_rate ?: 1)), 2) }}
                    </td>
                    <td>{{ $expense->notes ?? '-' }}</td>
                    <td class="text-center">
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
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-4 text-muted">لا توجد مصاريف مسجلة حتى الآن.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection