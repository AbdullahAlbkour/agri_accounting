@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card stat-card p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-plus-circle text-success me-2"></i>إضافة أرض جديدة</h5>
            
            <form action="{{ route('fields.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">اسم الأرض أو القطعة</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="مثال: قطعة الشمال، أرض السهل" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">المساحة بالدونم</label>
                    <input type="number" step="0.1" name="area_dunums" class="form-control @error('area_dunums') is-invalid @enderror" value="{{ old('area_dunums') }}" placeholder="مثال: 15.5">
                    @error('area_dunums')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">نوع الملكية</label>
                    <select name="ownership_type" class="form-select @error('ownership_type') is-invalid @enderror" required>
                        <option value="owned" {{ old('ownership_type') == 'owned' ? 'selected' : '' }}>ملك</option>
                        <option value="rented" {{ old('ownership_type') == 'rented' ? 'selected' : '' }}>إيجار</option>
                        <option value="shared" {{ old('ownership_type') == 'shared' ? 'selected' : '' }}>شراكة / ضمان</option>
                    </select>
                    @error('ownership_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('fields.index') }}" class="btn btn-light px-4">إلغاء</a>
                    <button type="submit" class="btn btn-success px-4 fw-bold">حفظ الأرض</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection