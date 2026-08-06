@extends('layouts.app')

@section('title', 'System Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="fa-solid fa-gauge-high text-info me-2"></i>Network Operations Dashboard</h3>
        <p class="text-secondary small mb-0">Centralized UniFi Access Point MAC Authentication & Dynamic VLAN Tagging Status</p>
    </div>
</div>

<!-- Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small">Active Authorized Devices</span>
                    <h2 class="fw-bold text-success mt-1 mb-0">{{ $totalActive }}</h2>
                </div>
                <div class="bg-success-subtle text-success p-3 rounded-circle fs-3">
                    <i class="fa-solid fa-laptop-medical"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small">Inactive / Blocked</span>
                    <h2 class="fw-bold text-danger mt-1 mb-0">{{ $totalInactive }}</h2>
                </div>
                <div class="bg-danger-subtle text-danger p-3 rounded-circle fs-3">
                    <i class="fa-solid fa-ban"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small">Total Auth Accepts</span>
                    <h2 class="fw-bold text-info mt-1 mb-0">{{ $totalAccept }}</h2>
                </div>
                <div class="bg-info-subtle text-info p-3 rounded-circle fs-3">
                    <i class="fa-solid fa-shield-check"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small">Total Auth Rejects</span>
                    <h2 class="fw-bold text-warning mt-1 mb-0">{{ $totalReject }}</h2>
                </div>
                <div class="bg-warning-subtle text-warning p-3 rounded-circle fs-3">
                    <i class="fa-solid fa-shield-xmark"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Graph & Log Activity -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-custom p-4">
            <h5 class="fw-semibold mb-3"><i class="fa-solid fa-chart-line text-info me-2"></i>Hourly Authentication Requests (Today)</h5>
            <canvas id="authChart" height="110"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-custom p-4">
            <h5 class="fw-semibold mb-3"><i class="fa-solid fa-clock-rotate-left text-info me-2"></i>Recent Access Log Activity</h5>
            <div class="table-responsive">
                <table class="table table-custom table-sm align-middle">
                    <thead>
                        <tr>
                            <th>MAC</th>
                            <th>SSID</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                            <tr>
                                <td class="font-monospace small">{{ $log->mac_address }}</td>
                                <td class="small">{{ $log?->ssid ?? 'N/A' }}</td>
                                <td>
                                    @if($log->auth_result === 'ACCEPT')
                                        <span class="badge bg-success">ACCEPT</span>
                                    @else
                                        <span class="badge bg-danger">REJECT</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No activity logs recorded today</td>
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
    const ctx = document.getElementById('authChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($labels) !!},
            datasets: [
                {
                    label: 'Access Accepts',
                    data: {!! json_encode($acceptData) !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Access Rejects',
                    data: {!! json_encode($rejectData) !!},
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#94a3b8' } }
            },
            scales: {
                x: { ticks: { color: '#64748b' }, grid: { color: '#334155' } },
                y: { ticks: { color: '#64748b' }, grid: { color: '#334155' }, beginAtZero: true }
            }
        }
    });
</script>
@endpush
@endsection
