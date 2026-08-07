@extends('layouts.app')

@section('title', 'MAC Address Management')

@section('content')
<style>
/* ===== MAC LIST PAGE - PREMIUM STYLES ===== */
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
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(56,189,248,0.08) 0%, transparent 70%);
    pointer-events: none;
}
.search-wrapper {
    position: relative;
}
.search-wrapper .search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    z-index: 2;
    transition: color 0.2s;
}
.search-wrapper input:focus ~ .search-icon,
.search-wrapper:focus-within .search-icon {
    color: #38bdf8;
}
.search-input {
    background: #0f172a !important;
    border: 1px solid #334155 !important;
    border-radius: 10px !important;
    color: #f1f5f9 !important;
    padding-left: 42px !important;
    padding-right: 36px !important;
    height: 44px;
    font-size: 0.9rem;
    transition: border-color 0.2s, box-shadow 0.2s !important;
}
.search-input:focus {
    border-color: #38bdf8 !important;
    box-shadow: 0 0 0 3px rgba(56,189,248,0.12) !important;
}
.search-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 4px;
    border-radius: 50%;
    display: none;
    transition: color 0.2s;
    z-index: 2;
}
.search-clear:hover { color: #f87171; }
.search-clear.visible { display: block; }

.filter-select {
    background: #0f172a !important;
    border: 1px solid #334155 !important;
    color: #f1f5f9 !important;
    border-radius: 10px !important;
    height: 44px;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s !important;
}
.filter-select:focus {
    border-color: #38bdf8 !important;
    box-shadow: 0 0 0 3px rgba(56,189,248,0.12) !important;
}

/* Table */
.devices-table-card {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 16px;
    overflow: hidden;
}
.devices-table thead th {
    background: #0f172a;
    border-color: #334155;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 14px 16px;
}
.devices-table tbody tr {
    border-color: #1e293b;
    transition: background 0.15s;
}
.devices-table tbody tr:hover {
    background: rgba(56,189,248,0.04) !important;
}
.devices-table tbody td {
    padding: 14px 16px;
    border-color: #334155;
    vertical-align: middle;
}
.mac-badge {
    font-family: 'JetBrains Mono', 'Courier New', monospace;
    font-size: 0.8rem;
    color: #38bdf8;
    background: rgba(56,189,248,0.08);
    border: 1px solid rgba(56,189,248,0.2);
    border-radius: 6px;
    padding: 3px 8px;
    letter-spacing: 0.05em;
}
.vlan-badge {
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 6px;
    padding: 3px 8px;
    background: rgba(99,102,241,0.15);
    color: #a5b4fc;
    border: 1px solid rgba(99,102,241,0.3);
}
.vlan-badge.vlan-none {
    background: rgba(100,116,139,0.15);
    color: #64748b;
    border-color: rgba(100,116,139,0.2);
    font-style: italic;
    font-weight: 400;
}
.ssid-badge {
    font-size: 0.78rem;
    background: rgba(34,211,238,0.1);
    color: #22d3ee;
    border: 1px solid rgba(34,211,238,0.2);
    border-radius: 6px;
    padding: 3px 9px;
}

/* Action buttons */
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

/* Top bar action buttons */
.btn-import {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.3);
    color: #34d399;
    border-radius: 10px;
    padding: 8px 16px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}
.btn-import:hover {
    background: rgba(16,185,129,0.2);
    color: #6ee7b7;
    transform: translateY(-1px);
}
.btn-export {
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.3);
    color: #fbbf24;
    border-radius: 10px;
    padding: 8px 16px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}
.btn-export:hover {
    background: rgba(245,158,11,0.2);
    color: #fde68a;
    transform: translateY(-1px);
}
.btn-add-device {
    background: linear-gradient(135deg, #0ea5e9, #6366f1);
    border: none;
    border-radius: 10px;
    padding: 8px 18px;
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
    transition: all 0.2s;
    box-shadow: 0 4px 15px rgba(14,165,233,0.25);
}
.btn-add-device:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(14,165,233,0.35);
    color: white;
}

/* Stats strip */
.stats-strip {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.stat-chip {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 10px;
    padding: 8px 16px;
    font-size: 0.82rem;
    display: flex;
    align-items: center;
    gap: 8px;
}
.stat-chip .num {
    font-size: 1.1rem;
    font-weight: 700;
}

/* Highlight for search matches */
mark.search-hl {
    background: rgba(251,191,36,0.3);
    color: #fef3c7;
    border-radius: 3px;
    padding: 1px 2px;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-state .empty-icon {
    font-size: 3rem;
    color: #334155;
    margin-bottom: 16px;
}

/* Modal styles */
.modal-content {
    background: #1e293b !important;
    border: 1px solid #334155 !important;
    border-radius: 16px !important;
}
.modal-header { border-color: #334155 !important; }
.modal-footer { border-color: #334155 !important; }
.form-control-dark {
    background: #0f172a !important;
    border: 1px solid #334155 !important;
    color: #f1f5f9 !important;
    border-radius: 8px !important;
    transition: border-color 0.2s, box-shadow 0.2s !important;
}
.form-control-dark:focus {
    border-color: #38bdf8 !important;
    box-shadow: 0 0 0 3px rgba(56,189,248,0.12) !important;
}
.form-label { color: #94a3b8; font-size: 0.83rem; font-weight: 600; margin-bottom: 6px; }
.form-text { color: #64748b; font-size: 0.78rem; }

/* Transition for rows */
#deviceTableBody tr {
    transition: opacity 0.15s, transform 0.15s;
}
#deviceTableBody tr.hiding {
    opacity: 0;
    transform: translateX(-4px);
}

/* No results row */
.no-results-row td {
    padding: 40px 20px;
    text-align: center;
    color: #64748b;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1" style="color:#f1f5f9;">
                <i class="fa-solid fa-laptop text-info me-2"></i>MAC Address Management
            </h3>
            <p class="text-secondary small mb-0">Manage authorized MAC addresses, per-device Dynamic VLAN, and Multi-SSID permissions</p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <!-- Import Button -->
            <button class="btn-import btn" data-bs-toggle="modal" data-bs-target="#importModal" title="Import CSV">
                <i class="fa-solid fa-file-import me-1"></i> Import CSV
            </button>
            <!-- Export Button -->
            <a href="{{ route('devices.export') }}" class="btn-export btn" title="Export CSV">
                <i class="fa-solid fa-file-export me-1"></i> Export CSV
            </a>
            <!-- Add Device Button -->
            <button class="btn-add-device btn" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                <i class="fa-solid fa-plus me-1"></i> Register Device
            </button>
        </div>
    </div>
</div>

<!-- Stats Strip -->
<div class="stats-strip" id="statsStrip">
    <div class="stat-chip">
        <i class="fa-solid fa-devices text-info"></i>
        <span>Total:</span>
        <span class="num text-info" id="statTotal">{{ $devices->total() }}</span>
    </div>
    <div class="stat-chip">
        <i class="fa-solid fa-circle-check text-success"></i>
        <span>Active:</span>
        <span class="num text-success" id="statActive">{{ $devices->where('status','active')->count() }}</span>
    </div>
    <div class="stat-chip">
        <i class="fa-solid fa-circle-xmark text-danger"></i>
        <span>Inactive:</span>
        <span class="num text-danger" id="statInactive">{{ $devices->where('status','inactive')->count() }}</span>
    </div>
    <div class="stat-chip">
        <i class="fa-solid fa-tag" style="color:#a5b4fc;"></i>
        <span>VLAN Assigned:</span>
        <span class="num" style="color:#a5b4fc;" id="statVlan">{{ $devices->whereNotNull('vlan_id')->count() }}</span>
    </div>
    <div class="stat-chip ms-auto text-secondary" style="font-size:0.78rem;"></div>
</div>

<!-- Search & Filter Bar -->
<div class="d-flex gap-3 mb-3 flex-wrap align-items-center">
    <div class="search-wrapper flex-grow-1" style="min-width:240px; max-width:420px;">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" id="liveSearch" class="form-control search-input" placeholder="Search MAC, device name, SSID, description..." autocomplete="off">
        <button class="search-clear" id="clearSearch" title="Clear search">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <select id="filterStatus" class="form-select filter-select" style="width:160px;">
        <option value="all" selected>All Status</option>
        <option value="active">Active Only</option>
        <option value="inactive">Inactive Only</option>
    </select>
    <select id="filterSsid" class="form-select filter-select" style="width:180px;">
        <option value="all" selected>All SSIDs</option>
        @foreach($availableSsids as $s)
            <option value="{{ $s }}">{{ $s }}</option>
        @endforeach
    </select>
    <div id="liveResultCount" class="text-secondary small" style="white-space:nowrap;"></div>
</div>

<!-- Devices Table -->
<div class="devices-table-card">
    <div class="table-responsive">
        <table class="table devices-table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:22%;">Device Name</th>
                    <th style="width:18%;">MAC Address</th>
                    <th style="width:14%;">Target SSID</th>
                    <th style="width:10%;">VLAN</th>
                    <th style="width:20%;">Description</th>
                    <th style="width:8%;">Status</th>
                    <th style="width:8%;" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="deviceTableBody">
                @forelse($devices as $device)
                    <tr data-id="{{ $device->id }}"
                        data-name="{{ strtolower($device->device_name) }}"
                        data-mac="{{ strtolower($device->mac_address) }}"
                        data-ssid="{{ strtolower($device->ssid) }}"
                        data-desc="{{ strtolower($device->description ?? '') }}"
                        data-status="{{ $device->status }}">
                        <td class="fw-semibold" style="color:#e2e8f0;">
                            <span class="searchable">{{ $device->device_name }}</span>
                            @if($device->location)
                                <div class="text-muted" style="font-size:0.75rem;"><i class="fa-solid fa-location-dot me-1"></i><span class="searchable">{{ $device->location }}</span></div>
                            @endif
                        </td>
                        <td>
                            <span class="mac-badge searchable">{{ $device->mac_address }}</span>
                        </td>
                        <td>
                            <span class="ssid-badge searchable">{{ $device->ssid }}</span>
                        </td>
                        <td>
                            @if($device->vlan_id)
                                <span class="vlan-badge">VLAN {{ $device->vlan_id }}</span>
                            @else
                                <span class="vlan-badge vlan-none">from SSID</span>
                            @endif
                        </td>
                        <td class="text-secondary small searchable" style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $device->description }}">
                            {{ $device->description ?? '—' }}
                        </td>
                        <td>
                            @if($device->status === 'active')
                                <span class="badge bg-success" style="font-size:0.72rem;">Active</span>
                            @else
                                <span class="badge bg-danger" style="font-size:0.72rem;">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <!-- Edit Button -->
                                <button type="button"
                                    class="btn btn-action btn-outline-info btn-edit-device"
                                    title="Edit Device"
                                    data-id="{{ $device->id }}"
                                    data-name="{{ $device->device_name }}"
                                    data-mac="{{ $device->raw_mac ?? $device->mac_address }}"
                                    data-ssid="{{ $device->ssid }}"
                                    data-location="{{ $device->location }}"
                                    data-description="{{ $device->description }}"
                                    data-status="{{ $device->status }}"
                                    data-vlan="{{ $device->vlan_id }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <!-- Toggle Status -->
                                <form action="{{ route('devices.toggle', $device->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="btn btn-action {{ $device->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        title="{{ $device->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                        <i class="fa-solid {{ $device->status === 'active' ? 'fa-power-off' : 'fa-circle-check' }}"></i>
                                    </button>
                                </form>
                                <!-- Delete -->
                                <form action="{{ route('devices.destroy', $device->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete {{ $device->device_name }} ({{ $device->mac_address }})?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-action btn-outline-danger" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                @endforelse
                <tr id="noResultsRow" style="display:none;">
                    <td colspan="7">
                        <div class="empty-state py-4">
                            <div class="empty-icon text-warning mb-2" style="font-size:2rem;"><i class="fa-solid fa-magnifying-glass"></i></div>
                            <p class="fw-semibold text-secondary mb-1">No matching MAC devices found</p>
                            <p class="text-muted small mb-0">Try adjusting your search query or filter options</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- No search results row (hidden by default) -->
    <div id="noResultsRow" style="display:none; padding: 40px 20px; text-align:center;">
        <i class="fa-solid fa-magnifying-glass text-secondary" style="font-size:2rem; margin-bottom:12px; display:block;"></i>
        <p class="text-secondary mb-1 fw-semibold">No results found</p>
        <p class="text-muted small mb-0">Try different keywords or clear the search</p>
    </div>

    <!-- Pagination -->
    @if($devices->hasPages())
    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top" style="border-color:#334155 !important;">
        <div class="text-secondary small">
            Showing {{ $devices->firstItem() }}–{{ $devices->lastItem() }} of {{ $devices->total() }} devices
        </div>
        <div>{{ $devices->links() }}</div>
    </div>
    @endif
</div>

<!-- ===================== MODAL: Add Device ===================== -->
<div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="addDeviceModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDeviceModalLabel">
                    <i class="fa-solid fa-plus text-info me-2"></i>Register MAC Address Device
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('devices.store') }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="active">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Device Name *</label>
                            <input type="text" name="device_name" class="form-control form-control-dark" placeholder="e.g. CEO Laptop Dell XPS" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">MAC Address *</label>
                            <input type="text" name="mac_address" class="form-control form-control-dark font-monospace" placeholder="AA:BB:CC:DD:EE:FF" required>
                            <div class="form-text">Accepted formats: AA:BB:CC:DD:EE:FF, AA-BB-CC-DD-EE-FF, aabbccddeeff</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Target SSID *</label>
                            <input type="text" name="ssid" class="form-control form-control-dark" placeholder="SSID-Staff" value="ALL" required>
                            <div class="form-text">Use <strong>ALL</strong> for any SSID</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">VLAN Override <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="number" name="vlan_id" class="form-control form-control-dark" placeholder="e.g. 10" min="1" max="4094">
                            <div class="form-text">Overrides SSID's VLAN for this MAC</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="location" class="form-control form-control-dark" placeholder="e.g. Floor 3 - HQ">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="description" class="form-control form-control-dark" rows="2" placeholder="Brief notes about this device"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-add-device btn">
                        <i class="fa-solid fa-plus me-1"></i>Register Device
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MODAL: Edit Device ===================== -->
<div class="modal fade" id="editDeviceModal" tabindex="-1" aria-labelledby="editDeviceModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDeviceModalLabel">
                    <i class="fa-solid fa-pen-to-square text-info me-2"></i>Edit Device
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editDeviceForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Device Name *</label>
                            <input type="text" name="device_name" id="editDeviceName" class="form-control form-control-dark" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">MAC Address *</label>
                            <input type="text" name="mac_address" id="editMacAddress" class="form-control form-control-dark font-monospace" required>
                            <div class="form-text">Accepted formats: AA:BB:CC:DD:EE:FF, AA-BB-CC-DD-EE-FF, aabbccddeeff</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Target SSID *</label>
                            <input type="text" name="ssid" id="editSsid" class="form-control form-control-dark" required>
                            <div class="form-text">Use <strong>ALL</strong> for any SSID</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">VLAN Override <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="number" name="vlan_id" id="editVlanId" class="form-control form-control-dark" placeholder="Leave empty = use SSID VLAN" min="1" max="4094">
                            <div class="form-text">Clear to use SSID's default VLAN</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="location" id="editLocation" class="form-control form-control-dark">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="description" id="editDescription" class="form-control form-control-dark" rows="2"></textarea>
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

<!-- ===================== MODAL: Import CSV ===================== -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">
                    <i class="fa-solid fa-file-import text-success me-2"></i>Import MAC Devices via CSV
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('devices.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-4 p-3 rounded-3" style="background:rgba(16,185,129,0.05); border:1px dashed rgba(16,185,129,0.3);">
                        <p class="mb-1 fw-semibold text-success" style="font-size:0.85rem;"><i class="fa-solid fa-circle-info me-2"></i>CSV Format (with header row):</p>
                        <code class="text-secondary" style="font-size:0.78rem; display:block; line-height:1.8;">
                            MAC Address, SSID, Device Name, Location, Description, Status, VLAN ID
                        </code>
                        <p class="mt-2 mb-0 text-muted" style="font-size:0.75rem;">
                            • Status: <code>active</code> or <code>inactive</code><br>
                            • SSID: use <code>ALL</code> for any SSID<br>
                            • VLAN ID: optional, 1-4094<br>
                            • Duplicates (MAC + SSID) will be skipped automatically
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select CSV File *</label>
                        <input type="file" name="csv_file" class="form-control form-control-dark" accept=".csv,.txt" required>
                        <div class="form-text">Maximum file size: 2MB</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-import btn">
                        <i class="fa-solid fa-upload me-1"></i>Import Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ===== LIVE SEARCH & FILTER =====
    const searchInput   = document.getElementById('liveSearch');
    const clearBtn      = document.getElementById('clearSearch');
    const filterStatus  = document.getElementById('filterStatus');
    const filterSsid    = document.getElementById('filterSsid');
    const tbody         = document.getElementById('deviceTableBody');
    const noResults     = document.getElementById('noResultsRow');
    const resultCount   = document.getElementById('liveResultCount');
    const rows          = Array.from(tbody.querySelectorAll('tr[data-id]'));

    function applyFilters() {
        const q      = searchInput.value.trim().toLowerCase();
        const status = filterStatus.value;
        const ssid   = filterSsid.value.toLowerCase();

        clearBtn.classList.toggle('visible', q.length > 0);

        let visible = 0;

        rows.forEach(row => {
            const rawMacStripped = (row.dataset.mac || '').replace(/[^a-f0-9]/gi, '');
            const qStripped      = q.replace(/[^a-f0-9]/gi, '');

            const matchQ = !q ||
                (row.dataset.name || '').includes(q) ||
                (row.dataset.mac || '').includes(q) ||
                (qStripped.length >= 3 && rawMacStripped.includes(qStripped)) ||
                (row.dataset.ssid || '').includes(q) ||
                (row.dataset.desc || '').includes(q);

            const rowStatus   = (row.dataset.status || '').toLowerCase().trim();
            const targetStatus = status.toLowerCase().trim();
            const matchStatus = targetStatus === 'all' || rowStatus === targetStatus;

            const rowSsid    = (row.dataset.ssid || '').toLowerCase().trim();
            const targetSsid = ssid.toLowerCase().trim();
            const matchSsid  = targetSsid === 'all' || rowSsid === targetSsid || rowSsid.includes(targetSsid);

            if (matchQ && matchStatus && matchSsid) {
                row.style.display = '';
                visible++;
                if (q) highlightRow(row, q);
                else   clearHighlight(row);
            } else {
                row.style.display = 'none';
                clearHighlight(row);
            }
        });

        if (noResults) {
            noResults.style.display = (rows.length > 0 && visible === 0) ? '' : 'none';
        }

        if (resultCount) {
            if (q || status !== 'all' || ssid !== 'all') {
                resultCount.textContent = `${visible} of ${rows.length} shown`;
            } else {
                resultCount.textContent = '';
            }
        }
    }

    function highlightRow(row, q) {
        row.querySelectorAll('.searchable').forEach(el => {
            const original = el.getAttribute('data-original') || el.textContent;
            el.setAttribute('data-original', original);
            const regex = new RegExp(`(${escapeRegex(q)})`, 'gi');
            el.innerHTML = original.replace(regex, '<mark class="search-hl">$1</mark>');
        });
    }

    function clearHighlight(row) {
        row.querySelectorAll('.searchable').forEach(el => {
            const original = el.getAttribute('data-original');
            if (original) {
                el.textContent = original;
                el.removeAttribute('data-original');
            }
        });
    }

    function escapeRegex(s) {
        return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Debounce for smooth search
    let searchTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyFilters, 120);
    });
    filterStatus.addEventListener('change', applyFilters);
    filterSsid.addEventListener('change', applyFilters);
    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        filterStatus.value = 'all';
        filterSsid.value = 'all';
        applyFilters();
        searchInput.focus();
    });

    // Run initial filter check on page load
    applyFilters();

    // ===== EDIT MODAL POPULATION =====
    document.querySelectorAll('.btn-edit-device').forEach(btn => {
        btn.addEventListener('click', function () {
            const id   = this.dataset.id;
            const form = document.getElementById('editDeviceForm');
            form.action = `/devices/${id}`;

            document.getElementById('editDeviceName').value  = this.dataset.name || '';
            document.getElementById('editMacAddress').value  = this.dataset.mac || '';
            document.getElementById('editSsid').value        = this.dataset.ssid || '';
            document.getElementById('editLocation').value    = this.dataset.location || '';
            document.getElementById('editDescription').value = this.dataset.description || '';
            document.getElementById('editStatus').value      = this.dataset.status || 'active';
            document.getElementById('editVlanId').value      = this.dataset.vlan || '';

            const modal = new bootstrap.Modal(document.getElementById('editDeviceModal'));
            modal.show();
        });
    });

    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

});
</script>
@endpush
@endsection
