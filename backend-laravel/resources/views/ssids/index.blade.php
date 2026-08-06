@extends('layouts.app')

@section('title', 'Multi-SSID & Dynamic VLAN')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1"><i class="fa-solid fa-wifi text-info me-2"></i>Multi-SSID & Dynamic VLAN Management</h3>
        <p class="text-secondary small mb-0">Configure UniFi SSIDs and IEEE 802.1Q dynamic VLAN assignments</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSsidModal">
        <i class="fa-solid fa-plus me-1"></i> Add New SSID
    </button>
</div>

<div class="card-custom p-4">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>SSID Name</th>
                    <th>IEEE 802.1Q Dynamic VLAN ID</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ssids as $ssid)
                    <tr>
                        <td class="fw-bold text-info"><i class="fa-solid fa-wifi me-2"></i>{{ $ssid->ssid_name }}</td>
                        <td><span class="badge bg-primary fs-6">VLAN {{ $ssid->vlan_id }}</span></td>
                        <td class="text-secondary small">{{ $ssid->description ?? '-' }}</td>
                        <td>
                            @if($ssid->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('ssids.destroy', $ssid->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this SSID?')">
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
                        <td colspan="5" class="text-center text-muted py-4">No SSIDs configured yet</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add SSID -->
<div class="modal fade" id="addSsidModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="fa-solid fa-plus text-info me-2"></i>Add UniFi SSID & Dynamic VLAN</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('ssids.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">SSID Broadcast Name</label>
                        <input type="text" name="ssid_name" class="form-control bg-secondary text-white" placeholder="e.g. SSID-Staff" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IEEE 802.1Q Dynamic VLAN ID (1 - 4094)</label>
                        <input type="number" name="vlan_id" class="form-control bg-secondary text-white" placeholder="e.g. 10" min="1" max="4094" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <input type="text" name="description" class="form-control bg-secondary text-white" placeholder="Staff corporate network">
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save SSID</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
