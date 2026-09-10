@extends('layouts.app')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card bg-primary text-white p-3">
            <h6><i class="fa-solid fa-sack-dollar me-1"></i> إجمالي الإيرادات</h6>
            <h3>${{ number_format($totalSalesUSD, 2) }}</h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card bg-danger text-white p-3">
            <h6><i class="fa-solid fa-money-bill-transfer me-1"></i> إجمالي المصاريف</h6>
            <h3>${{ number_format($totalExpensesUSD, 2) }}</h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card {{ $netProfitUSD >= 0 ? 'bg-success' : 'bg-warning text-dark' }} text-white p-3">
            <h6><i class="fa-solid fa-scale-balanced me-1"></i> صافي الأرباح</h6>
            <h3>${{ number_format($netProfitUSD, 2) }}</h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <a href="{{ route('buyer-payments.index') }}" class="text-decoration-none">
            <div class="card stat-card bg-secondary text-white p-3">
                <h6><i class="fa-solid fa-clock-rotate-left me-1"></i> ديون مستحقة لك</h6>
                <h3>${{ number_format($totalReceivablesUSD, 2) }}</h3>
                <small class="opacity-75">بعد خصم سندات القبض</small>
            </div>
        </a>
    </div>
</div>

<!-- التنبيهات والإشعارات الذكية -->
@if($alertsCount > 0)
<div class="card stat-card p-4 mb-4 border-start border-4 border-warning">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-warning mb-0">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>التنبيهات الذكية
            <span class="badge bg-danger rounded-pill">{{ $alertsCount }}</span>
        </h5>
        <a href="{{ route('alerts.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 no-print">
            عرض الكل <i class="fa-solid fa-arrow-left ms-1"></i>
        </a>
    </div>

    <div class="row g-3">
        @foreach($topAlerts as $alert)
        <div class="col-lg-6">
            <a href="{{ $alert['url'] }}" class="text-decoration-none">
                <div class="d-flex gap-3 p-3 rounded border h-100 bg-{{ $alert['level'] }}-subtle">
                    <i class="fa-solid {{ $alert['icon'] }} fa-lg text-{{ $alert['level'] }} mt-1"></i>
                    <div>
                        <div class="fw-bold text-dark">{{ $alert['title'] }}</div>
                        <div class="text-muted small">{{ $alert['message'] }}</div>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- المخططات البيانية التفاعلية -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card stat-card p-4 h-100">
            <h5 class="fw-bold text-success mb-1"><i class="fa-solid fa-chart-column me-2"></i>مقارنة صافي أرباح المحاصيل</h5>
            <p class="text-muted small mb-3">الإيرادات والمصاريف وصافي الربح لكل محصول (بالدولار الأمريكي)</p>

            @if($cropsChart->isEmpty())
                <div class="text-center text-muted py-5">لا توجد بيانات كافية لعرض المخطط بعد.</div>
            @else
                <div style="position: relative; height: 340px;">
                    <canvas id="cropsProfitChart"></canvas>
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card stat-card p-4 h-100">
            <h5 class="fw-bold text-danger mb-1"><i class="fa-solid fa-chart-pie me-2"></i>توزيع المصاريف حسب البنود</h5>
            <p class="text-muted small mb-3">نسبة كل بند من إجمالي المصاريف (بالدولار الأمريكي)</p>

            @if($expensesChart->isEmpty())
                <div class="text-center text-muted py-5">لا توجد مصاريف مسجلة بعد.</div>
            @else
                <div style="position: relative; height: 340px;">
                    <canvas id="expensesBreakdownChart"></canvas>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card stat-card h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-success"><i class="fa-solid fa-seedling me-2"></i> المواسم الزراعية النشطة</h5>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2">
                    {{ $seasonsCount }} نشط / {{ $closedSeasonsCount }} مؤرشف
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>الموسم</th>
                            <th>الأرض</th>
                            <th>المحصول</th>
                            <th>تاريخ البدء</th>
                            <th>المصاريف ($)</th>
                            <th>المبيعات ($)</th>
                            <th>الربح التقديري ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activeSeasons as $season)
                        <tr>
                            <td><strong><a href="{{ route('seasons.show', $season) }}" class="text-decoration-none text-success">{{ $season->name }}</a></strong></td>
                            <td>{{ $season->field->name ?? '-' }}</td>
                            <td>{{ $season->crop->name ?? '-' }}</td>
                            <td>{{ $season->start_date }}</td>
                            <td class="text-danger font-monospace">${{ number_format($season->totalExpensesUSD(), 2) }}</td>
                            <td class="text-success font-monospace">${{ number_format($season->totalSalesUSD(), 2) }}</td>
                            <td class="font-monospace fw-bold {{ $season->netProfitUSD() >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($season->netProfitUSD(), 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">لا توجد مواسم زراعية نشطة حالياً</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card stat-card h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-secondary"><i class="fa-solid fa-user-tie me-2"></i> أعلى الديون على التجار</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>التاجر</th>
                            <th class="text-start">الرصيد المتبقي ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topDebtors as $debtor)
                        <tr>
                            <td>
                                <a href="{{ route('reports.index', ['buyer_name' => $debtor['buyer_name']]) }}" class="text-decoration-none">
                                    {{ $debtor['buyer_name'] }}
                                </a>
                            </td>
                            <td class="text-start font-monospace fw-bold text-danger">${{ number_format($debtor['remaining_usd'], 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-center text-muted py-4">لا توجد ديون مستحقة 🎉</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const cropsData = @json($cropsChart);
        const expensesData = @json($expensesChart);

        const currency = (value) => '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        Chart.defaults.font.family = "'Cairo', sans-serif";

        const cropsCanvas = document.getElementById('cropsProfitChart');
        if (cropsCanvas && cropsData.length) {
            new Chart(cropsCanvas, {
                type: 'bar',
                data: {
                    labels: cropsData.map(c => c.name),
                    datasets: [
                        {
                            label: 'الإيرادات',
                            data: cropsData.map(c => c.sales),
                            backgroundColor: 'rgba(46, 125, 50, 0.75)',
                            borderRadius: 6,
                        },
                        {
                            label: 'المصاريف',
                            data: cropsData.map(c => c.expenses),
                            backgroundColor: 'rgba(211, 47, 47, 0.75)',
                            borderRadius: 6,
                        },
                        {
                            label: 'صافي الربح',
                            data: cropsData.map(c => c.net_profit),
                            backgroundColor: cropsData.map(c => c.net_profit >= 0 ? 'rgba(25, 118, 210, 0.85)' : 'rgba(245, 124, 0, 0.85)'),
                            borderRadius: 6,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true } },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ctx.dataset.label + ': ' + currency(ctx.parsed.y),
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: (value) => currency(value) },
                        },
                    },
                },
            });
        }

        const expensesCanvas = document.getElementById('expensesBreakdownChart');
        if (expensesCanvas && expensesData.length) {
            const palette = [
                '#d32f2f', '#f57c00', '#fbc02d', '#388e3c', '#0288d1',
                '#7b1fa2', '#c2185b', '#00796b', '#5d4037', '#455a64',
            ];

            const total = expensesData.reduce((sum, item) => sum + Number(item.total), 0);

            new Chart(expensesCanvas, {
                type: 'doughnut',
                data: {
                    labels: expensesData.map(e => e.name),
                    datasets: [{
                        data: expensesData.map(e => e.total),
                        backgroundColor: expensesData.map((e, i) => palette[i % palette.length]),
                        borderWidth: 2,
                        borderColor: '#fff',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '58%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10 } },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => {
                                    const value = Number(ctx.parsed);
                                    const percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return ctx.label + ': ' + currency(value) + ' (' + percent + '%)';
                                },
                            },
                        },
                    },
                },
            });
        }
    })();
</script>
@endpush
