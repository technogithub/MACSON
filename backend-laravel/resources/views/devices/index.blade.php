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
/* Big SSID Menu Tabs / Cards */
.ssid-menu-card {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 14px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-decoration: none;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    height: 100%;
}
.ssid-menu-card:hover {
    border-color: rgba(56, 189, 248, 0.5);
    background: #24344d;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.35);
}
.ssid-menu-card.active {
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.16) 0%, rgba(99, 102, 241, 0.16) 100%);
    border: 1.5px solid #38bdf8;
    box-shadow: 0 0 20px rgba(56, 189, 248, 0.22), inset 0 0 12px rgba(56, 189, 248, 0.08);
}
.ssid-menu-card.active::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, #38bdf8 0%, #818cf8 100%);
}
.ssid-menu-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.ssid-menu-card .badge-counter {
    font-size: 0.8rem;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 20px;
    font-family: 'JetBrains Mono', monospace;
}

/* Pagination styling */
.pagination {
    margin-bottom: 0;
    gap: 4px;
}
.pagination .page-item .page-link {
    background: #0f172a;
    border: 1px solid #334155;
    color: #94a3b8;
    border-radius: 8px !important;
    font-size: 0.85rem;
    padding: 6px 12px;
    transition: all 0.15s;
}
.pagination .page-item .page-link:hover {
    background: #334155;
    color: #38bdf8;
    border-color: #475569;
}
.pagination .page-item.active .page-link {
    background: #0ea5e9;
    border-color: #0ea5e9;
    color: #ffffff;
    font-weight: 600;
}
.pagination .page-item.disabled .page-link {
    background: #0f172a;
    border-color: #1e293b;
    color: #475569;
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
        <span class="num text-info" id="statTotal">{{ $totalCount ?? $devices->total() }}</span>
    </div>
    <div class="stat-chip">
        <i class="fa-solid fa-circle-check text-success"></i>
        <span>Active:</span>
        <span class="num text-success" id="statActive">{{ $activeCount ?? $devices->where('status','active')->count() }}</span>
    </div>
    <div class="stat-chip">
        <i class="fa-solid fa-circle-xmark text-danger"></i>
        <span>Inactive:</span>
        <span class="num text-danger" id="statInactive">{{ $inactiveCount ?? $devices->where('status','inactive')->count() }}</span>
    </div>
    <div class="stat-chip">
        <i class="fa-solid fa-tag" style="color:#a5b4fc;"></i>
        <span>VLAN Assigned:</span>
        <span class="num" style="color:#a5b4fc;" id="statVlan">{{ $devices->whereNotNull('vlan_id')->count() }}</span>
    </div>
    <div class="stat-chip ms-auto text-secondary" style="font-size:0.78rem;"></div>
</div>

<!-- ===================== MENU BESAR: NAVIGASI WIFI SSID ===================== -->
<div class="row g-3 mb-4">
    <!-- Tab 1: SEMUA MAC -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ route('devices.index', array_merge(request()->except(['page', 'ssid']), ['ssid' => 'all'])) }}"
           class="ssid-menu-card {{ $selectedSsid === 'all' ? 'active' : '' }}">
            <div class="d-flex align-items-center gap-3">
                <div class="ssid-menu-icon" style="background:rgba(56,189,248,0.12); color:#38bdf8; border:1px solid rgba(56,189,248,0.25);">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div>
                    <div class="fw-bold text-light" style="font-size:0.95rem; letter-spacing:0.02em;">SEMUA MAC</div>
                    <div class="text-secondary small" style="font-size:0.75rem;">Semua Network SSID</div>
                </div>
            </div>
            <div>
                <span class="badge {{ $selectedSsid === 'all' ? 'bg-info text-dark fw-bold' : 'bg-dark text-info border border-secondary' }} badge-counter">
                    {{ $totalCount }}
                </span>
            </div>
        </a>
    </div>

    <!-- Tab 2: SJA REG -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ route('devices.index', array_merge(request()->except(['page', 'ssid']), ['ssid' => 'SJA REG'])) }}"
           class="ssid-menu-card {{ $selectedSsid === 'SJA REG' ? 'active' : '' }}">
            <div class="d-flex align-items-center gap-3">
                <div class="ssid-menu-icon" style="background:rgba(16,185,129,0.12); color:#34d399; border:1px solid rgba(16,185,129,0.25);">
                    <i class="fa-solid fa-wifi"></i>
                </div>
                <div>
                    <div class="fw-bold text-light" style="font-size:0.95rem; letter-spacing:0.02em;">SJA REG</div>
                    <div class="text-secondary small" style="font-size:0.75rem;">Wifi Staff & Reguler</div>
                </div>
            </div>
            <div>
                <span class="badge {{ $selectedSsid === 'SJA REG' ? 'bg-success text-white fw-bold' : 'bg-dark text-success border border-secondary' }} badge-counter">
                    {{ $ssidCounts['SJA REG'] ?? 0 }}
                </span>
            </div>
        </a>
    </div>

    <!-- Tab 3: SJA AP -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ route('devices.index', array_merge(request()->except(['page', 'ssid']), ['ssid' => 'SJA AP'])) }}"
           class="ssid-menu-card {{ $selectedSsid === 'SJA AP' ? 'active' : '' }}">
            <div class="d-flex align-items-center gap-3">
                <div class="ssid-menu-icon" style="background:rgba(245,158,11,0.12); color:#fbbf24; border:1px solid rgba(245,158,11,0.25);">
                    <i class="fa-solid fa-tower-broadcast"></i>
                </div>
                <div>
                    <div class="fw-bold text-light" style="font-size:0.95rem; letter-spacing:0.02em;">SJA AP</div>
                    <div class="text-secondary small" style="font-size:0.75rem;">Access Point Backbone</div>
                </div>
            </div>
            <div>
                <span class="badge {{ $selectedSsid === 'SJA AP' ? 'bg-warning text-dark fw-bold' : 'bg-dark text-warning border border-secondary' }} badge-counter">
                    {{ $ssidCounts['SJA AP'] ?? 0 }}
                </span>
            </div>
        </a>
    </div>

    <!-- Tab 4: DEVICE OPS AP -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ route('devices.index', array_merge(request()->except(['page', 'ssid']), ['ssid' => 'DEVICE OPS AP'])) }}"
           class="ssid-menu-card {{ $selectedSsid === 'DEVICE OPS AP' ? 'active' : '' }}">
            <div class="d-flex align-items-center gap-3">
                <div class="ssid-menu-icon" style="background:rgba(168,85,247,0.12); color:#c084fc; border:1px solid rgba(168,85,247,0.25);">
                    <i class="fa-solid fa-microchip"></i>
                </div>
                <div>
                    <div class="fw-bold text-light" style="font-size:0.95rem; letter-spacing:0.02em;">DEVICE OPS AP</div>
                    <div class="text-secondary small" style="font-size:0.75rem;">Operasional & POS</div>
                </div>
            </div>
            <div>
                <span class="badge {{ $selectedSsid === 'DEVICE OPS AP' ? 'text-white fw-bold' : 'bg-dark border border-secondary' }} badge-counter" style="{{ $selectedSsid === 'DEVICE OPS AP' ? 'background:#a855f7;' : 'color:#c084fc;' }}">
                    {{ $ssidCounts['DEVICE OPS AP'] ?? 0 }}
                </span>
            </div>
        </a>
    </div>
