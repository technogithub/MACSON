<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - SANTAFE NAC</title>
    
    <!-- Anti-flicker Theme Init (Runs immediately before DOM render) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('santafe_theme') || 'cyber-blue';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <!-- Favicon: Modern SVG Shield Logo (Cyber Cyan & Indigo) -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%2338bdf8'/%3E%3Cstop offset='100%25' stop-color='%236366f1'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='32' height='32' rx='8' fill='%230f172a'/%3E%3Cpath d='M16 4 L25 8 V16 C25 21.5 21 26.5 16 28 C11 26.5 7 21.5 7 16 V8 Z' fill='url(%23g)'/%3E%3Cpath d='M16 4 V28 C21 26.5 25 21.5 25 16 V8 Z' fill='%23ffffff' opacity='0.25'/%3E%3Ccircle cx='16' cy='15' r='3.5' fill='%23ffffff'/%3E%3C/svg%3E">
    <link rel="alternate icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%230f172a'/%3E%3Cpath d='M16 4 L25 8 V16 C25 21.5 21 26.5 16 28 C11 26.5 7 21.5 7 16 V8 Z' fill='%2338bdf8'/%3E%3C/svg%3E">
    
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
        /* ===== THEMES DEFINITION ===== */
        :root,
        [data-theme="cyber-blue"] {
            --macson-bg: #090d16;
            --macson-card-bg: rgba(17, 24, 39, 0.75);
            --macson-border: rgba(255, 255, 255, 0.08);
            --macson-border-hover: rgba(56, 189, 248, 0.3);
            --macson-primary: #38bdf8;
            --macson-secondary: #6366f1;
            --macson-cyan: #38bdf8;
            --macson-indigo: #6366f1;
            --macson-emerald: #10b981;
            --macson-rose: #f43f5e;
            --macson-amber: #f59e0b;
            --macson-gradient: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%);
            --macson-glow-1: rgba(56, 189, 248, 0.04);
            --macson-glow-2: rgba(99, 102, 241, 0.04);
        }

        [data-theme="emerald-green"] {
            --macson-bg: #08140f;
            --macson-card-bg: rgba(13, 31, 23, 0.75);
            --macson-border: rgba(52, 211, 153, 0.12);
            --macson-border-hover: rgba(16, 185, 129, 0.4);
            --macson-primary: #10b981;
            --macson-secondary: #14b8a6;
            --macson-cyan: #10b981;
            --macson-indigo: #14b8a6;
            --macson-emerald: #10b981;
            --macson-rose: #f43f5e;
            --macson-amber: #f59e0b;
            --macson-gradient: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
            --macson-glow-1: rgba(16, 185, 129, 0.05);
            --macson-glow-2: rgba(20, 184, 166, 0.05);
        }

        [data-theme="crimson-rose"] {
            --macson-bg: #14090e;
            --macson-card-bg: rgba(36, 15, 23, 0.75);
            --macson-border: rgba(244, 63, 94, 0.12);
            --macson-border-hover: rgba(244, 63, 94, 0.4);
            --macson-primary: #f43f5e;
            --macson-secondary: #f59e0b;
            --macson-cyan: #f43f5e;
            --macson-indigo: #f59e0b;
            --macson-emerald: #10b981;
            --macson-rose: #f43f5e;
            --macson-amber: #f59e0b;
            --macson-gradient: linear-gradient(135deg, #f43f5e 0%, #fb923c 100%);
            --macson-glow-1: rgba(244, 63, 94, 0.05);
            --macson-glow-2: rgba(245, 158, 11, 0.05);
        }

        [data-theme="purple-void"] {
            --macson-bg: #0e0a1a;
            --macson-card-bg: rgba(26, 17, 46, 0.75);
            --macson-border: rgba(192, 132, 252, 0.12);
            --macson-border-hover: rgba(192, 132, 252, 0.4);
            --macson-primary: #c084fc;
            --macson-secondary: #f472b6;
            --macson-cyan: #c084fc;
            --macson-indigo: #f472b6;
            --macson-emerald: #10b981;
            --macson-rose: #f43f5e;
            --macson-amber: #f59e0b;
            --macson-gradient: linear-gradient(135deg, #c084fc 0%, #f472b6 100%);
            --macson-glow-1: rgba(192, 132, 252, 0.05);
            --macson-glow-2: rgba(244, 114, 182, 0.05);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--macson-bg);
            background-image: 
                radial-gradient(circle at 15% 15%, var(--macson-glow-1) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, var(--macson-glow-2) 0%, transparent 40%);
            background-attachment: fixed;
            color: #f1f5f9;
            min-height: 100vh;
            transition: background-color 0.3s ease;
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
            background: var(--macson-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.35rem;
        }

        .status-pulse {
            width: 8px;
            height: 8px;
            background-color: var(--macson-emerald);
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
            color: var(--macson-primary);
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--macson-border-hover);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
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
            border-color: var(--macson-primary) !important;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15) !important;
        }

        /* ===== GLOBAL SUBPAGES THEME OVERRIDES ===== */
        /* Pastikan halaman DHCP, Devices, Logs, UniFi Vouchers ikut ganti tema */
        .page-header {
            background: linear-gradient(135deg, var(--macson-card-bg) 0%, rgba(255, 255, 255, 0.03) 50%, var(--macson-card-bg) 100%) !important;
            border-color: var(--macson-border) !important;
        }

        .stat-card,
        .dhcp-card,
        .devices-table-card,
        .vouchers-table-card,
        .modal-content,
        .card:not(.bg-transparent) {
            background: var(--macson-card-bg) !important;
            border-color: var(--macson-border) !important;
            backdrop-filter: blur(12px);
        }

        .dhcp-table thead th,
        .devices-table thead th,
        .table thead th,
        .table-dark thead th {
            background: var(--macson-bg) !important;
            border-color: var(--macson-border) !important;
        }

        .search-input,
        .filter-select,
        .form-select,
        .form-control {
            background-color: var(--macson-bg) !important;
            border-color: var(--macson-border) !important;
            color: #f1f5f9 !important;
        }

        .search-input:focus,
        .filter-select:focus,
        .form-select:focus,
        .form-control:focus {
            border-color: var(--macson-primary) !important;
        }

        /* Nav Pills & Tabs Active State */
        .nav-pills .nav-link.active,
        .nav-tabs .nav-link.active {
            background-color: var(--macson-primary) !important;
            color: #0f172a !important;
            border-color: var(--macson-primary) !important;
            font-weight: 600;
        }

        /* Primary Accent Text & Links */
        .text-info,
        .text-cyan {
            color: var(--macson-primary) !important;
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
                <span class="brand-text">SANTAFE</span>
                <span class="badge bg-dark-subtle text-info border border-info-subtle rounded-pill fs-7 px-2 py-1 ms-1">v1.0</span>
            </a>

            <!-- Right Navbar Items -->
            <div class="d-flex align-items-center gap-3 ms-auto">
                <div class="d-none d-md-flex align-items-center gap-2 px-3 py-1.5 rounded-pill" style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);">
                    <span class="status-pulse"></span>
                    <span class="text-success small fw-semibold">Engine Active</span>
                </div>

                <!-- Theme Switcher Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm d-flex align-items-center gap-1.5 px-2.5 py-1.5 rounded-pill border-0" 
                            type="button" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false" 
                            title="Ganti Tema Warna"
                            style="background:rgba(255,255,255,0.05);border:1px solid var(--macson-border) !important;">
                        <i class="fa-solid fa-palette text-info" id="themeCurrentIcon" style="font-size:13px;"></i>
                        <span class="d-none d-xl-inline small fw-semibold text-light me-1" id="themeCurrentLabel">Cyber Blue</span>
                        <i class="fa-solid fa-chevron-down text-secondary" style="font-size:9px;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-2" 
                        style="background:#0f172a;border:1px solid var(--macson-border) !important;min-width:200px;border-radius:14px;">
                        <li class="px-2 py-1 text-secondary text-uppercase fw-bold" style="font-size:10px;letter-spacing:0.8px;">
                            Pilih Tema Warna
                        </li>
                        <li><hr class="dropdown-divider border-secondary my-1"></li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center justify-content-between rounded-2 py-2 theme-choice-btn" data-set-theme="cyber-blue">
                                <span class="d-flex align-items-center gap-2 small">
                                    <span style="width:14px;height:14px;background:linear-gradient(135deg,#38bdf8,#6366f1);border-radius:4px;display:inline-block;"></span>
                                    <span>Cyber Blue</span>
                                </span>
                                <i class="fa-solid fa-check small text-info theme-check-icon d-none" data-theme-check="cyber-blue"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center justify-content-between rounded-2 py-2 theme-choice-btn" data-set-theme="emerald-green">
                                <span class="d-flex align-items-center gap-2 small">
                                    <span style="width:14px;height:14px;background:linear-gradient(135deg,#10b981,#14b8a6);border-radius:4px;display:inline-block;"></span>
                                    <span>Emerald Shield</span>
                                </span>
                                <i class="fa-solid fa-check small text-success theme-check-icon d-none" data-theme-check="emerald-green"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center justify-content-between rounded-2 py-2 theme-choice-btn" data-set-theme="crimson-rose">
                                <span class="d-flex align-items-center gap-2 small">
                                    <span style="width:14px;height:14px;background:linear-gradient(135deg,#f43f5e,#fb923c);border-radius:4px;display:inline-block;"></span>
                                    <span>Crimson Protocol</span>
                                </span>
                                <i class="fa-solid fa-check small text-danger theme-check-icon d-none" data-theme-check="crimson-rose"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center justify-content-between rounded-2 py-2 theme-choice-btn" data-set-theme="purple-void">
                                <span class="d-flex align-items-center gap-2 small">
                                    <span style="width:14px;height:14px;background:linear-gradient(135deg,#c084fc,#f472b6);border-radius:4px;display:inline-block;"></span>
                                    <span>Purple Void</span>
                                </span>
                                <i class="fa-solid fa-check small text-warning theme-check-icon d-none" data-theme-check="purple-void"></i>
                            </button>
                        </li>
                    </ul>
                </div>

                @auth
                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm d-flex align-items-center gap-2 px-2.5 py-1.5 rounded-pill border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background:rgba(255,255,255,0.05);border:1px solid var(--macson-border) !important;">
                        <span style="width:28px;height:28px;background:var(--macson-gradient);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:white;">
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
                    @if(auth()->user()->isSuperAdmin())
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
                        <a class="nav-item-macson {{ request()->routeIs('dhcp.*') ? 'active' : '' }}" href="{{ route('dhcp.index') }}">
                            <i class="fa-solid fa-network-wired"></i>
                            <span>DHCP Server</span>
                        </a>
                    @endif
                    <a class="nav-item-macson {{ request()->routeIs('vouchers.*') ? 'active' : '' }}" href="{{ route('vouchers.index') }}">
                        <i class="fa-solid fa-ticket"></i>
                        <span>UniFi Vouchers</span>
                    </a>
                    @if(auth()->user()->isSuperAdmin())
                        <a class="nav-item-macson {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}">
                            <i class="fa-solid fa-list-check"></i>
                            <span>Audit Access Logs</span>
                        </a>
                    @endif
                </nav>
            </aside>

            <!-- Mobile Offcanvas Sidebar -->
            <div class="offcanvas offcanvas-start bg-dark text-light border-end border-secondary" tabindex="-1" id="mobileSidebar" style="width:280px;background:#0f172a !important;">
                <div class="offcanvas-header border-bottom border-secondary">
                    <h5 class="offcanvas-title font-outfit fw-bold brand-text">SANTAFE Menu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body p-3">
                    <nav class="nav flex-column">
                        @if(auth()->user()->isSuperAdmin())
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
                            <a class="nav-item-macson {{ request()->routeIs('dhcp.*') ? 'active' : '' }}" href="{{ route('dhcp.index') }}">
                                <i class="fa-solid fa-network-wired"></i>
                                <span>DHCP Server</span>
                            </a>
                        @endif
                        <a class="nav-item-macson {{ request()->routeIs('vouchers.*') ? 'active' : '' }}" href="{{ route('vouchers.index') }}">
                            <i class="fa-solid fa-ticket"></i>
                            <span>UniFi Vouchers</span>
                        </a>
                        @if(auth()->user()->isSuperAdmin())
                            <a class="nav-item-macson {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}">
                                <i class="fa-solid fa-list-check"></i>
                                <span>Audit Access Logs</span>
                            </a>
                        @endif
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
    
    <!-- Multi-Theme Switcher Script (LocalStorage) -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const themeNames = {
                'cyber-blue':    { label: 'Cyber Blue',       iconClass: 'fa-solid fa-palette text-info' },
                'emerald-green': { label: 'Emerald Shield',   iconClass: 'fa-solid fa-shield-heart text-success' },
                'crimson-rose':  { label: 'Crimson Protocol', iconClass: 'fa-solid fa-fire text-danger' },
                'purple-void':   { label: 'Purple Void',      iconClass: 'fa-solid fa-wand-magic-sparkles text-warning' }
            };

            function applyTheme(themeKey) {
                if (!themeNames[themeKey]) themeKey = 'cyber-blue';
                
                // Pasang atribut ke elemen root HTML
                document.documentElement.setAttribute('data-theme', themeKey);
                localStorage.setItem('santafe_theme', themeKey);

                // Update UI Navbar Label & Icon
                const labelEl = document.getElementById('themeCurrentLabel');
                const iconEl  = document.getElementById('themeCurrentIcon');
                if (labelEl) labelEl.textContent = themeNames[themeKey].label;
                if (iconEl) iconEl.className = themeNames[themeKey].iconClass;

                // Update checklist icon di dropdown
                document.querySelectorAll('.theme-check-icon').forEach(el => {
                    if (el.getAttribute('data-theme-check') === themeKey) {
                        el.classList.remove('d-none');
                    } else {
                        el.classList.add('d-none');
                    }
                });
            }

            // Inisialisasi status visual saat load
            const currentTheme = localStorage.getItem('santafe_theme') || 'cyber-blue';
            applyTheme(currentTheme);

            // Pasang event listener ke setiap tombol pilihan tema
            document.querySelectorAll('.theme-choice-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const chosen = this.getAttribute('data-set-theme');
                    applyTheme(chosen);
                });
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>
