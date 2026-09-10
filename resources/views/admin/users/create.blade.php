@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card stat-card p-4">
            <h5 class="fw-bold mb-3 text-success"><i class="fa-solid fa-user-plus me-2"></i>إنشاء حساب جديد</h5>

            @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">الاسم الكامل</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">اسم المستخدم (فريد)</label>
                        <input type="text" dir="ltr" name="username" class="form-control text-start" value="{{ old('username') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">البريد الإلكتروني</label>
                        <input type="email" dir="ltr" name="email" class="form-control text-start" value="{{ old('email') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">رقم الهاتف (اختياري)</label>
                        <input type="text" dir="ltr" name="phone" class="form-control text-start" value="{{ old('phone') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">الصلاحية</label>
                        <select name="role" class="form-select" required>
                            <option value="farmer" {{ old('role') === 'admin' ? '' : 'selected' }}>مزارع</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>مدير النظام</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">كلمة المرور</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">تأكيد كلمة المرور</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success px-4 fw-bold rounded-pill">
                        <i class="fa-solid fa-floppy-disk me-1"></i> حفظ الحساب
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light px-4 rounded-pill">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
