<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - MACSON</title>
    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .sidebar {
            min-height: calc(100vh - 65px);
            background: #1e293b;
            border-right: 1px solid #334155;
        }
        .nav-link {
            color: #94a3b8;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .nav-link:hover, .nav-link.active {
            color: #38bdf8;
            background-color: #0f172a;
        }
        .stat-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .card-custom {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
        }
        .table-custom {
            color: #e2e8f0;
        }
        .table-custom th {
            background-color: #0f172a;
            border-color: #334155;
            color: #94a3b8;
            font-weight: 600;
        }
        .table-custom td {
            border-color: #334155;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top border-bottom border-secondary bg-dark shadow-sm py-2">
        <div class="container-fluid px-4">
            <a class="navbar-brand text-info d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                <i class="fa-solid fa-shield-halved fs-4 text-info"></i>
                <span>MACSON</span>
                <span class="badge bg-primary text-white fs-6 fw-normal ms-1">v1.0.0</span>
            </a>
            <div class="d-flex align-items-center gap-3 ms-auto">
                <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill">
                    <i class="fa-solid fa-circle text-success me-1 fs-6"></i> System Operational
                </span>
                <span class="text-secondary">|</span>
                <span class="small text-light"><i class="fa-solid fa-user me-1 text-info"></i> Admin</span>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="fa-solid fa-chart-pie me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('devices.*') ? 'active' : '' }}" href="{{ route('devices.index') }}">
                            <i class="fa-solid fa-laptop me-2"></i> MAC Devices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('ssids.*') ? 'active' : '' }}" href="{{ route('ssids.index') }}">
                            <i class="fa-solid fa-wifi me-2"></i> Multi-SSID & VLANs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}">
                            <i class="fa-solid fa-list-check me-2"></i> RADIUS Audit Logs
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-9 col-lg-10 p-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