</div>

@if(!empty($otherSsids) && count($otherSsids) > 0)
<!-- Other SSIDs Quick Selector (if present) -->
<div class="d-flex align-items-center gap-2 mb-3 px-1 flex-wrap">
    <span class="text-secondary small" style="font-size:0.78rem;"><i class="fa-solid fa-tags me-1"></i>SSID Lainnya:</span>
    @foreach($otherSsids as $oSsid)
        <a href="{{ route('devices.index', array_merge(request()->except(['page', 'ssid']), ['ssid' => $oSsid])) }}"
           class="btn btn-sm {{ $selectedSsid === $oSsid ? 'btn-info text-white fw-semibold' : 'btn-outline-secondary text-secondary' }} py-0 px-2.5 rounded-pill"
           style="font-size:0.75rem;">
            {{ $oSsid }} ({{ $ssidCounts[$oSsid] ?? 0 }})
        </a>
    @endforeach
</div>
@endif

<!-- Search & Filter Bar (server-side submit) -->
<form method="GET" action="{{ route('devices.index') }}" id="searchFilterForm" class="d-flex gap-3 mb-3 flex-wrap align-items-center">
    <div class="search-wrapper flex-grow-1" style="min-width:240px; max-width:420px;">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" name="search" id="liveSearch" class="form-control search-input"
               placeholder="Search MAC, device name, SSID, description..."
               value="{{ request('search') }}"
               autocomplete="off">
        <button type="button" class="search-clear" id="clearSearch" title="Clear search"
                style="{{ request('search') ? 'display:flex;' : 'display:none;' }}">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <select name="status" id="filterStatus" class="form-select filter-select" style="width:160px;" onchange="this.form.submit()">
        <option value="all" {{ request('status','all') === 'all' ? 'selected' : '' }}>All Status</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
    </select>
    <select name="ssid" id="filterSsid" class="form-select filter-select" style="width:180px;" onchange="this.form.submit()">
        <option value="all" {{ request('ssid','all') === 'all' ? 'selected' : '' }}>All SSIDs</option>
        @php
            $dropdownSsids = array_unique(array_merge($primarySsids, $availableSsids, $masterNames ?? []));
        @endphp
        @foreach($dropdownSsids as $s)
            @if(!empty($s))
                <option value="{{ $s }}" {{ request('ssid') === $s ? 'selected' : '' }}>{{ $s }}</option>
            @endif
        @endforeach
    </select>
    <div class="d-flex align-items-center gap-2">
        <label for="filterPerPage" class="text-secondary small text-nowrap mb-0 d-none d-md-inline" style="font-size:0.82rem;">
            <i class="fa-solid fa-list-ol me-1 text-info"></i>Tampilkan:
        </label>
        <select name="per_page" id="filterPerPage" class="form-select filter-select" style="width:125px;" onchange="this.form.submit()">
            <option value="15" {{ (int)request('per_page', 15) === 15 ? 'selected' : '' }}>15 / hal</option>
            <option value="50" {{ (int)request('per_page', 15) === 50 ? 'selected' : '' }}>50 / hal</option>
            <option value="100" {{ (int)request('per_page', 15) === 100 ? 'selected' : '' }}>100 / hal</option>
        </select>
    </div>
    @if(request()->hasAny(['search','status','ssid']) && (request('ssid','all') !== 'all' || request('status','all') !== 'all' || request('search')))
    <a href="{{ route('devices.index', request()->has('per_page') ? ['per_page' => request('per_page')] : []) }}" class="btn btn-sm btn-outline-secondary" title="Reset semua filter">
        <i class="fa-solid fa-rotate-left me-1"></i>Reset
    </a>
    <span class="text-secondary small" style="white-space:nowrap;">{{ $devices->total() }} result(s)</span>
    @endif
</form>

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
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state py-5 text-center">
                                <div class="empty-icon text-warning mb-2" style="font-size:2rem;"><i class="fa-solid fa-database"></i></div>
                                <p class="fw-semibold text-secondary mb-1">No devices registered yet</p>
                                <p class="text-muted small mb-0">Click "Register Device" to add your first MAC address</p>
                            </div>
                        </td>
                    </tr>
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
    <!-- Pagination Footer -->
    @if($devices->total() > 0)
    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top flex-wrap gap-3" style="border-color:#334155 !important;">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="text-secondary small">
                Showing <strong class="text-light">{{ $devices->firstItem() ?? 0 }}–{{ $devices->lastItem() ?? 0 }}</strong> of <strong class="text-info">{{ $devices->total() }}</strong> devices
            </span>
            <div class="d-flex align-items-center gap-1.5 ms-2">
                <span class="text-muted" style="font-size:0.75rem;">Limit:</span>
                <div class="btn-group btn-group-sm">
                    @foreach([15, 50, 100] as $size)
                        <a href="{{ route('devices.index', array_merge(request()->query(), ['per_page' => $size, 'page' => 1])) }}"
                           class="btn btn-sm {{ (int)request('per_page', 15) === $size ? 'btn-info text-white fw-bold' : 'btn-outline-secondary text-secondary' }}"
                           style="font-size:0.75rem; padding: 2px 9px;">
                            {{ $size }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
        <div>
            @if($devices->hasPages())
                {{ $devices->links() }}
            @endif
        </div>
    </div>
    @endif
</div>

<!-- ===================== MODAL: Add Device ===================== -->
<div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="addDeviceModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-lg">
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
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Target SSID *</span>
                                @if($selectedSsid !== 'all')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:0.7rem;">Auto: {{ $selectedSsid }}</span>
                                @endif
                            </label>
                            <select name="ssid" id="addDeviceSsid" class="form-select form-control-dark" required>
                                <option value="ALL" {{ $selectedSsid === 'all' ? 'selected' : '' }}>ALL (Authorized for Any SSID)</option>
                                @php
                                    $allModalSsids = array_unique(array_merge($primarySsids, $masterSsids->pluck('ssid_name')->toArray(), $availableSsids));
                                @endphp
                                @foreach($allModalSsids as $sName)
                                    @if($sName !== 'ALL' && !empty($sName))
                                        @php
                                            $mMatch = $masterSsids->firstWhere('ssid_name', $sName);
                                        @endphp
                                        <option value="{{ $sName }}" {{ $selectedSsid === $sName ? 'selected' : '' }}>
                                            {{ $sName }} {{ $mMatch && $mMatch->vlan_id ? '(VLAN ' . $mMatch->vlan_id . ')' : '' }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="form-text">Select target SSID or <strong>ALL</strong></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">VLAN Override <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="number" name="vlan_id" class="form-control form-control-dark" placeholder="e.g. 10 (Leave blank for Auto)" min="1" max="4094">
                            <div class="form-text">Enter VLAN ID manually (1-4094), or leave empty for Auto</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="location" class="form-control form-control-dark" placeholder="e.g. Floor 3 - HQ">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="description" class="form-control form-control-dark" rows="2" placeholder="Brief notes about this device"></textarea>
                        </div>
                        <!-- Bagian Reservasi IP (hanya muncul untuk SSID tertentu) -->
                        <div id="addReserveSection" class="col-12 d-none">
                            <div class="p-3 rounded-3" style="background:rgba(56,189,248,0.05); border:1px solid rgba(56,189,248,0.2);">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-thumbtack text-info"></i>
                                        <span class="fw-bold text-info" style="font-size:0.9rem;">Reservasi Static IP (Opsional)</span>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="addReserveCheck" name="reserve_ip" value="1">
                                        <label class="form-check-label text-secondary small" for="addReserveCheck">Aktifkan</label>
                                    </div>
                                </div>
                                <div id="addReserveFields" class="d-none">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label">Subnet / IP Pool *</label>
                                            <select name="reserve_subnet_id" id="addReserveSubnet" class="form-select form-control-dark">
                                                <option value="">-- Pilih Subnet Pool --</option>
                                                @foreach($dhcpSubnets as $sn)
                                                    <option value="{{ $sn->id }}"
                                                        data-name="{{ strtolower($sn->name) }}"
                                                        data-start="{{ $sn->pool_start }}"
                                                        data-end="{{ $sn->pool_end }}">
                                                        {{ $sn->name }}
                                                        @if($sn->vlan_id) (VLAN {{ $sn->vlan_id }}) @endif
                                                        — {{ $sn->pool_start }} ~ {{ $sn->pool_end }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">IP Address *</label>
                                            <div class="input-group">
                                                <input type="text" name="reserve_ip_address" id="addReserveIp"
                                                       class="form-control form-control-dark font-monospace"
                                                       placeholder="e.g. 10.14.27.50">
                                                <button type="button" class="btn btn-outline-info btn-sm" id="addSuggestIpBtn" title="Auto-suggest IP kosong">
                                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Suggest
                                                </button>
                                            </div>
                                            <div class="form-text" id="addSuggestInfo">Pilih subnet dulu, lalu klik Suggest untuk IP kosong otomatis.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Hostname <span class="text-muted fw-normal">(optional)</span></label>
                                            <input type="text" name="reserve_hostname" id="addReserveHostname"
                                                   class="form-control form-control-dark"
                                                   placeholder="Otomatis dari Device Name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Keterangan <span class="text-muted fw-normal">(optional)</span></label>
                                            <input type="text" name="reserve_description" class="form-control form-control-dark"
                                                   placeholder="Catatan reservasi">
                                        </div>
                                    </div>
                                </div>
                            </div>
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
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
                            <select name="ssid" id="editSsid" class="form-select form-control-dark" required>
                                <option value="ALL">ALL (Authorized for Any SSID)</option>
                                @foreach($allModalSsids as $sName)
                                    @if($sName !== 'ALL' && !empty($sName))
                                        @php
                                            $mMatch = $masterSsids->firstWhere('ssid_name', $sName);
                                        @endphp
                                        <option value="{{ $sName }}">
                                            {{ $sName }} {{ $mMatch && $mMatch->vlan_id ? '(VLAN ' . $mMatch->vlan_id . ')' : '' }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="form-text">Select target SSID or <strong>ALL</strong></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">VLAN Override <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="number" name="vlan_id" id="editVlanId" class="form-control form-control-dark" placeholder="e.g. 10 (Leave blank for Auto)" min="1" max="4094">
                            <div class="form-text">Enter VLAN ID manually (1-4094), or clear for Auto</div>
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
                        <!-- Info: Reservasi auto-hapus jika SSID berubah ke REG/ALL -->
                        <div id="editReserveWarning" class="col-12 d-none">
                            <div class="alert mb-0 py-2" style="background:rgba(251,146,60,0.1); border:1px solid rgba(251,146,60,0.3); border-radius:8px;">
                                <i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>
                                <span class="text-warning small">
                                    <strong>Perhatian:</strong> Mengubah SSID ke <strong>SJA REG</strong> atau <strong>ALL</strong> akan <strong>otomatis menghapus</strong> reservasi Static IP yang terhubung ke device ini.
                                </span>
                            </div>
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

    // ===== SEARCH FORM UX ONLY (actual filtering is server-side) =====
    const searchInput = document.getElementById('liveSearch');
    const clearBtn    = document.getElementById('clearSearch');
    const searchForm  = document.getElementById('searchFilterForm');

    if (searchInput && searchForm) {
        // Show/hide clear button
        function updateClearBtn() {
            if (clearBtn) clearBtn.style.display = searchInput.value.length > 0 ? 'flex' : 'none';
        }
        searchInput.addEventListener('input', updateClearBtn);
        updateClearBtn();

        // Submit on Enter key
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchForm.submit();
            }
        });

        // Clear button: kosongkan input dan submit
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                searchForm.submit();
            });
        }
    }



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

    // ===== ADD DEVICE: Reservasi IP Logic =====
    const addSsidEl          = document.getElementById('addDeviceSsid');
    const addReserveSection  = document.getElementById('addReserveSection');
    const addReserveCheck    = document.getElementById('addReserveCheck');
    const addReserveFields   = document.getElementById('addReserveFields');
    const addReserveSubnet   = document.getElementById('addReserveSubnet');
    const addReserveIp       = document.getElementById('addReserveIp');
    const addReserveHostname = document.getElementById('addReserveHostname');
    const addSuggestBtn      = document.getElementById('addSuggestIpBtn');
    const addDeviceNameEl    = document.querySelector('#addDeviceModal input[name="device_name"]');

    const NO_RESERVE_SSIDS = ['SJA REG', 'ALL', ''];

    function toggleAddReserveSection(ssid) {
        if (NO_RESERVE_SSIDS.includes(ssid)) {
            addReserveSection.classList.add('d-none');
            addReserveCheck.checked = false;
            addReserveFields.classList.add('d-none');
        } else {
            addReserveSection.classList.remove('d-none');
            // Auto-select pool untuk SJA AP
            if (ssid === 'SJA AP') {
                const sjaApOption = Array.from(addReserveSubnet.options).find(
                    o => o.dataset.name && o.dataset.name.includes('sja ap')
                );
                if (sjaApOption) {
                    addReserveSubnet.value    = sjaApOption.value;
                    addReserveSubnet.disabled = true;
                }
            } else {
                addReserveSubnet.disabled = false;
            }
        }
    }

    if (addSsidEl) {
        addSsidEl.addEventListener('change', function() {
            toggleAddReserveSection(this.value);
        });
        // Initial state on modal open
        const addDeviceModal = document.getElementById('addDeviceModal');
        addDeviceModal.addEventListener('show.bs.modal', function() {
            toggleAddReserveSection(addSsidEl.value);
        });
        addDeviceModal.addEventListener('hidden.bs.modal', function() {
            // Reset form reservasi
            addReserveCheck.checked = false;
            addReserveFields.classList.add('d-none');
            addReserveSection.classList.add('d-none');
            addReserveSubnet.disabled = false;
            addReserveIp.value = '';
            if (addReserveHostname) addReserveHostname.value = '';
        });
    }

    // Toggle fields saat checkbox dicentang
    if (addReserveCheck) {
        addReserveCheck.addEventListener('change', function() {
            addReserveFields.classList.toggle('d-none', !this.checked);
            // Pre-fill hostname dari device name
            if (this.checked && addDeviceNameEl && addReserveHostname) {
                addReserveHostname.value = addDeviceNameEl.value || '';
            }
        });
    }

    // Update hostname placeholder saat device name berubah
    if (addDeviceNameEl && addReserveHostname) {
        addDeviceNameEl.addEventListener('input', function() {
            addReserveHostname.placeholder = this.value || 'Otomatis dari Device Name';
        });
    }

    // Auto-suggest IP via AJAX
    if (addSuggestBtn) {
        addSuggestBtn.addEventListener('click', function() {
            const subnetId = addReserveSubnet.value;
            if (!subnetId) {
                alert('Pilih Subnet Pool terlebih dahulu!');
                return;
            }
            addSuggestBtn.disabled = true;
            addSuggestBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Loading...';

            fetch(`/devices/suggest-ip?subnet_id=${subnetId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.suggested_ip) {
                        addReserveIp.value = data.suggested_ip;
                        document.getElementById('addSuggestInfo').textContent = `✓ IP tersedia: ${data.suggested_ip}`;
                    } else {
                        document.getElementById('addSuggestInfo').textContent = 'Tidak ada IP kosong di range pool ini.';
                    }
                })
                .catch(() => {
                    document.getElementById('addSuggestInfo').textContent = 'Gagal memuat suggestion.';
                })
                .finally(() => {
                    addSuggestBtn.disabled = false;
                    addSuggestBtn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles me-1"></i>Suggest';
                });
        });
    }

    // ===== EDIT DEVICE: Warning jika SSID berubah ke REG/ALL =====
    const editSsidEl         = document.getElementById('editSsid');
    const editReserveWarning = document.getElementById('editReserveWarning');

    document.querySelectorAll('.btn-edit-device').forEach(btn => {
        btn.addEventListener('click', function() {
            const originalSsid = this.dataset.ssid;
            // Setelah modal population selesai, track perubahan SSID
            if (editSsidEl && editReserveWarning) {
                setTimeout(() => {
                    editSsidEl.addEventListener('change', function() {
                        const newSsid = this.value;
                        const originalNoReserve = NO_RESERVE_SSIDS.includes(originalSsid);
                        const newNoReserve      = NO_RESERVE_SSIDS.includes(newSsid);
                        if (!originalNoReserve && newNoReserve) {
                            editReserveWarning.classList.remove('d-none');
                        } else {
                            editReserveWarning.classList.add('d-none');
                        }
                    }, { once: false });
                }, 100);
            }
        });
    });

    // Reset edit warning saat modal ditutup
    const editDeviceModalEl = document.getElementById('editDeviceModal');
    if (editDeviceModalEl) {
        editDeviceModalEl.addEventListener('hidden.bs.modal', function() {
            if (editReserveWarning) editReserveWarning.classList.add('d-none');
        });
    }

});
</script>
@endpush
@endsection
