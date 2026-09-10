<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام المحاسبة الزراعية</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Cairo Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-green: #1b5e20;
            --hover-green: #2e7d32;
            --bg-light: #f8fafc;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-light);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: #fff;
            position: fixed;
            top: 0;
            right: 0;
            z-index: 1000;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            padding: 20px 24px;
            font-size: 1.25rem;
            font-weight: 800;
            color: #4ade80;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 16px 12px;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 6px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.2s ease-in-out;
        }

        .sidebar-menu a i {
            font-size: 1.15rem;
            width: 24px;
            text-align: center;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: #fff;
            background-color: var(--hover-green);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
            transform: translateX(-4px);
        }

        /* Main Content Styling */
        .main-wrapper {
            margin-right: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        .top-navbar {
            background-color: #fff;
            padding: 16px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-area {
            padding: 30px;
            flex: 1;
        }

        .stat-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
        }

        @media (max-width: 991.98px) {
            .sidebar {
                right: -100%;
            }
            .sidebar.show {
                right: 0;
            }
            .main-wrapper {
                margin-right: 0;
            }
        }

        @media print {
            .sidebar, .top-navbar, .no-print {
                display: none !important;
            }
            .main-wrapper {
                margin-right: 0 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar Column (Right) -->
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <i class="fa-solid fa-leaf"></i>
            <span>المحاسبة الزراعية</span>
        </a>

        <ul class="sidebar-menu">
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>لوحة التحكم</span>
                </a>
            </li>
            <li>
                <a href="{{ route('alerts.index') }}" class="{{ request()->routeIs('alerts.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-bell"></i>
                    <span>التنبيهات والإشعارات</span>
                    @if(($navbarAlerts ?? collect())->isNotEmpty())
                        <span class="badge bg-danger rounded-pill ms-auto">{{ ($navbarAlerts ?? collect())->count() }}</span>
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ route('seasons.index') }}" class="{{ request()->routeIs('seasons.*') && ! request()->routeIs('seasons.archive') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>المواسم الزراعية</span>
                </a>
            </li>
            <li>
                <a href="{{ route('expenses.index') }}" class="{{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-receipt"></i>
                    <span>سجل المصاريف</span>
                </a>
            </li>
            <li>
                <a href="{{ route('sales.index') }}" class="{{ request()->routeIs('sales.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <span>سجل المبيعات</span>
                </a>
            </li>
            <li>
                <a href="{{ route('fields.index') }}" class="{{ request()->routeIs('fields.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>إدارة الأراضي</span>
                </a>
            </li>
            <li>
                <a href="{{ route('crops.index') }}" class="{{ request()->routeIs('crops.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-wheat-awn"></i>
                    <span>إدارة المحاصيل</span>
                </a>
            </li>
            <li>
                <a href="{{ route('buyer-payments.index') }}" class="{{ request()->routeIs('buyer-payments.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-money-check-dollar"></i>
                    <span>سندات القبض والدفعات</span>
                </a>
            </li>
            <li>
                <a href="{{ route('seasons.archive') }}" class="{{ request()->routeIs('seasons.archive') ? 'active' : '' }}">
                    <i class="fa-solid fa-box-archive"></i>
                    <span>أرشيف المواسم</span>
                </a>
            </li>
            <li>
                <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>التقارير والحسابات</span>
                </a>
            </li>
            <li>
                <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gears"></i>
                    <span>الإعدادات والنسخ الاحتياطي</span>
                </a>
            </li>

            @if(auth()->user()?->isAdmin())
            <li class="mt-3 pt-3 border-top border-secondary border-opacity-25">
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>إدارة المزارعين</span>
                </a>
            </li>
            @endif
        </ul>
    </aside>

    <!-- Main Content Area (Left) -->
    <div class="main-wrapper">
        <header class="top-navbar no-print">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" type="button" onclick="document.querySelector('.sidebar').classList.toggle('show')">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h5 class="mb-0 fw-bold text-secondary">النظام المحاسبي الزراعي</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                @if(auth()->user()?->isAdmin())
                    <span class="badge bg-dark-subtle text-dark border px-3 py-2 rounded-pill d-none d-md-inline">
                        <i class="fa-solid fa-user-shield me-1"></i> وضع مدير النظام: تعرض كل بيانات المزارعين
                    </span>
                @else
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill d-none d-md-inline">
                        <i class="fa-solid fa-shield-halved me-1"></i> بياناتك خاصة بحسابك
                    </span>
                @endif

                @auth
                @php($navbarAlerts = $navbarAlerts ?? collect())
                <div class="dropdown">
                    <button class="btn btn-light border rounded-circle position-relative px-3 py-2" type="button" data-bs-toggle="dropdown" title="التنبيهات والإشعارات">
                        <i class="fa-solid fa-bell {{ $navbarAlerts->isNotEmpty() ? 'text-danger' : 'text-secondary' }}"></i>
                        @if($navbarAlerts->isNotEmpty())
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ $navbarAlerts->count() > 9 ? '9+' : $navbarAlerts->count() }}
                                <span class="visually-hidden">تنبيهات</span>
                            </span>
                        @endif
                    </button>

                    <ul class="dropdown-menu dropdown-menu-start shadow" style="min-width: 340px; max-width: 92vw;">
                        <li class="dropdown-header fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-triangle-exclamation text-warning me-1"></i> التنبيهات الذكية</span>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $navbarAlerts->count() }}</span>
                        </li>
                        <li><hr class="dropdown-divider"></li>

                        @forelse($navbarAlerts->take(5) as $alert)
                        <li>
                            <a class="dropdown-item py-2" href="{{ $alert['url'] }}" style="white-space: normal;">
                                <div class="d-flex gap-2">
                                    <i class="fa-solid {{ $alert['icon'] }} text-{{ $alert['level'] }} mt-1"></i>
                                    <div>
                                        <div class="fw-semibold small">{{ $alert['title'] }}</div>
                                        <div class="text-muted" style="font-size: .78rem;">{{ $alert['message'] }}</div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        @empty
                        <li class="px-3 py-3 text-center text-muted small">
                            <i class="fa-solid fa-circle-check text-success me-1"></i> لا توجد تنبيهات حالياً
                        </li>
                        @endforelse

                        @if($navbarAlerts->count() > 5)
                        <li><hr class="dropdown-divider"></li>
                        <li class="px-3 small text-muted">و {{ $navbarAlerts->count() - 5 }} تنبيهات أخرى...</li>
                        @endif

                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-center fw-semibold" href="{{ route('alerts.index') }}">
                                عرض كل التنبيهات
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="dropdown">
                    <button class="btn btn-light border rounded-pill px-3 dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-circle-user text-success me-1"></i> {{ auth()->user()->name }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-start shadow">
                        <li>
                            <span class="dropdown-item-text small">
                                <span class="badge {{ auth()->user()->isAdmin() ? 'bg-dark' : 'bg-success' }} rounded-pill">
                                    {{ auth()->user()->roleLabel() }}
                                </span>
                            </span>
                        </li>
                        <li><span class="dropdown-item-text small text-muted font-monospace" dir="ltr">{{ '@' . (auth()->user()->username ?? '') }}</span></li>
                        <li><span class="dropdown-item-text small text-muted" dir="ltr">{{ auth()->user()->email }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('settings.index') }}">
                                <i class="fa-solid fa-gears me-1"></i> الإعدادات والنسخ الاحتياطي
                            </a>
                        </li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fa-solid fa-right-from-bracket me-1"></i> تسجيل الخروج
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                @endauth
            </div>
        </header>

        <main class="content-area">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show no-print" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Bootstrap Bundle JS -->
    <!-- نافذة تكبير المرفقات (صور الفواتير والسندات) -->
    <div class="modal fade" id="attachmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold" id="attachmentModalTitle">المرفق</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center bg-light">
                    <img id="attachmentModalImage" src="" alt="المرفق" class="img-fluid rounded" style="max-height: 70vh;">
                </div>
                <div class="modal-footer">
                    <a id="attachmentModalDownload" href="#" download class="btn btn-success fw-bold rounded-pill px-4">
                        <i class="fa-solid fa-download me-1"></i> تحميل المرفق
                    </a>
                    <a id="attachmentModalOpen" href="#" target="_blank" class="btn btn-outline-secondary rounded-pill px-4">
                        فتح في تبويب جديد
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // فتح المرفق مكبّراً داخل النافذة المنبثقة
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-attachment-url]');

            if (!trigger) {
                return;
            }

            event.preventDefault();

            const url = trigger.dataset.attachmentUrl;
            const title = trigger.dataset.attachmentTitle || 'المرفق';

            document.getElementById('attachmentModalImage').src = url;
            document.getElementById('attachmentModalTitle').textContent = title;
            document.getElementById('attachmentModalDownload').href = url;
            document.getElementById('attachmentModalOpen').href = url;

            bootstrap.Modal.getOrCreateInstance(document.getElementById('attachmentModal')).show();
        });
    </script>
    <!-- Chart.js للمخططات البيانية التفاعلية -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    @stack('scripts')
</body>
</html>