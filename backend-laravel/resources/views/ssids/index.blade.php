@extends('layouts.app')

@section('title', 'Multi-SSID & Dynamic VLAN')

@section('content')
<style>
/* ===== SSID PAGE - PREMIUM STYLES ===== */
.page-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    border: 1px solid #334155;
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.page-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -5%;
    width: 280px;
    height: 280px;
    background: radial-gradient(circle, rgba(34,211,238,0.07) 0%, transparent 70%);
    pointer-events: none;
}
.ssid-card {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 16px;
    overflow: hidden;
}
.ssid-table thead th {
    background: #0f172a;
    border-color: #334155;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 14px 16px;
}
.ssid-table tbody tr {
    border-color: #334155;
    transition: background 0.15s;
}
.ssid-table tbody tr:hover { background: rgba(34,211,238,0.03) !important; }
.ssid-table tbody td { padding: 14px 16px; border-color: #334155; vertical-align: middle; }

.ssid-name-badge {
    font-size: 0.9rem;
    font-weight: 700;
    color: #22d3ee;
    display: flex;
    align-items: center;
    gap: 8px;
}
.vlan-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(99,102,241,0.15);
    border: 1px solid rgba(99,102,241,0.3);
    color: #a5b4fc;
    border-radius: 8px;
    padding: 5px 12px;
    font-size: 0.82rem;
    font-weight: 700;
}
.device-count-badge {
    background: rgba(56,189,248,0.1);
    border: 1px solid rgba(56,189,248,0.2);
    color: #38bdf8;
    border-radius: 6px;
    padding: 3px 10px;
    font-size: 0.78rem;
    font-weight: 600;
}
.btn-action {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 0.8rem;
    transition: all 0.15s;
}
.btn-action:hover { transform: translateY(-1px); }
.btn-add-ssid {
    background: linear-gradient(135deg, #06b6d4, #6366f1);
    border: none;
    border-radius: 10px;
    padding: 8px 18px;
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
    transition: all 0.2s;
    box-shadow: 0 4px 15px rgba(6,182,212,0.25);
}
.btn-add-ssid:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(6,182,212,0.35);
    color: white;
}
.modal-content {
    background: #1e293b !important;
    border: 1px solid #334155 !important;
    border-radius: 16px !important;
}
.modal-header, .modal-footer { border-color: #334155 !important; }
.form-control-dark {
    background: #0f172a !important;
    border: 1px solid #334155 !important;
    color: #f1f5f9 !important;
    border-radius: 8px !important;
    transition: border-color 0.2s, box-shadow 0.2s !important;
}
.form-control-dark:focus {
    border-color: #22d3ee !important;
    box-shadow: 0 0 0 3px rgba(34,211,238,0.12) !important;
}
.form-label { color: #94a3b8; font-size: 0.83rem; font-weight: 600; margin-bottom: 6px; }
.form-text { color: #64748b; font-size: 0.78rem; }
.info-box {
    background: rgba(34,211,238,0.05);
    border: 1px dashed rgba(34,211,238,0.25);
    border-radius: 10px;
    padding: 14px 16px;
    font-size: 0.82rem;
    color: #94a3b8;
    line-height: 1.7;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1" style="color:#f1f5f9;">
                <i class="fa-solid fa-wifi text-info me-2"></i>Multi-SSID & Dynamic VLAN Management
            </h3>
            <p class="text-secondary small mb-0">Configure UniFi SSIDs and IEEE 802.1Q Dynamic VLAN per-SSID. Per-device VLAN overrides can be set in MAC Devices.</p>
        </div>
        <button class="btn-add-ssid btn" data-bs-toggle="modal" data-bs-target="#addSsidModal">
            <i class="fa-solid fa-plus me-1"></i> Add New SSID
        </button>
    </div>
</div>

<!-- Info box about VLAN Priority -->
<div class="info-box mb-4">
    <p class="mb-1 fw-semibold" style="color:#22d3ee;"><i class="fa-solid fa-circle-info me-2"></i>Dynamic VLAN Priority Order:</p>
    <ol class="mb-0 ps-3">
        <li><strong style="color:#a5b4fc;">Per-device VLAN override</strong> — Set on individual MAC address in <a href="{{ route('devices.index') }}" class="text-info">MAC Devices</a> page (highest priority)</li>
        <li><strong style="color:#22d3ee;">SSID VLAN</strong> — Default VLAN assigned to this SSID (fallback if no device VLAN set)</li>
        <li><strong class="text-secondary">No VLAN (0)</strong> — Device gets untagged access if neither is set</li>
    </ol>
</div>

<!-- SSID Table -->
<div class="ssid-card">
    <div class="table-responsive">
        <table class="table ssid-table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:22%;">SSID Name</th>
                    <th style="width:14%;">IEEE 802.1Q VLAN</th>
                    <th style="width:36%;">Description</th>
                    <th style="width:10%;">Devices</th>
                    <th style="width:8%;">Status</th>
                    <th style="width:10%;" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ssids as $ssid)
                    <tr>
                        <td>
                            <div class="ssid-name-badge">
                                <i class="fa-solid fa-wifi" style="font-size:0.85rem;"></i>
                                {{ $ssid->ssid_name }}
                            </div>
                        </td>
                        <td>
                            @if($ssid->vlan_id)
                                <span class="vlan-tag">
                                    <i class="fa-solid fa-tag" style="font-size:0.7rem;"></i>
                                    VLAN {{ $ssid->vlan_id }}
                                </span>
                            @else
                                <span class="text-muted small fst-italic">Untagged</span>
                            @endif
                        </td>
                        <td class="text-secondary small">{{ $ssid->description ?? '—' }}</td>
                        <td>
                            <a href="{{ route('devices.index') }}?ssid={{ $ssid->ssid_name }}" class="device-count-badge text-decoration-none">
                                <i class="fa-solid fa-laptop me-1" style="font-size:0.7rem;"></i>
                                {{ $ssid->devices_count ?? 0 }} devices
                            </a>
                        </td>
                        <td>
                            @if($ssid->status === 'active')
                                <span class="badge bg-success" style="font-size:0.72rem;">Active</span>
                            @else
                                <span class="badge bg-danger" style="font-size:0.72rem;">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <!-- Edit -->
                                <button type="button"
                                    class="btn btn-action btn-outline-info btn-edit-ssid"
                                    title="Edit SSID"
                                    data-id="{{ $ssid->id }}"
                                    data-name="{{ $ssid->ssid_name }}"
                                    data-vlan="{{ $ssid->vlan_id }}"
                                    data-description="{{ $ssid->description }}"
                                    data-status="{{ $ssid->status }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <!-- Delete -->
                                <form action="{{ route('ssids.destroy', $ssid->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete SSID {{ $ssid->ssid_name }}? Devices using this SSID will lose their association.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-action btn-outline-danger" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="fa-solid fa-wifi-slash" style="font-size:2.5rem; color:#334155; display:block; margin-bottom:12px;"></i>
                            No SSIDs configured yet. Add your first SSID above.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($ssids->hasPages())
    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top" style="border-color:#334155 !important;">
        <div class="text-secondary small">Showing {{ $ssids->firstItem() }}–{{ $ssids->lastItem() }} of {{ $ssids->total() }} SSIDs</div>
        <div>{{ $ssids->links() }}</div>
    </div>
    @endif
</div>

<!-- ===================== MODAL: Add SSID ===================== -->
<div class="modal fade" id="addSsidModal" tabindex="-1" aria-labelledby="addSsidModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSsidModalLabel">
                    <i class="fa-solid fa-plus text-info me-2"></i>Add UniFi SSID & Dynamic VLAN
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('ssids.store') }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="active">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">SSID Broadcast Name *</label>
                            <input type="text" name="ssid_name" class="form-control form-control-dark" placeholder="e.g. SSID-Staff" required>
                            <div class="form-text">Must match exactly the SSID name broadcast by your Access Points</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">IEEE 802.1Q VLAN ID <span class="text-muted fw-normal">(1–4094, optional)</span></label>
                            <input type="number" name="vlan_id" class="form-control form-control-dark" placeholder="e.g. 10" min="1" max="4094">
                            <div class="form-text">Default VLAN for all devices connecting to this SSID. Can be overridden per-MAC in MAC Devices.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="description" class="form-control form-control-dark" placeholder="e.g. Corporate staff high-speed network">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-add-ssid btn">
                        <i class="fa-solid fa-plus me-1"></i>Save SSID
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MODAL: Edit SSID ===================== -->
<div class="modal fade" id="editSsidModal" tabindex="-1" aria-labelledby="editSsidModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSsidModalLabel">
                    <i class="fa-solid fa-pen-to-square text-info me-2"></i>Edit SSID
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSsidForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">SSID Broadcast Name *</label>
                            <input type="text" name="ssid_name" id="editSsidName" class="form-control form-control-dark" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">IEEE 802.1Q VLAN ID <span class="text-muted fw-normal">(1–4094, optional)</span></label>
                            <input type="number" name="vlan_id" id="editVlanId" class="form-control form-control-dark" placeholder="Leave empty = untagged" min="1" max="4094">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="description" id="editDescription" class="form-control form-control-dark">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" id="editStatus" class="form-select form-control-dark">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Edit SSID modal population
    document.querySelectorAll('.btn-edit-ssid').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            document.getElementById('editSsidForm').action = `/ssids/${id}`;
            document.getElementById('editSsidName').value   = this.dataset.name || '';
            document.getElementById('editVlanId').value     = this.dataset.vlan || '';
            document.getElementById('editDescription').value = this.dataset.description || '';
            document.getElementById('editStatus').value     = this.dataset.status || 'active';

            new bootstrap.Modal(document.getElementById('editSsidModal')).show();
        });
    });

    // Auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const a = bootstrap.Alert.getOrCreateInstance(alert);
            if (a) a.close();
        }, 5000);
    });
});
</script>
@endpush
@endsection
