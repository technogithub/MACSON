<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - MACSON NAC</title>
    
    <!-- Bootstrap 5.3 CSS & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts: Outfit (Brand & Headings) + Inter (Body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --macson-bg: #090d16;
            --macson-card-bg: rgba(17, 24, 39, 0.75);
            --macson-border: rgba(255, 255, 255, 0.08);
            --macson-border-hover: rgba(56, 189, 248, 0.3);
            --macson-cyan: #38bdf8;
            --macson-indigo: #6366f1;
            --macson-emerald: #10b981;
            --macson-rose: #f43f5e;
            --macson-amber: #f59e0b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--macson-bg);
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(56, 189, 248, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(99, 102, 241, 0.04) 0%, transparent 40%);
            background-attachment: fixed;
            color: #f1f5f9;
            min-height: 100vh;
        }

        h1, h2, h3, h4, h5, h6, .font-outfit {
            font-family: 'Outfit', sans-serif;
        }

        /* ===== NAVBAR ===== */
        .macson-navbar {
            background: rgba(9, 13, 22, 0.85) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--macson-border) !important;
        }

        .brand-text {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.35rem;
        }

        .status-pulse {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-emerald 2s infinite;
        }

        @keyframes pulse-emerald {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* ===== SIDEBAR ===== */
        .macson-sidebar {
            width: 260px;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border-right: 1px solid var(--macson-border);
            min-height: calc(100vh - 65px);
        }

        .nav-item-macson {
            color: #94a3b8;
            border-radius: 12px;
            padding: 11px 18px;
            font-weight: 500;
            font-size: 0.92rem;
            transition: all 0.2s ease;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid transparent;
        }

        .nav-item-macson:hover {
            color: #f8fafc;
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.06);
            transform: translateX(3px);
        }

        .nav-item-macson.active {
            color: #38bdf8;
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.12) 0%, rgba(99, 102, 241, 0.08) 100%);
            border-color: rgba(56, 189, 248, 0.25);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(56, 189, 248, 0.08);
        }

        .nav-item-macson i {
            font-size: 1.1rem;
            width: 22px;
            text-align: center;
            transition: transform 0.2s ease;
        }

        .nav-item-macson:hover i {
            transform: scale(1.15);
        }

        /* ===== FUTURISTIC CARDS ===== */
        .macson-card {
            background: var(--macson-card-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--macson-border);
            border-radius: 16px;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .macson-card:hover {
            border-color: var(--macson-border-hover);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
        }

        .macson-stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            transition: transform 0.25s ease;
        }

        .macson-card:hover .macson-stat-icon {
            transform: scale(1.1) rotate(-4deg);
        }

        /* ===== TABLES ===== */
        .macson-table {
            color: #e2e8f0;
            margin-bottom: 0;
        }

        .macson-table th {
            background: rgba(15, 23, 42, 0.8) !important;
            border-bottom: 1px solid var(--macson-border) !important;
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
        }

        .macson-table td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            padding: 14px 16px;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .macson-table tbody tr {
            transition: background-color 0.15s ease;
        }

        .macson-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.02) !important;
        }

        /* Badges */
        .mac-badge {
            background: rgba(56, 189, 248, 0.1);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.25);
            padding: 4px 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .ssid-badge {
            background: rgba(99, 102, 241, 0.1);
            color: #818cf8;
            border: 1px solid rgba(99, 102, 241, 0.25);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 500;
        }

        .vlan-badge {
            background: rgba(16, 185, 129, 0.1);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.25);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
        }

        /* Form Inputs */
        .form-control-dark {
            background: rgba(15, 23, 42, 0.8) !important;
            border: 1px solid var(--macson-border) !important;
            color: #f1f5f9 !important;
            border-radius: 10px;
            padding: 10px 14px;
        }

        .form-control-dark:focus {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15) !important;
        }

        /* Mobile Adjustments */
        @media (max-width: 991.98px) {
            .macson-sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg macson-navbar sticky-top py-2">
        <div class="container-fluid px-3 px-lg-4">
            
            <!-- Mobile Offcanvas Toggle -->
            <button class="btn btn-sm text-light me-2 d-lg-none border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                <i class="fa-solid fa-bars fs-4 text-info"></i>
            </button>

            <!-- Brand Logo -->
            <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="{{ route('dashboard') }}">
                <div class="d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:linear-gradient(135deg,rgba(56,189,248,0.2),rgba(99,102,241,0.2));border:1px solid rgba(56,189,248,0.4);border-radius:10px;">
                    <i class="fa-solid fa-shield-halved text-info fs-5"></i>
                </div>
                <span class="brand-text">MACSON</span>
                <span class="badge bg-dark-subtle text-info border border-info-subtle rounded-pill fs-7 px-2 py-1 ms-1">v1.0</span>
            </a>

            <!-- Right Navbar Items -->
            <div class="d-flex align-items-center gap-3 ms-auto">
                <div class="d-none d-md-flex align-items-center gap-2 px-3 py-1.5 rounded-pill" style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);">
                    <span class="status-pulse"></span>
                    <span class="text-success small fw-semibold">Engine Active</span>
                </div>

                @auth
                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm d-flex align-items-center gap-2 px-2.5 py-1.5 rounded-pill border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background:rgba(255,255,255,0.05);border:1px solid var(--macson-border) !important;">
                        <span style="width:28px;height:28px;background:linear-gradient(135deg,#0ea5e9,#6366f1);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:white;">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="d-none d-md-inline small fw-semibold text-light me-1">{{ auth()->user()->name }}</span>
                        <i class="fa-solid fa-chevron-down text-secondary" style="font-size:10px;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-2" style="background:#0f172a;border:1px solid var(--macson-border) !important;min-width:220px;border-radius:14px;">
                        <li class="px-3 py-2">
                            <div class="text-light fw-semibold small font-outfit">{{ auth()->user()->name }}</div>
                            <div class="text-secondary" style="font-size:11px;">{{ auth()->user()->email }}</div>
                            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0.5 mt-1" style="font-size:10px;">{{ auth()->user()->role }}</span>
                        </li>
                        <li><hr class="dropdown-divider border-secondary my-1"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2 rounded-2 py-2">
                                    <i class="fa-solid fa-right-from-bracket"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Container Layout -->
    <div class="container-fluid px-0">
        <div class="d-flex">
            
            @auth
            <!-- Desktop Sidebar -->
            <aside class="macson-sidebar p-3 d-none d-lg-block">
                <div class="text-uppercase text-secondary fw-bold px-3 mb-2" style="font-size:0.7rem;letter-spacing:1px;">Core Navigation</div>
                <nav class="nav flex-column">
                    <a class="nav-item-macson {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>
                    <a class="nav-item-macson {{ request()->routeIs('devices.*') ? 'active' : '' }}" href="{{ route('devices.index') }}">
                        <i class="fa-solid fa-laptop-code"></i>
                        <span>MAC Devices</span>
                    </a>
                    <a class="nav-item-macson {{ request()->routeIs('ssids.*') ? 'active' : '' }}" href="{{ route('ssids.index') }}">
                        <i class="fa-solid fa-wifi"></i>
                        <span>SSID & VLAN</span>
                    </a>
                    <a class="nav-item-macson {{ request()->routeIs('vouchers.*') ? 'active' : '' }}" href="{{ route('vouchers.index') }}">
                        <i class="fa-solid fa-ticket"></i>
                        <span>UniFi Vouchers</span>
                    </a>
                    <a class="nav-item-macson {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Audit Access Logs</span>
                    </a>
                </nav>
            </aside>

            <!-- Mobile Offcanvas Sidebar -->
            <div class="offcanvas offcanvas-start bg-dark text-light border-end border-secondary" tabindex="-1" id="mobileSidebar" style="width:280px;background:#0f172a !important;">
                <div class="offcanvas-header border-bottom border-secondary">
                    <h5 class="offcanvas-title font-outfit fw-bold brand-text">MACSON Menu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body p-3">
                    <nav class="nav flex-column">
                        <a class="nav-item-macson {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="fa-solid fa-chart-pie"></i>
                            <span>Dashboard</span>
                        </a>
                        <a class="nav-item-macson {{ request()->routeIs('devices.*') ? 'active' : '' }}" href="{{ route('devices.index') }}">
                            <i class="fa-solid fa-laptop-code"></i>
                            <span>MAC Devices</span>
                        </a>
                        <a class="nav-item-macson {{ request()->routeIs('ssids.*') ? 'active' : '' }}" href="{{ route('ssids.index') }}">
                            <i class="fa-solid fa-wifi"></i>
                            <span>SSID & VLAN</span>
                        </a>
                        <a class="nav-item-macson {{ request()->routeIs('vouchers.*') ? 'active' : '' }}" href="{{ route('vouchers.index') }}">
                            <i class="fa-solid fa-ticket"></i>
                            <span>UniFi Vouchers</span>
                        </a>
                        <a class="nav-item-macson {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}">
                            <i class="fa-solid fa-list-check"></i>
                            <span>Audit Access Logs</span>
                        </a>
                    </nav>
                </div>
            </div>
            @endauth

            <!-- Main Page Content View -->
            <main class="flex-grow-1 p-3 p-lg-4" style="min-width: 0;">
                
                {{-- Flash Notifications --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 mb-4" role="alert" style="background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.3) !important;color:#34d399;border-radius:12px;">
                        <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 mb-4" role="alert" style="background:rgba(244,63,94,0.12);border:1px solid rgba(244,63,94,0.3) !important;color:#fb7185;border-radius:12px;">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>

        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
