@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-bell text-warning me-2"></i>التنبيهات والإشعارات الذكية</h4>
        <p class="text-muted mb-0">متابعة تلقائية لديون التجار المتأخرة وسقف مصاريف المواسم والمحاصيل</p>
    </div>
    <span class="badge {{ $alerts->isEmpty() ? 'bg-success' : 'bg-danger' }} rounded-pill px-3 py-2 fs-6">
        {{ $alerts->count() }} تنبيه
    </span>
</div>

<!-- الحدود المعتمدة -->
<div class="card stat-card p-3 mb-4 bg-light border">
    <div class="row g-3 text-center small">
        <div class="col-md-3 col-6">
            <span class="text-muted d-block">حد دين التاجر</span>
            <strong class="font-monospace">${{ number_format($thresholds['debt_threshold_usd'], 2) }}</strong>
        </div>
        <div class="col-md-3 col-6">
            <span class="text-muted d-block">مدة التأخر في السداد</span>
            <strong class="font-monospace">{{ $thresholds['debt_idle_days'] }} يوم</strong>
        </div>
        <div class="col-md-3 col-6">
            <span class="text-muted d-block">سقف المصاريف من الإيراد</span>
            <strong class="font-monospace">{{ round($thresholds['expense_ratio'] * 100) }}%</strong>
        </div>
        <div class="col-md-3 col-6">
            <span class="text-muted d-block">موسم بلا إيرادات</span>
            <strong class="font-monospace">{{ $thresholds['season_idle_days'] }} يوم</strong>
        </div>
    </div>
    <p class="text-muted small mb-0 mt-2 text-center">
        <i class="fa-solid fa-circle-info me-1"></i>
        يمكن ضبط هذه الحدود من ملف <code dir="ltr">.env</code>
        (<code dir="ltr">ALERT_DEBT_THRESHOLD_USD</code>، <code dir="ltr">ALERT_DEBT_IDLE_DAYS</code>،
        <code dir="ltr">ALERT_EXPENSE_RATIO</code>، <code dir="ltr">ALERT_SEASON_IDLE_DAYS</code>).
    </p>
</div>

@if($alerts->isEmpty())
    <div class="card stat-card p-5 text-center">
        <i class="fa-solid fa-circle-check fa-3x text-success mb-3"></i>
        <h5 class="fw-bold mb-1">لا توجد تنبيهات حالياً</h5>
        <p class="text-muted mb-0">لا ديون تجاوزت الحد، ولا مواسم تجاوزت سقف المصاريف المحدد.</p>
    </div>
@else
    @foreach($groups as $key => $group)
        @if($group['items']->isNotEmpty())
        <div class="card stat-card p-4 mb-4">
            <h5 class="fw-bold text-{{ $group['color'] }} mb-3">
                <i class="fa-solid {{ $group['icon'] }} me-2"></i>{{ $group['title'] }}
                <span class="badge bg-{{ $group['color'] }}-subtle text-{{ $group['color'] }} rounded-pill">{{ $group['items']->count() }}</span>
            </h5>

            <div class="list-group list-group-flush">
                @foreach($group['items'] as $alert)
                <div class="list-group-item px-0 py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div class="d-flex gap-3">
                            <span class="badge bg-{{ $alert['level'] }}-subtle text-{{ $alert['level'] }} rounded-circle p-3">
                                <i class="fa-solid {{ $alert['icon'] }}"></i>
                            </span>
                            <div>
                                <h6 class="fw-bold mb-1">{{ $alert['title'] }}</h6>
                                <p class="text-muted mb-0 small">{{ $alert['message'] }}</p>
                            </div>
                        </div>
                        <a href="{{ $alert['url'] }}" class="btn btn-sm btn-outline-{{ $alert['level'] }} rounded-pill px-3 no-print">
                            {{ $alert['action'] }} <i class="fa-solid fa-arrow-left ms-1"></i>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach
@endif
@endsection
