@extends('layouts.app')

@section('title', 'MAC Address Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="fa-solid fa-laptop text-info me-2"></i>MAC Address Devices</h3>
        <p class="text-secondary small mb-0">Manage authorized MAC addresses and Multi-SSID access permissions</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
        <i class="fa-solid fa-plus me-1"></i> Register New Device
    </button>
</div>

<div class="card-custom p-4">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Device Name</th>
                    <th>MAC Address</th>
                    <th>Target SSID</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                    <tr>
                        <td class="fw-semibold">{{ $device->device_name }}</td>
                        <td class="font-monospace text-info">{{ $device->mac_address }}</td>
                        <td><span class="badge bg-secondary">{{ $device->ssid }}</span></td>
                        <td>
                            @if($device->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('devices.update', $device->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="device_name" value="{{ $device->device_name }}">
                                <input type="hidden" name="mac_address" value="{{ $device->raw_mac ?? $device->mac_address }}">
                                <input type="hidden" name="ssid" value="{{ $device->ssid }}">
                                <input type="hidden" name="status" value="{{ $device->status === 'active' ? 'inactive' : 'active' }}">
                                <button type="submit" class="btn btn-sm {{ $device->status === 'active' ? 'btn-warning' : 'btn-success' }}">
                                    <i class="fa-solid {{ $device->status === 'active' ? 'fa-power-off' : 'fa-check' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('devices.destroy', $device->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this device?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No MAC devices registered yet</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add Device -->
<div class="modal fade" id="addDeviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="fa-solid fa-plus text-info me-2"></i>Register MAC Address Device</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('devices.store') }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="active">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Device Name</label>
                        <input type="text" name="device_name" class="form-control bg-secondary text-white" placeholder="e.g. CEO Laptop" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">MAC Address (Format: AA:BB:CC:DD:EE:FF)</label>
                        <input type="text" name="mac_address" class="form-control bg-secondary text-white font-monospace" placeholder="AA:BB:CC:DD:EE:FF" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target SSID</label>
                        <input type="text" name="ssid" class="form-control bg-secondary text-white" placeholder="e.g. ALL or SSID Name" value="ALL" required>
                        <small class="text-muted">Specify the target SSID or use ALL for any.</small>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Device</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
