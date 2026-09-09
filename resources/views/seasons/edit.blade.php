@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card stat-card p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>تعديل بيانات الموسم الزراعي</h5>
            
            <form action="{{ route('seasons.update', $season) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">اسم الموسم</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $season->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">المحصول</label>
                        <select name="crop_id" class="form-select @error('crop_id') is-invalid @enderror" required>
                            @foreach($crops as $crop)
                                <option value="{{ $crop->id }}" {{ old('crop_id', $season->crop_id) == $crop->id ? 'selected' : '' }}>{{ $crop->name }}</option>
                            @endforeach
                        </select>
                        @error('crop_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">الأرض / القطعة</label>
                        <select name="field_id" class="form-select @error('field_id') is-invalid @enderror" required>
                            @foreach($fields as $field)
                                <option value="{{ $field->id }}" {{ old('field_id', $season->field_id) == $field->id ? 'selected' : '' }}>{{ $field->name }}</option>
                            @endforeach
                        </select>
                        @error('field_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">تاريخ البداية</label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $season->start_date) }}" required>
                        @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">حالة الموسم</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status', $season->status) == 'active' ? 'selected' : '' }}>نشط (مستمر)</option>
                            <option value="closed" {{ old('status', $season->status) == 'closed' ? 'selected' : '' }}>مغلق (منتهي)</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $season->notes) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('seasons.index') }}" class="btn btn-light px-4">إلغاء</a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection