@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card stat-card p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>تعديل المحصول</h5>
            
            <form action="{{ route('crops.update', $crop) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label fw-semibold">اسم المحصول</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $crop->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('crops.index') }}" class="btn btn-light px-4">إلغاء</a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
