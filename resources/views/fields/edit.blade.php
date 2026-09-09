@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card stat-card p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>تعديل بيانات الأرض</h5>
            
            <form action="{{ route('fields.update', $field) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label fw-semibold">اسم الأرض أو القطعة</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $field->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">المساحة بالدونم</label>
                    <input type="number" step="0.1" name="area_dunums" class="form-control @error('area_dunums') is-invalid @enderror" value="{{ old('area_dunums', $field->area_dunums) }}">
                    @error('area_dunums')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">نوع الملكية</label>
                    <select name="ownership_type" class="form-select @error('ownership_type') is-invalid @enderror" required>
                        <option value="owned" {{ old('ownership_type', $field->ownership_type) == 'owned' ? 'selected' : '' }}>ملك</option>
                        <option value="rented" {{ old('ownership_type', $field->ownership_type) == 'rented' ? 'selected' : '' }}>إيجار</option>
                        <option value="shared" {{ old('ownership_type', $field->ownership_type) == 'shared' ? 'selected' : '' }}>شراكة / ضمان</option>
                    </select>
                    @error('ownership_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('fields.index') }}" class="btn btn-light px-4">إلغاء</a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection