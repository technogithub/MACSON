@extends('layouts.app')

@section('title', 'RADIUS Audit Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="fa-solid fa-list-check text-info me-2"></i>RADIUS Authentication Audit Logs</h3>
        <p class="text-secondary small mb-0">Real-time access log history sent by Ubiquiti UniFi Access Points</p>
    </div>
    <form action="{{ route('logs.clear') }}" method="POST" onsubmit="return confirm('Clear all audit logs?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">
            <i class="fa-solid fa-trash-can me-1"></i> Clear Logs
        </button>
    </form>
</div>

<div class="card-custom p-4">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Device MAC</th>
                    <th>Target SSID</th>
                    <th>AP MAC (Called-Station)</th>
                    <th>Auth Result</th>
                    <th>Assigned VLAN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="small text-secondary">{{ $log->log_date }}</td>
                        <td class="font-monospace fw-bold text-info">{{ $log->mac_address }}</td>
                        <td><span class="badge bg-secondary">{{ $log->ssid ?? 'N/A' }}</span></td>
                        <td class="font-monospace small text-muted">{{ $log->ap_mac ?? '-' }}</td>
                        <td>
                            @if($log->auth_result === 'ACCEPT')
                                <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>ACCEPT</span>
                            @else
                                <span class="badge bg-danger"><i class="fa-solid fa-xmark me-1"></i>REJECT</span>
                            @endif
                        </td>
                        <td>
                            @if($log->vlan_id)
                                <span class="badge bg-primary">VLAN {{ $log->vlan_id }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No RADIUS authentication logs recorded yet</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        {{ $logs->links() }}
    </div>
</div>
@endsection
