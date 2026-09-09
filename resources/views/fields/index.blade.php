@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-map-location-dot text-success me-2"></i>إدارة الأراضي</h4>
        <p class="text-muted mb-0">سجل الأراضي والقطع الزراعية ومساحاتها</p>
    </div>
    <a href="{{ route('fields.create') }}" class="btn btn-success px-4 py-2 rounded-pill fw-bold">
        <i class="fa-solid fa-plus me-1"></i> إضافة أرض جديدة
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
                    <th style="width: 80px;">#</th>
                    <th>اسم الأرض / القطعة</th>
                    <th>المساحة (دونم)</th>
                    <th>نوع الملكية</th>
                    <th>عدد المواسم</th>
                    <th style="width: 150px;" class="text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fields as $field)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $field->name }}</strong></td>
                    <td>{{ $field->area_dunums ?? '-' }} دونم</td>
                    <td>
                        @if($field->ownership_type === 'owned')
                            <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">ملك</span>
                        @elseif($field->ownership_type === 'rented')
                            <span class="badge bg-warning-subtle text-dark px-3 py-1 rounded-pill">إيجار</span>
                        @else
                            <span class="badge bg-info-subtle text-info px-3 py-1 rounded-pill">ضمان / شراكة</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill">
                            {{ $field->seasons_count ?? 0 }} مواسم
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="{{ route('fields.edit', $field) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('fields.destroy', $field) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الأرض؟')">
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
                    <td colspan="6" class="text-center py-4 text-muted">لا توجد أراضٍ مسجلة بعد. اضغط على "إضافة أرض جديدة" للبدء.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection