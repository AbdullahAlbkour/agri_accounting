@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-calendar-days text-success me-2"></i>المواسم الزراعية</h4>
        <p class="text-muted mb-0">إدارة ومتابعة المواسم الزراعية النشطة والمغلقة (المؤرشفة)</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('seasons.archive') }}" class="btn btn-outline-secondary px-3 py-2 rounded-pill fw-bold">
            <i class="fa-solid fa-box-archive me-1"></i> الأرشيف والمقارنات
        </a>
        <a href="{{ route('seasons.create') }}" class="btn btn-success px-4 py-2 rounded-pill fw-bold">
            <i class="fa-solid fa-plus me-1"></i> فتح موسم زراعي جديد
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- فلترة حسب الحالة والنوع -->
<div class="d-flex flex-wrap gap-3 align-items-center mb-3 no-print">
    <div class="btn-group" role="group">
        <a href="{{ route('seasons.index', ['type' => $type]) }}" class="btn btn-sm {{ $status ? 'btn-outline-dark' : 'btn-dark' }}">كل المواسم</a>
        <a href="{{ route('seasons.index', ['status' => 'active', 'type' => $type]) }}" class="btn btn-sm {{ $status === 'active' ? 'btn-success' : 'btn-outline-success' }}">النشطة فقط</a>
        <a href="{{ route('seasons.index', ['status' => 'closed', 'type' => $type]) }}" class="btn btn-sm {{ $status === 'closed' ? 'btn-secondary' : 'btn-outline-secondary' }}">المغلقة (المؤرشفة)</a>
    </div>

    <form action="{{ route('seasons.index') }}" method="GET" class="d-flex gap-2 align-items-center">
        <input type="hidden" name="status" value="{{ $status }}">
        <label class="form-label fw-semibold mb-0">نوع الموسم:</label>
        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">كل الأنواع</option>
            @foreach($seasonTypes as $value => $label)
                <option value="{{ $value }}" {{ $type === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>اسم الموسم</th>
                    <th>النوع</th>
                    <th>المحصول</th>
                    <th>الأرض</th>
                    <th>الحالة</th>
                    <th>المصاريف ($)</th>
                    <th>المبيعات ($)</th>
                    <th>صافي الربح ($)</th>
                    <th style="width: 210px;" class="text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($seasons as $season)
                <tr class="{{ $season->isClosed() ? 'table-light text-muted' : '' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td><strong><a href="{{ route('seasons.show', $season) }}" class="text-decoration-none text-success">{{ $season->name }}</a></strong></td>
                    <td><span class="badge bg-info-subtle text-info px-3 py-1 rounded-pill">{{ $season->typeLabel() }}</span></td>
                    <td>{{ $season->crop->name ?? '-' }}</td>
                    <td>{{ $season->field->name ?? '-' }}</td>
                    <td>
                        @if($season->isClosed())
                            <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill">
                                <i class="fa-solid fa-lock me-1"></i> مغلق / مؤرشف
                            </span>
                        @else
                            <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">نشط</span>
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

                            @if($season->isClosed())
                                <form action="{{ route('seasons.reopen', $season) }}" method="POST" class="d-inline" onsubmit="return confirm('إعادة فتح الموسم تسمح بتعديل مبيعاته ومصاريفه مجدداً. هل تريد المتابعة؟')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="إعادة فتح الموسم">
                                        <i class="fa-solid fa-lock-open"></i>
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('seasons.edit', $season) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('seasons.close', $season) }}" method="POST" class="d-inline" onsubmit="return confirm('سيتم إغلاق الموسم وأرشفته، ولن يمكن تعديل مبيعاته أو مصاريفه. هل أنت متأكد؟')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-dark" title="إغلاق وأرشفة الموسم">
                                        <i class="fa-solid fa-lock"></i>
                                    </button>
                                </form>
                                <form action="{{ route('seasons.destroy', $season) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الموسم؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">لا توجد مواسم زراعية مطابقة. اضغط على "فتح موسم زراعي جديد" للبدء.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($seasons->hasPages())
    <div class="card-footer bg-white py-3">
        {{ $seasons->links() }}
    </div>
    @endif
</div>
@endsection
