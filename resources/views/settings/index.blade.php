@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-gears text-success me-2"></i>الإعدادات والنسخ الاحتياطي</h4>
        <p class="text-muted mb-0">حماية بياناتك: أنشئ نسخة احتياطية من قاعدة البيانات واحتفظ بها على جهازك</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card stat-card p-4 h-100">
            <h5 class="fw-bold text-success mb-3"><i class="fa-solid fa-database me-2"></i>نسخة احتياطية من قاعدة البيانات</h5>

            <p class="text-muted">
                بضغطة زر واحدة يتم توليد ملف <strong>.sql</strong> يحتوي على كامل بيانات النظام
                (المحاصيل، الأراضي، المواسم، المصاريف، المبيعات، وسندات القبض) مع جمل إنشاء الجداول،
                ويمكن استعادته لاحقاً عبر phpMyAdmin أو الأمر <code dir="ltr">mysql &lt; backup.sql</code>.
            </p>

            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">محرك قاعدة البيانات</span>
                    <strong class="font-monospace">{{ $driver }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">اسم قاعدة البيانات</span>
                    <strong class="font-monospace" dir="ltr">{{ $databaseName }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">طريقة التوليد</span>
                    <strong>
                        @if($usesMysqldump)
                            <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">mysqldump (الأداة الرسمية)</span>
                        @else
                            <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill">مولّد PHP المدمج</span>
                        @endif
                    </strong>
                </li>
            </ul>

            <a href="{{ route('backup.download') }}" class="btn btn-success btn-lg w-100 fw-bold rounded-pill">
                <i class="fa-solid fa-cloud-arrow-down me-2"></i> تنزيل نسخة احتياطية الآن (.sql)
            </a>

            <p class="text-muted small mt-3 mb-0">
                <i class="fa-solid fa-circle-info me-1"></i>
                يُنصح بأخذ نسخة احتياطية بشكل دوري (أسبوعياً على الأقل) وقبل إغلاق أي موسم زراعي.
            </p>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card stat-card p-4 h-100">
            <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-chart-simple me-2"></i>محتوى قاعدة البيانات</h5>

            <table class="table table-hover align-middle mb-0">
                <tbody>
                    @foreach($stats as $label => $count)
                    <tr>
                        <td class="fw-semibold">{{ $label }}</td>
                        <td class="text-start font-monospace fw-bold">{{ number_format($count) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-12">
        <div class="card stat-card p-4">
            <h5 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-user-shield me-2"></i>حساب المستخدم الحالي</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded border">
                        <span class="text-muted d-block">الاسم</span>
                        <strong>{{ auth()->user()->name ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded border">
                        <span class="text-muted d-block">البريد الإلكتروني</span>
                        <strong dir="ltr">{{ auth()->user()->email ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded border d-flex flex-column justify-content-between">
                        <span class="text-muted d-block mb-2">إنهاء الجلسة</span>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100 fw-bold">
                                <i class="fa-solid fa-right-from-bracket me-1"></i> تسجيل الخروج
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
