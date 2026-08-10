@extends('layouts.app')

@section('title', 'Network Operations Dashboard')

@section('content')
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1 font-outfit text-light d-flex align-items-center gap-2">
            <i class="fa-solid fa-gauge-high text-info"></i>
            <span>Network Operations Center</span>
        </h3>
        <p class="text-secondary small mb-0">Real-time RADIUS Access Control & Dynamic 802.1Q VLAN Tagging Metrics</p>
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

