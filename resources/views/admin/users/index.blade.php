@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-users-gear text-success me-2"></i>إدارة حسابات المزارعين</h4>
        <p class="text-muted mb-0">عرض كافة الحسابات المسجّلة في النظام وبياناتها وإدارتها</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-success px-4 py-2 rounded-pill fw-bold">
        <i class="fa-solid fa-user-plus me-1"></i> إنشاء حساب جديد
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card stat-card bg-primary text-white p-3">
            <h6><i class="fa-solid fa-users me-1"></i> إجمالي الحسابات</h6>
            <h3>{{ $totals['users'] }}</h3>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card bg-success text-white p-3">
            <h6><i class="fa-solid fa-tractor me-1"></i> المزارعون</h6>
            <h3>{{ $totals['farmers'] }}</h3>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card bg-dark text-white p-3">
            <h6><i class="fa-solid fa-user-shield me-1"></i> المدراء</h6>
            <h3>{{ $totals['admins'] }}</h3>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card stat-card bg-secondary text-white p-3">
            <h6><i class="fa-solid fa-user-slash me-1"></i> حسابات موقوفة</h6>
            <h3>{{ $totals['inactive'] }}</h3>
        </div>
    </div>
</div>

<div class="card stat-card p-3 mb-3 bg-light border no-print">
    <form action="{{ route('admin.users.index') }}" method="GET" class="row g-2 align-items-end">
        <div class="col-md-6">
            <label class="form-label fw-semibold mb-1">بحث بالاسم أو اسم المستخدم أو البريد</label>
            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="اكتب للبحث...">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold mb-1">الصلاحية</label>
            <select name="role" class="form-select">
                <option value="">-- الكل --</option>
                <option value="farmer" {{ request('role') === 'farmer' ? 'selected' : '' }}>مزارع</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>مدير النظام</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-dark w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> بحث</button>
        </div>
    </form>
</div>

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>الاسم الكامل</th>
                    <th>اسم المستخدم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الصلاحية</th>
                    <th>الحالة</th>
                    <th>بياناته</th>
                    <th>تاريخ التسجيل</th>
                    <th class="text-center" style="width: 190px;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td><strong>{{ $user->name }}</strong></td>
                    <td><span class="badge bg-light text-dark border font-monospace" dir="ltr">{{ $user->username ?? '-' }}</span></td>
                    <td dir="ltr" class="text-start">{{ $user->email }}</td>
                    <td>
                        @if($user->isAdmin())
                            <span class="badge bg-dark px-3 py-1 rounded-pill"><i class="fa-solid fa-user-shield me-1"></i> مدير النظام</span>
                        @else
                            <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">مزارع</span>
                        @endif
                    </td>
                    <td>
                        @if($user->is_active)
                            <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">نشط</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill">موقوف</span>
                        @endif
                    </td>
                    <td class="small text-muted">
                        {{ $stats[$user->id]['seasons'] }} موسم ·
                        {{ $stats[$user->id]['sales'] }} بيع ·
                        {{ $stats[$user->id]['expenses'] }} مصروف
                    </td>
                    <td class="small text-muted">{{ $user->created_at?->format('Y-m-d') }}</td>
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-success" title="عرض البيانات">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>

                            @unless(auth()->user()->is($user))
                            <form action="{{ route('admin.users.toggle-active', $user) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="{{ $user->is_active ? 'إيقاف الحساب' : 'تفعيل الحساب' }}">
                                    <i class="fa-solid {{ $user->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('سيتم حذف الحساب وكل بياناته (المواسم، المصاريف، المبيعات، السندات). هل أنت متأكد؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف الحساب">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            @endunless
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-4 text-muted">لا توجد حسابات مطابقة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="card-footer bg-white py-3">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
