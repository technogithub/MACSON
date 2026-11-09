@extends('layouts.app')

@section('title', 'Server Monitor')

@section('content')

{{-- Page Header --}}
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1 font-outfit text-light d-flex align-items-center gap-2">
            <i class="fa-solid fa-server text-info"></i>
            <span>Server Monitor</span>
            <span class="badge rounded-pill ms-1" id="live-badge" style="background:rgba(16,185,129,0.15);color:#34d399;border:1px solid rgba(16,185,129,0.3);font-size:0.72rem;">
                <span class="status-pulse d-inline-block me-1" style="width:6px;height:6px;"></span>LIVE
            </span>
        </h3>
        <p class="text-secondary small mb-0">Real-time server resource & container monitoring</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="text-secondary small"><i class="fa-regular fa-clock me-1"></i>Last update: <span id="last-update">—</span></span>
        <span class="badge bg-dark-subtle text-secondary border border-secondary" style="font-size:0.72rem;">
            Uptime: <span id="server-uptime">—</span>
        </span>
    </div>
</div>

{{-- Metric Cards Row --}}
<div class="row g-3 mb-4">
    {{-- CPU Card --}}
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="macson-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="text-secondary small fw-medium mb-1">CPU Usage</div>
                    <h2 class="fw-bold font-outfit text-light mb-0" id="cpu-pct" style="font-size:2.2rem;">—</h2>
                    <div class="text-secondary small mt-1" id="cpu-cores">— cores</div>
                </div>
                <div class="macson-stat-icon" style="background:rgba(56,189,248,0.12);color:#38bdf8;border:1px solid rgba(56,189,248,0.25);">
                    <i class="fa-solid fa-microchip"></i>
                </div>
            </div>
            <div class="progress mt-2" style="height:6px;background:rgba(255,255,255,0.07);border-radius:4px;">
                <div class="progress-bar" id="cpu-bar" role="progressbar" style="width:0%;background:#38bdf8;border-radius:4px;transition:width 0.6s ease;"></div>
            </div>
            {{-- Mini sparkline chart --}}
            <canvas id="cpu-chart" height="50" class="mt-3" style="max-height:50px;"></canvas>
        </div>
    </div>

    {{-- RAM Card --}}
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="macson-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="text-secondary small fw-medium mb-1">RAM Usage</div>
                    <h2 class="fw-bold font-outfit text-light mb-0" id="ram-pct" style="font-size:2.2rem;">—</h2>
                    <div class="text-secondary small mt-1" id="ram-detail">— / — MB</div>
                </div>
                <div class="macson-stat-icon" style="background:rgba(245,158,11,0.12);color:#fbbf24;border:1px solid rgba(245,158,11,0.25);">
                    <i class="fa-solid fa-memory"></i>
                </div>
            </div>
            <div class="progress mt-2" style="height:6px;background:rgba(255,255,255,0.07);border-radius:4px;">
                <div class="progress-bar" id="ram-bar" role="progressbar" style="width:0%;background:#f59e0b;border-radius:4px;transition:width 0.6s ease;"></div>
            </div>
            <canvas id="ram-chart" height="50" class="mt-3" style="max-height:50px;"></canvas>
        </div>
    </div>

    {{-- Disk Card --}}
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="macson-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="text-secondary small fw-medium mb-1">Disk Usage</div>
                    <h2 class="fw-bold font-outfit text-light mb-0" id="disk-pct" style="font-size:2.2rem;">—</h2>
                    <div class="text-secondary small mt-1" id="disk-detail">— / — GB</div>
                </div>
                <div class="macson-stat-icon" style="background:rgba(192,132,252,0.12);color:#c084fc;border:1px solid rgba(192,132,252,0.25);">
                    <i class="fa-solid fa-hard-drive"></i>
                </div>
            </div>
            <div class="progress mt-2" style="height:6px;background:rgba(255,255,255,0.07);border-radius:4px;">
                <div class="progress-bar" id="disk-bar" role="progressbar" style="width:0%;background:#c084fc;border-radius:4px;transition:width 0.6s ease;"></div>
            </div>
            <div class="mt-3 d-flex justify-content-between text-secondary" style="font-size:0.75rem;">
                <span>Free: <span class="text-light fw-semibold" id="disk-free">—</span> GB</span>
                <span>Total: <span class="text-light fw-semibold" id="disk-total">—</span> GB</span>
            </div>
        </div>
    </div>

    {{-- Network Card --}}
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="macson-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="text-secondary small fw-medium mb-1">Network I/O</div>
                    <div class="mt-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-bold" style="color:#10b981;font-size:1.1rem;">↑</span>
                            <span class="fw-bold font-outfit text-light" id="net-tx" style="font-size:1.3rem;">—</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold" style="color:#38bdf8;font-size:1.1rem;">↓</span>
                            <span class="fw-bold font-outfit text-light" id="net-rx" style="font-size:1.3rem;">—</span>
                        </div>
                    </div>
                </div>
                <div class="macson-stat-icon" style="background:rgba(16,185,129,0.12);color:#34d399;border:1px solid rgba(16,185,129,0.25);">
                    <i class="fa-solid fa-network-wired"></i>
                </div>
            </div>
            <canvas id="net-chart" height="60" class="mt-2" style="max-height:60px;"></canvas>
        </div>
    </div>
</div>

{{-- Docker Containers Table --}}
<div class="macson-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold font-outfit text-light mb-0 d-flex align-items-center gap-2">
            <i class="fa-brands fa-docker text-info"></i>
            <span>Docker Containers</span>
        </h5>
        <span class="text-secondary small">Auto-refresh every 5s</span>
    </div>
    <div class="table-responsive">
        <table class="table macson-table align-middle">
            <thead>
                <tr>
                    <th>Container</th>
                    <th>State</th>
                    <th>Status Detail</th>
                </tr>
            </thead>
            <tbody id="docker-table-body">
                <tr>
                    <td colspan="3" class="text-center text-secondary py-4 small">
                        <i class="fa-solid fa-spinner fa-spin me-2"></i>Connecting to stream...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Sparkline chart setup ────────────────────────────────────────────
    const maxPoints = 30;
    const sparklineOpts = (color) => ({
        type: 'line',
        data: {
            labels: Array(maxPoints).fill(''),
            datasets: [{
                data: Array(maxPoints).fill(null),
                borderColor: color,
                borderWidth: 1.8,
                backgroundColor: color.replace(')', ', 0.1)').replace('rgb', 'rgba'),
                tension: 0.4,
                fill: true,
                pointRadius: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 300 },
            plugins: { legend: { display: false } },
            scales: {
                x: { display: false },
                y: { display: false, min: 0, max: 100 }
            }
        }
    });

    const cpuChart  = new Chart(document.getElementById('cpu-chart'),  sparklineOpts('rgb(56,189,248)'));
    const ramChart  = new Chart(document.getElementById('ram-chart'),  sparklineOpts('rgb(245,158,11)'));

    // Network chart (dual dataset)
    const netChart = new Chart(document.getElementById('net-chart'), {
        type: 'line',
        data: {
            labels: Array(maxPoints).fill(''),
            datasets: [
                {
                    label: 'Upload',
                    data: Array(maxPoints).fill(0),
                    borderColor: '#10b981',
                    borderWidth: 1.8,
                    backgroundColor: 'rgba(16,185,129,0.08)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0,
                },
                {
                    label: 'Download',
                    data: Array(maxPoints).fill(0),
                    borderColor: '#38bdf8',
                    borderWidth: 1.8,
                    backgroundColor: 'rgba(56,189,248,0.08)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 300 },
            plugins: { legend: { display: false } },
            scales: {
                x: { display: false },
                y: { display: false, min: 0 }
            }
        }
    });

    function pushSparkline(chart, value) {
        chart.data.datasets[0].data.shift();
        chart.data.datasets[0].data.push(value);
        chart.update('none');
    }

    function pushNetChart(tx, rx) {
        netChart.data.datasets[0].data.shift();
        netChart.data.datasets[0].data.push(tx / 1024); // KB/s
        netChart.data.datasets[1].data.shift();
        netChart.data.datasets[1].data.push(rx / 1024);
        netChart.update('none');
    }

    // ── Color helpers ────────────────────────────────────────────────────
    function colorFor(pct) {
        if (pct >= 85) return '#f43f5e';
        if (pct >= 65) return '#f59e0b';
        return '#10b981';
    }

    // ── SSE Connection ───────────────────────────────────────────────────
    const sse = new EventSource("{{ route('server-monitor.stream') }}");

    sse.onmessage = function (e) {
        const d = JSON.parse(e.data);

        // Timestamp
        document.getElementById('last-update').textContent = d.timestamp ?? '—';
        document.getElementById('server-uptime').textContent = d.uptime ?? '—';

        // CPU
        const cpu = d.cpu?.percent ?? 0;
        document.getElementById('cpu-pct').textContent = cpu + '%';
        document.getElementById('cpu-cores').textContent = (d.cpu?.cores ?? '—') + ' cores';
        const cpuBar = document.getElementById('cpu-bar');
        cpuBar.style.width = cpu + '%';
        cpuBar.style.background = colorFor(cpu);
        pushSparkline(cpuChart, cpu);

        // RAM
        const ram = d.ram?.percent ?? 0;
        document.getElementById('ram-pct').textContent = ram + '%';
        document.getElementById('ram-detail').textContent =
            (d.ram?.used_mb ?? '—') + ' / ' + (d.ram?.total_mb ?? '—') + ' MB';
        const ramBar = document.getElementById('ram-bar');
        ramBar.style.width = ram + '%';
        ramBar.style.background = colorFor(ram);
        pushSparkline(ramChart, ram);

        // Disk
        const disk = d.disk?.percent ?? 0;
        document.getElementById('disk-pct').textContent = disk + '%';
        document.getElementById('disk-detail').textContent =
            (d.disk?.used_gb ?? '—') + ' / ' + (d.disk?.total_gb ?? '—') + ' GB';
        document.getElementById('disk-free').textContent  = d.disk?.free_gb ?? '—';
        document.getElementById('disk-total').textContent = d.disk?.total_gb ?? '—';
        const diskBar = document.getElementById('disk-bar');
        diskBar.style.width = disk + '%';
        diskBar.style.background = colorFor(disk);

        // Network
        document.getElementById('net-tx').textContent = d.network?.tx_human ?? '—';
        document.getElementById('net-rx').textContent = d.network?.rx_human ?? '—';
        pushNetChart(d.network?.tx_bytes_s ?? 0, d.network?.rx_bytes_s ?? 0);

        // Docker containers
        const tbody = document.getElementById('docker-table-body');
        if (d.docker && d.docker.length > 0) {
            tbody.innerHTML = d.docker.map(c => {
                const color  = c.running ? '#34d399'  : '#fb7185';
                const bg     = c.running ? 'rgba(16,185,129,0.1)' : 'rgba(244,63,94,0.1)';
                const border = c.running ? 'rgba(16,185,129,0.3)' : 'rgba(244,63,94,0.3)';
                const icon   = c.running ? '●' : '○';
                const label  = c.running ? 'Running' : 'Stopped';
                return `
                    <tr>
                        <td>
                            <span class="fw-semibold text-light">
                                <i class="fa-brands fa-docker me-2 text-info opacity-75"></i>${c.name}
                            </span>
                        </td>
                        <td>
                            <span class="badge" style="background:${bg};color:${color};border:1px solid ${border};font-size:0.75rem;padding:4px 10px;border-radius:6px;">
                                ${icon} ${label}
                            </span>
                        </td>
                        <td class="text-secondary small">${c.status}</td>
                    </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-secondary py-3 small">No containers found</td></tr>';
        }
    };

    sse.onerror = function () {
        document.getElementById('live-badge').style.opacity = '0.4';
        document.getElementById('live-badge').innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i>DISCONNECTED';
    };
});
</script>
@endpush
