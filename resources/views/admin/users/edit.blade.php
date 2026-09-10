@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card stat-card p-4">
            <h5 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-user-pen me-2"></i>تعديل حساب: {{ $user->name }}</h5>

            @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            @if(auth()->user()->is($user))
            <div class="alert alert-light border">
                <i class="fa-solid fa-circle-info me-1"></i>
                هذا حسابك الشخصي: لا يمكنك تغيير صلاحيتك أو إيقاف حسابك بنفسك.
            </div>
            @endif

            <form action="{{ route('admin.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">الاسم الكامل</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">اسم المستخدم (فريد)</label>
                        <input type="text" dir="ltr" name="username" class="form-control text-start" value="{{ old('username', $user->username) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">البريد الإلكتروني</label>
                        <input type="email" dir="ltr" name="email" class="form-control text-start" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">رقم الهاتف</label>
                        <input type="text" dir="ltr" name="phone" class="form-control text-start" value="{{ old('phone', $user->phone) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">الصلاحية</label>
                        <select name="role" class="form-select" {{ auth()->user()->is($user) ? 'disabled' : '' }} required>
                            <option value="farmer" {{ old('role', $user->role) === 'farmer' ? 'selected' : '' }}>مزارع</option>
                            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>مدير النظام</option>
                        </select>
                        @if(auth()->user()->is($user))
                            <input type="hidden" name="role" value="{{ $user->role }}">
                        @endif
                    </div>

                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                                   {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                   {{ auth()->user()->is($user) ? 'disabled' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_active">الحساب مفعّل</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">كلمة مرور جديدة (اختياري)</label>
                        <input type="password" name="password" class="form-control" placeholder="اتركها فارغة للإبقاء على الحالية">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">تأكيد كلمة المرور الجديدة</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4 fw-bold rounded-pill">
                        <i class="fa-solid fa-floppy-disk me-1"></i> حفظ التعديلات
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light px-4 rounded-pill">رجوع</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
