@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card stat-card p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-plus-circle text-success me-2"></i>فتح موسم زراعي جديد</h5>
            
            <form action="{{ route('seasons.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">اسم الموسم</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="مثال: موسم قمح 2026 - الشرقية" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">نوع الموسم</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror">
                            <option value="">-- غير محدد --</option>
                            @foreach(\App\Models\Season::TYPES as $value => $label)
                                <option value="{{ $value }}" {{ old('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">المحصول</label>
                        <select name="crop_id" class="form-select @error('crop_id') is-invalid @enderror" required>
                            <option value="">-- اختر المحصول --</option>
                            @foreach($crops as $crop)
                                <option value="{{ $crop->id }}" {{ old('crop_id') == $crop->id ? 'selected' : '' }}>{{ $crop->name }}</option>
                            @endforeach
                        </select>
                        @error('crop_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">الأرض / القطعة</label>
                        <select name="field_id" class="form-select @error('field_id') is-invalid @enderror" required>
                            <option value="">-- اختر الأرض --</option>
                            @foreach($fields as $field)
                                <option value="{{ $field->id }}" {{ old('field_id') == $field->id ? 'selected' : '' }}>{{ $field->name }} ({{ $field->area_dunums ?? '-' }} دونم)</option>
                            @endforeach
                        </select>
                        @error('field_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">تاريخ البداية</label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', date('Y-m-d')) }}" required>
                        @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">حالة الموسم</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>نشط (مستمر)</option>
                            <option value="closed" {{ old('status') == 'closed' ? 'selected' : '' }}>مغلق (منتهي)</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="أي تفاصيل تخص تكاليف التضمين أو الزراعة...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('seasons.index') }}" class="btn btn-light px-4">إلغاء</a>
                    <button type="submit" class="btn btn-success px-4 fw-bold">حفظ الموسم</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection