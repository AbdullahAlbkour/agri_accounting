@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-calendar-days text-success me-2"></i>المواسم الزراعية</h4>
        <p class="text-muted mb-0">إدارة ومتابعة المواسم الزراعية النشطة والمكتملة</p>
    </div>
    <a href="{{ route('seasons.create') }}" class="btn btn-success px-4 py-2 rounded-pill fw-bold">
        <i class="fa-solid fa-plus me-1"></i> فتح موسم زراعي جديد
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
                    <th style="width: 60px;">#</th>
                    <th>اسم الموسم</th>
                    <th>المحصول</th>
                    <th>الأرض</th>
                    <th>الحالة</th>
                    <th>المصاريف ($)</th>
                    <th>المبيعات ($)</th>
                    <th>صافي الربح ($)</th>
                    <th style="width: 170px;" class="text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($seasons as $season)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong><a href="{{ route('seasons.show', $season) }}" class="text-decoration-none text-success">{{ $season->name }}</a></strong></td>
                    <td>{{ $season->crop->name ?? '-' }}</td>
                    <td>{{ $season->field->name ?? '-' }}</td>
                    <td>
                        @if($season->status === 'active')
                            <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">نشط</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill">مغلق</span>
                        @endif
                    </td>
                    <td class="text-danger font-monospace">${{ number_format($season->totalExpensesUSD(), 2) }}</td>
                    <td class="text-success font-monospace">${{ number_format($season->totalSalesUSD(), 2) }}</td>
                    <td class="font-monospace fw-bold {{ $season->netProfitUSD() >= 0 ? 'text-success' : 'text-danger' }}">
                        ${{ number_format($season->netProfitUSD(), 2) }}
                    </td>
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="{{ route('seasons.show', $season) }}" class="btn btn-sm btn-outline-success" title="عرض التفاصيل">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('seasons.edit', $season) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('seasons.destroy', $season) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الموسم؟')">
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
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">لا توجد مواسم زراعية مسجلة بعد. اضغط على "فتح موسم زراعي جديد" للبدء.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection