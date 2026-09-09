@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-wheat-awn text-success me-2"></i>إدارة المحاصيل</h4>
        <p class="text-muted mb-0">عرض وإضافة أنواع المحاصيل الزراعية المزروعة</p>
    </div>
    <a href="{{ route('crops.create') }}" class="btn btn-success px-4 py-2 rounded-pill fw-bold">
        <i class="fa-solid fa-plus me-1"></i> إضافة محصول جديد
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
                    <th>اسم المحصول</th>
                    <th>عدد المواسم المرتبطة</th>
                    <th>تاريخ الإضافة</th>
                    <th style="width: 150px;" class="text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($crops as $crop)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong class="text-dark">{{ $crop->name }}</strong></td>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill">
                            {{ $crop->seasons_count ?? 0 }} مواسم
                        </span>
                    </td>
                    <td>{{ $crop->created_at->format('Y-m-d') }}</td>
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="{{ route('crops.edit', $crop) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('crops.destroy', $crop) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المحصول؟')">
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
                    <td colspan="5" class="text-center py-4 text-muted">لا توجد محاصيل مسجلة بعد. اضغط على "إضافة محصول جديد" للبدء.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection