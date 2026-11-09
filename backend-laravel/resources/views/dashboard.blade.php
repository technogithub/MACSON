@extends('layouts.app')

@section('title', 'Network Operations Dashboard')

@section('content')
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1 font-outfit text-light d-flex align-items-center gap-2">
            <i class="fa-solid fa-gauge-high text-info"></i>
            <span>SANTAFE</span>
            <span class="badge bg-dark-subtle text-info border border-info-subtle rounded-pill fs-7 px-2.5 py-1 ms-1">v1.0</span>
        </h3>
        <p class="text-secondary small mb-0">
            Santos Advanced Network Traffic &amp; Access Filtering Engine
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('devices.index') }}" class="btn btn-sm btn-info fw-semibold px-3 py-2 rounded-3 text-dark d-flex align-items-center gap-2" style="background:#38bdf8;border:none;">
            <i class="fa-solid fa-plus"></i> Register Device
        </a>
    </div>
</div>

<!-- Futuristic Metrics Grid -->
<div class="row g-3 mb-4">
    <!-- Active Devices -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="macson-card p-3.5 p-lg-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary small fw-medium mb-1">Active Authorized</div>
                    <h2 class="fw-bold font-outfit text-light mb-0" style="font-size: 2.2rem;">{{ number_format($totalActive) }}</h2>
                    <div class="text-emerald small mt-1" style="color:#10b981;font-size:0.78rem;">
                        <i class="fa-solid fa-circle-check me-1"></i>Radius Authenticated
                    </div>
                </div>
                <div class="macson-stat-icon" style="background:rgba(16,185,129,0.12);color:#34d399;border:1px solid rgba(16,185,129,0.25);">
                    <i class="fa-solid fa-laptop-code"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Inactive / Blocked -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="macson-card p-3.5 p-lg-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary small fw-medium mb-1">Inactive / Blocked</div>
                    <h2 class="fw-bold font-outfit text-light mb-0" style="font-size: 2.2rem;">{{ number_format($totalInactive) }}</h2>
                    <div class="text-rose small mt-1" style="color:#f43f5e;font-size:0.78rem;">
                        <i class="fa-solid fa-ban me-1"></i>Access Restricted
                    </div>
                </div>
                <div class="macson-stat-icon" style="background:rgba(244,63,94,0.12);color:#fb7185;border:1px solid rgba(244,63,94,0.25);">
                    <i class="fa-solid fa-user-slash"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Auth Accepts -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="macson-card p-3.5 p-lg-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary small fw-medium mb-1">Auth Accepts (Today)</div>
                    <h2 class="fw-bold font-outfit text-light mb-0" style="font-size: 2.2rem;">{{ number_format($totalAccept) }}</h2>
                    <div class="text-cyan small mt-1" style="color:#38bdf8;font-size:0.78rem;">
                        <i class="fa-solid fa-shield-check me-1"></i>Successful Requests
                    </div>
                </div>
                <div class="macson-stat-icon" style="background:rgba(56,189,248,0.12);color:#38bdf8;border:1px solid rgba(56,189,248,0.25);">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Auth Rejects -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="macson-card p-3.5 p-lg-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary small fw-medium mb-1">Auth Rejects (Today)</div>
                    <h2 class="fw-bold font-outfit text-light mb-0" style="font-size: 2.2rem;">{{ number_format($totalReject) }}</h2>
                    <div class="text-amber small mt-1" style="color:#f59e0b;font-size:0.78rem;">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>Denied Connection
                    </div>
                </div>
                <div class="macson-stat-icon" style="background:rgba(245,158,11,0.12);color:#fbbf24;border:1px solid rgba(245,158,11,0.25);">
                    <i class="fa-solid fa-shield-xmark"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Server Health Widget (Mini) -->
<div class="macson-card p-3 mb-4" id="server-health-widget">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold font-outfit text-light mb-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-server text-info"></i>
            <span>Server Health</span>
            <span class="badge rounded-pill ms-1" id="widget-live-badge" style="background:rgba(16,185,129,0.15);color:#34d399;border:1px solid rgba(16,185,129,0.3);font-size:0.68rem;">
                <i class="fa-solid fa-circle" style="font-size:7px;animation:pulse-emerald 2s infinite;"></i> LIVE
            </span>
        </h6>
        <a href="{{ route('server-monitor.index') }}" class="small text-info text-decoration-none fw-semibold">
            Detail <i class="fa-solid fa-arrow-right ms-1" style="font-size:11px;"></i>
        </a>
    </div>
    <div class="row g-3">
        <!-- CPU -->
        <div class="col-6 col-md-3">
            <div class="d-flex flex-column gap-1">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-secondary"><i class="fa-solid fa-microchip me-1 text-info"></i>CPU</span>
                    <span class="fw-bold small text-light" id="w-cpu-pct">—%</span>
                </div>
                <div class="progress" style="height:5px;background:rgba(255,255,255,0.07);border-radius:4px;">
                    <div class="progress-bar" id="w-cpu-bar" role="progressbar" style="width:0%;background:var(--macson-primary);border-radius:4px;transition:width 0.6s ease;"></div>
                </div>
            </div>
        </div>
        <!-- RAM -->
        <div class="col-6 col-md-3">
            <div class="d-flex flex-column gap-1">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-secondary"><i class="fa-solid fa-memory me-1 text-warning"></i>RAM</span>
                    <span class="fw-bold small text-light" id="w-ram-pct">—%</span>
                </div>
                <div class="progress" style="height:5px;background:rgba(255,255,255,0.07);border-radius:4px;">
                    <div class="progress-bar" id="w-ram-bar" role="progressbar" style="width:0%;border-radius:4px;transition:width 0.6s ease;"></div>
                </div>
                <span class="text-secondary" style="font-size:0.68rem;" id="w-ram-detail">— / — MB</span>
            </div>
        </div>
        <!-- Disk -->
        <div class="col-6 col-md-3">
            <div class="d-flex flex-column gap-1">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-secondary"><i class="fa-solid fa-hard-drive me-1 text-purple" style="color:#c084fc;"></i>Disk</span>
                    <span class="fw-bold small text-light" id="w-disk-pct">—%</span>
                </div>
                <div class="progress" style="height:5px;background:rgba(255,255,255,0.07);border-radius:4px;">
                    <div class="progress-bar" id="w-disk-bar" role="progressbar" style="width:0%;background:#c084fc;border-radius:4px;transition:width 0.6s ease;"></div>
                </div>
                <span class="text-secondary" id="w-disk-detail" style="font-size:0.68rem;">— / — GB</span>
            </div>
        </div>
        <!-- Network -->
        <div class="col-6 col-md-3">
            <div class="d-flex flex-column gap-1">
                <span class="small text-secondary"><i class="fa-solid fa-network-wired me-1 text-success"></i>Network</span>
                <div class="d-flex flex-column gap-0">
                    <span class="text-light fw-semibold" style="font-size:0.78rem;">↑ <span id="w-net-tx">—</span></span>
                    <span class="text-light fw-semibold" style="font-size:0.78rem;">↓ <span id="w-net-rx">—</span></span>
                </div>
            </div>
        </div>
    </div>
    <!-- Docker Mini Status -->
    <div class="mt-3 pt-2" style="border-top:1px solid rgba(255,255,255,0.05);">
        <div class="d-flex flex-wrap gap-2 align-items-center" id="w-docker-list">
            <span class="text-secondary small"><i class="fa-brands fa-docker me-1"></i>Containers:</span>
            <span class="text-secondary small">Loading...</span>
        </div>
    </div>
</div>

