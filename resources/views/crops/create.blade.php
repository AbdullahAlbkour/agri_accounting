@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card stat-card p-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-plus-circle text-success me-2"></i>إضافة محصول جديد</h5>
            
            <form action="{{ route('crops.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">اسم المحصول (مثل: قمح، شعير، زيتون، بطاطا)</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="أدخل اسم المحصول" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('crops.index') }}" class="btn btn-light px-4">إلغاء</a>
                    <button type="submit" class="btn btn-success px-4 fw-bold">حفظ المحصول</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection