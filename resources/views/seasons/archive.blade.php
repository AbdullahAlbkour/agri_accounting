@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-box-archive text-secondary me-2"></i>أرشيف المواسم السابقة</h4>
        <p class="text-muted mb-0">مقارنة إنتاجية المحاصيل والأرباح عبر السنوات للمواسم المغلقة</p>
    </div>
    <div class="d-flex gap-2 no-print">
        <a href="{{ route('seasons.index') }}" class="btn btn-outline-success px-3 rounded-pill fw-bold">
            <i class="fa-solid fa-calendar-days me-1"></i> المواسم الحالية
        </a>
        <button onclick="window.print()" class="btn btn-outline-danger px-3 rounded-pill fw-bold">
            <i class="fa-solid fa-print me-1"></i> طباعة
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- فلاتر الأرشيف -->
<div class="card stat-card p-3 mb-4 bg-light border no-print">
    <form action="{{ route('seasons.archive') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">المحصول</label>
            <select name="crop_id" class="form-select">
                <option value="">-- كل المحاصيل --</option>
                @foreach($crops as $crop)
                    <option value="{{ $crop->id }}" {{ (string) $cropId === (string) $crop->id ? 'selected' : '' }}>{{ $crop->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">السنة</label>
            <select name="year" class="form-select">
                <option value="">-- كل السنوات --</option>
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ (string) $year === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-dark w-100 fw-bold"><i class="fa-solid fa-filter me-1"></i> عرض المقارنة</button>
        </div>
    </form>
</div>

@if($comparison->isEmpty())
    <div class="card stat-card p-5 text-center text-muted">
        <i class="fa-solid fa-box-open fa-2x mb-3"></i>
        <p class="mb-0">لا توجد مواسم مؤرشفة مطابقة. أغلق موسماً من صفحة المواسم ليظهر هنا في الأرشيف.</p>
    </div>
@else

<!-- مخطط مقارنة الأرباح عبر السنوات -->
<div class="card stat-card p-4 mb-4">
    <h5 class="fw-bold text-success mb-1"><i class="fa-solid fa-chart-column me-2"></i>مقارنة الأداء عبر السنوات</h5>
    <p class="text-muted small mb-3">إجمالي الإيرادات والمصاريف وصافي الربح للمواسم المؤرشفة في كل سنة (بالدولار)</p>
    <div style="position: relative; height: 320px;">
        <canvas id="archiveYearlyChart"></canvas>
    </div>
</div>

<!-- جدول المقارنة التفصيلي -->
<div class="card stat-card p-4 mb-4">
    <h5 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-table-list me-2"></i>مقارنة المحاصيل حسب السنة</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>المحصول</th>
                    <th>السنة</th>
                    <th>عدد المواسم</th>
                    <th>الإنتاج (طن)</th>
                    <th>المساحة (دونم)</th>
                    <th>الإنتاجية (طن/دونم)</th>
                    <th>الإيرادات ($)</th>
                    <th>المصاريف ($)</th>
                    <th>صافي الربح ($)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($comparison as $row)
                <tr>
                    <td><strong>{{ $row['crop'] }}</strong></td>
                    <td><span class="badge bg-dark-subtle text-dark px-3 py-1 rounded-pill">{{ $row['year'] }}</span></td>
                    <td>{{ $row['seasons_count'] }}</td>
                    <td class="font-monospace">{{ number_format($row['quantity_tons'], 3) }}</td>
                    <td class="font-monospace">{{ $row['area_dunums'] > 0 ? number_format($row['area_dunums'], 2) : '-' }}</td>
                    <td class="font-monospace fw-bold text-primary">
                        {{ $row['yield_per_dunum'] !== null ? number_format($row['yield_per_dunum'], 3) : '-' }}
                    </td>
                    <td class="font-monospace text-success">${{ number_format($row['total_sales'], 2) }}</td>
                    <td class="font-monospace text-danger">${{ number_format($row['total_expenses'], 2) }}</td>
                    <td class="font-monospace fw-bold {{ $row['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        ${{ number_format($row['net_profit'], 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- قائمة المواسم المؤرشفة -->
<div class="card stat-card p-4">
    <h5 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-lock me-2"></i>المواسم المؤرشفة ({{ $seasons->count() }})</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>الموسم</th>
                    <th>المحصول</th>
                    <th>الأرض</th>
                    <th>من - إلى</th>
                    <th>الإنتاج (طن)</th>
                    <th>الإيرادات ($)</th>
                    <th>المصاريف ($)</th>
                    <th>صافي الربح ($)</th>
                    <th class="text-center no-print" style="width: 130px;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($seasons as $season)
                <tr>
                    <td><strong><a href="{{ route('seasons.show', $season) }}" class="text-decoration-none text-success">{{ $season->name }}</a></strong></td>
                    <td>{{ $season->crop->name ?? '-' }}</td>
                    <td>{{ $season->field->name ?? '-' }}</td>
                    <td class="small text-muted">{{ $season->start_date }} — {{ $season->end_date ?? '...' }}</td>
                    <td class="font-monospace">{{ number_format($season->totalQuantityTons(), 3) }}</td>
                    <td class="font-monospace text-success">${{ number_format($season->totalSalesUSD(), 2) }}</td>
                    <td class="font-monospace text-danger">${{ number_format($season->totalExpensesUSD(), 2) }}</td>
                    <td class="font-monospace fw-bold {{ $season->netProfitUSD() >= 0 ? 'text-success' : 'text-danger' }}">
                        ${{ number_format($season->netProfitUSD(), 2) }}
                    </td>
                    <td class="text-center no-print">
                        <div class="btn-group">
                            <a href="{{ route('seasons.show', $season) }}" class="btn btn-sm btn-outline-success" title="عرض">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <form action="{{ route('seasons.reopen', $season) }}" method="POST" onsubmit="return confirm('إعادة فتح الموسم تسمح بتعديل حركاته مجدداً. هل تريد المتابعة؟')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="إعادة فتح">
                                    <i class="fa-solid fa-lock-open"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        const yearly = @json($yearlyTotals);
        const canvas = document.getElementById('archiveYearlyChart');

        if (!canvas || !yearly.length) {
            return;
        }

        Chart.defaults.font.family = "'Cairo', sans-serif";
        const currency = (v) => '$' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: yearly.map(y => y.year),
                datasets: [
                    { label: 'الإيرادات', data: yearly.map(y => y.total_sales), backgroundColor: 'rgba(46, 125, 50, 0.75)', borderRadius: 6 },
                    { label: 'المصاريف', data: yearly.map(y => y.total_expenses), backgroundColor: 'rgba(211, 47, 47, 0.75)', borderRadius: 6 },
                    {
                        label: 'صافي الربح',
                        data: yearly.map(y => y.net_profit),
                        type: 'line',
                        borderColor: '#1976d2',
                        backgroundColor: '#1976d2',
                        tension: 0.3,
                        pointRadius: 5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true } },
                    tooltip: { callbacks: { label: (ctx) => ctx.dataset.label + ': ' + currency(ctx.parsed.y) } },
                },
                scales: { y: { beginAtZero: true, ticks: { callback: (v) => currency(v) } } },
            },
        });
    })();
</script>
@endpush