<!-- Graph & Log Activity Row -->
<div class="row g-4">
    <!-- Chart Column -->
    <div class="col-12 col-lg-8">
        <div class="macson-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold font-outfit text-light mb-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chart-line text-info"></i>
                    <span>Hourly Authentication Requests</span>
                </h5>
                <span class="badge bg-dark text-secondary border border-secondary px-2.5 py-1">24-Hour Timeline</span>
            </div>
            <div style="position: relative; height: 260px; width: 100%;">
                <canvas id="authChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity Log Column -->
    <div class="col-12 col-lg-4">
        <div class="macson-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold font-outfit text-light mb-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-info"></i>
                    <span>Live Access Stream</span>
                </h5>
                <a href="{{ route('logs.index') }}" class="small text-info text-decoration-none fw-semibold">View All</a>
            </div>

            <div class="table-responsive">
                <table class="table macson-table align-middle">
                    <thead>
                        <tr>
                            <th>MAC Address</th>
                            <th>SSID</th>
                            <th class="text-end">Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                            <tr>
                                <td>
                                    <span class="mac-badge" style="font-size:0.78rem;">{{ $log->mac_address }}</span>
                                </td>
                                <td>
                                    <span class="ssid-badge" style="font-size:0.75rem;">{{ $log?->ssid ?? 'ALL' }}</span>
                                </td>
                                <td class="text-end">
                                    @if($log->auth_result === 'ACCEPT')
                                        <span class="badge rounded-pill" style="background:rgba(16,185,129,0.15);color:#34d399;border:1px solid rgba(16,185,129,0.3);font-size:0.72rem;">ACCEPT</span>
                                    @else
                                        <span class="badge rounded-pill" style="background:rgba(244,63,94,0.15);color:#fb7185;border:1px solid rgba(244,63,94,0.3);font-size:0.72rem;">REJECT</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-secondary py-4 small">No activity logs recorded today</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // ── Server Health Widget SSE ─────────────────────────────────────────
    (function () {
        const sse = new EventSource("{{ route('server-monitor.stream') }}");

        function colorBar(pct) {
            if (pct >= 85) return '#f43f5e';
            if (pct >= 65) return '#f59e0b';
            return '#10b981';
        }

        sse.onmessage = function (e) {
            const d = JSON.parse(e.data);

            // CPU
            const cpu = d.cpu?.percent ?? 0;
            document.getElementById('w-cpu-pct').textContent = cpu + '%';
            const cpuBar = document.getElementById('w-cpu-bar');
            cpuBar.style.width = cpu + '%';
            cpuBar.style.background = colorBar(cpu);

            // RAM
            const ram = d.ram?.percent ?? 0;
            document.getElementById('w-ram-pct').textContent = ram + '%';
            const ramBar = document.getElementById('w-ram-bar');
            ramBar.style.width = ram + '%';
            ramBar.style.background = colorBar(ram);
            document.getElementById('w-ram-detail').textContent =
                (d.ram?.used_mb ?? '—') + ' / ' + (d.ram?.total_mb ?? '—') + ' MB';

            // Disk
            const disk = d.disk?.percent ?? 0;
            document.getElementById('w-disk-pct').textContent = disk + '%';
            const diskBar = document.getElementById('w-disk-bar');
            diskBar.style.width = disk + '%';
            diskBar.style.background = colorBar(disk);
            document.getElementById('w-disk-detail').textContent =
                (d.disk?.used_gb ?? '—') + ' / ' + (d.disk?.total_gb ?? '—') + ' GB';

            // Network
            document.getElementById('w-net-tx').textContent = d.network?.tx_human ?? '—';
            document.getElementById('w-net-rx').textContent = d.network?.rx_human ?? '—';

            // Docker
            const dockerEl = document.getElementById('w-docker-list');
            if (d.docker && d.docker.length > 0) {
                let html = '<span class="text-secondary small"><i class="fa-brands fa-docker me-1"></i>Containers:</span>';
                d.docker.forEach(c => {
                    const color = c.running ? 'rgba(16,185,129,0.15)' : 'rgba(244,63,94,0.15)';
                    const textColor = c.running ? '#34d399' : '#fb7185';
                    const border = c.running ? 'rgba(16,185,129,0.3)' : 'rgba(244,63,94,0.3)';
                    const icon = c.running ? '●' : '○';
                    html += `<span class="badge" style="background:${color};color:${textColor};border:1px solid ${border};font-size:0.7rem;padding:3px 8px;border-radius:6px;">${icon} ${c.name}</span>`;
                });
                dockerEl.innerHTML = html;
            }
        };

        sse.onerror = function () {
            document.getElementById('widget-live-badge').style.opacity = '0.4';
        };
    })();
</script>
<script>
    // ── Auth Chart ───────────────────────────────────────────────────────
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('authChart').getContext('2d');

        // Gradient fills
        const acceptGradient = ctx.createLinearGradient(0, 0, 0, 260);
        acceptGradient.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
        acceptGradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        const rejectGradient = ctx.createLinearGradient(0, 0, 0, 260);
        rejectGradient.addColorStop(0, 'rgba(244, 63, 94, 0.35)');
        rejectGradient.addColorStop(1, 'rgba(244, 63, 94, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($labels) !!},
                datasets: [
                    {
                        label: 'Access Accepts',
                        data: {!! json_encode($acceptData) !!},
                        borderColor: '#10b981',
                        borderWidth: 2.5,
                        backgroundColor: acceptGradient,
                        tension: 0.35,
                        fill: true,
                        pointBackgroundColor: '#10b981',
                        pointRadius: 3
                    },
                    {
                        label: 'Access Rejects',
                        data: {!! json_encode($rejectData) !!},
                        borderColor: '#f43f5e',
                        borderWidth: 2.5,
                        backgroundColor: rejectGradient,
                        tension: 0.35,
                        fill: true,
                        pointBackgroundColor: '#f43f5e',
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#94a3b8',
                            font: { family: 'Inter', size: 12 },
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#64748b', font: { size: 11 } },
                        grid: { color: 'rgba(255, 255, 255, 0.04)' }
                    },
                    y: {
                        ticks: { color: '#64748b', font: { size: 11 } },
                        grid: { color: 'rgba(255, 255, 255, 0.04)' },
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection

