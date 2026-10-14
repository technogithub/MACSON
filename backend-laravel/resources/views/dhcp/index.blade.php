@extends('layouts.app')

@section('title', 'DHCP Server Management')

@section('content')
<style>
/* ===== DHCP PAGE - PREMIUM STYLES ===== */
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
    background: radial-gradient(circle, rgba(99,102,241,0.08) 0%, transparent 70%);
    pointer-events: none;
}
.stat-card {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
}
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}
.dhcp-card {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 16px;
    overflow: hidden;
}
.dhcp-table thead th {
    background: #0f172a;
    border-color: #334155;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 14px 16px;
}
.dhcp-table tbody tr {
    border-color: #334155;
    transition: background 0.15s;
}
.dhcp-table tbody tr:hover { background: rgba(99,102,241,0.03) !important; }
.dhcp-table tbody td { padding: 14px 16px; border-color: #334155; vertical-align: middle; }

/* Badges & Tags */
.vlan-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    font-family: monospace;
    background: rgba(165,180,252,0.1);
    color: #a5b4fc;
    border: 1px solid rgba(165,180,252,0.25);
}
.ip-tag {
    font-family: 'JetBrains Mono', monospace;
    color: #38bdf8;
    font-weight: 600;
}
.mac-tag {
    font-family: 'JetBrains Mono', monospace;
    color: #cbd5e1;
    font-weight: 500;
    letter-spacing: 0.5px;
}

/* Custom Tabs */
.nav-tabs-dhcp {
    border-bottom: 1px solid #334155;
    gap: 8px;
}
.nav-tabs-dhcp .nav-link {
    background: transparent;
    border: none;
    color: #94a3b8;
    font-weight: 600;
    font-size: 0.95rem;
    padding: 12px 20px;
    border-radius: 10px 10px 0 0;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}
.nav-tabs-dhcp .nav-link:hover {
    color: #f8fafc;
    background: rgba(255,255,255,0.03);
}
.nav-tabs-dhcp .nav-link.active {
    color: #38bdf8;
    background: #1e293b;
    border-bottom: 2px solid #38bdf8;
}

/* ===== IP POOL PILL TAB BAR - Modern Minimalist ===== */
.pool-tab-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    overflow-x: auto;
    padding: 4px 2px 8px;
    scrollbar-width: thin;
    scrollbar-color: #334155 transparent;
    flex-wrap: nowrap;
}
.pool-tab-bar::-webkit-scrollbar { height: 3px; }
.pool-tab-bar::-webkit-scrollbar-track { background: transparent; }
.pool-tab-bar::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }

.pool-tab-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 13px;
    border-radius: 999px;
    background: rgba(15,23,42,0.7);
    border: 1px solid #334155;
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.82rem;
    font-weight: 500;
    white-space: nowrap;
    transition: all 0.18s ease;
    flex-shrink: 0;
}
.pool-tab-pill:hover {
    border-color: rgba(99,102,241,0.45);
    color: #a5b4fc;
    background: rgba(99,102,241,0.06);
}
.pool-tab-pill.active {
    background: rgba(99,102,241,0.13);
    border-color: rgba(99,102,241,0.55);
    color: #e0e7ff;
    font-weight: 600;
    box-shadow: 0 0 0 1px rgba(99,102,241,0.2);
}
.pool-tab-pill .ptab-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    opacity: 0.5;
    flex-shrink: 0;
}
.pool-tab-pill.active .ptab-dot { opacity: 1; }
.pool-tab-pill .ptab-vlan {
    font-size: 0.65rem;
    font-weight: 700;
    background: rgba(165,180,252,0.15);
    color: #a5b4fc;
    border: 1px solid rgba(165,180,252,0.3);
    border-radius: 999px;
    padding: 0 5px;
    font-family: monospace;
    letter-spacing: 0.02em;
}
.pool-tab-pill.active .ptab-vlan {
    background: rgba(165,180,252,0.25);
}
.pool-tab-pill .ptab-count {
    font-size: 0.7rem;
    font-weight: 700;
    background: rgba(255,255,255,0.07);
    border-radius: 999px;
    padding: 1px 7px;
    font-family: 'JetBrains Mono', monospace;
    letter-spacing: 0.02em;
    color: inherit;
}
.pool-tab-pill.active .ptab-count {
    background: rgba(99,102,241,0.25);
    color: #c7d2fe;
}
/* Live badge inside pill - green variant */
.pool-tab-pill .ptab-live {
    font-size: 0.68rem;
    font-weight: 700;
    background: rgba(74,222,128,0.12);
    border: 1px solid rgba(74,222,128,0.3);
    color: #4ade80;
    border-radius: 999px;
    padding: 0px 6px;
    font-family: 'JetBrains Mono', monospace;
}
.pool-tab-pill.active .ptab-live {
    background: rgba(74,222,128,0.2);
}
</style>

<!-- ===================== PAGE HEADER ===================== -->
<div class="page-header">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge" style="background:rgba(99,102,241,0.2);color:#a5b4fc;border:1px solid rgba(99,102,241,0.3);">
                    <i class="fa-solid fa-server me-1"></i>Kea DHCPv4 Integration
                </span>
                <span class="badge" style="background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3);">
                    <i class="fa-solid fa-bolt me-1"></i>Multi-VLAN Relay Ready
                </span>
            </div>
            <h2 class="font-outfit fw-bold text-white mb-1">
                <i class="fa-solid fa-network-wired text-info me-2"></i>DHCP Server Management
            </h2>
            <p class="text-secondary small mb-0">Kelola IP Pool tiap segmen VLAN, atur Static IP reservation per-MAC address, dan pantau active dynamic leases.</p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('dhcp.sync') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-info" title="Tulis ulang file config dan reload Kea DHCP">
                    <i class="fa-solid fa-rotate me-1"></i>Sync & Reload Kea
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ===================== STATS ROW ===================== -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(56,189,248,0.15);color:#38bdf8;">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <div class="text-secondary small">Total Subnet Pools</div>
                <div class="fs-4 fw-bold text-white">{{ $subnets->count() }} <span class="fs-6 fw-normal text-muted">Subnets</span></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(165,180,252,0.15);color:#a5b4fc;">
                <i class="fa-solid fa-thumbtack"></i>
            </div>
            <div>
                <div class="text-secondary small">Static IP Reservations</div>
                <div class="fs-4 fw-bold text-white">{{ $staticLeases->total() }} <span class="fs-6 fw-normal text-muted">Reserved</span></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(74,222,128,0.15);color:#4ade80;">
                <i class="fa-solid fa-laptop-code"></i>
            </div>
            <div>
                <div class="text-secondary small">Active Dynamic Leases</div>
                <div class="fs-4 fw-bold text-white"><span id="statActiveDynamicLeases">{{ $totalActiveLeases ?? $activeLeases->total() }}</span> <span class="fs-6 fw-normal text-muted">Online</span></div>
            </div>
        </div>
    </div>
</div>

{{-- Definisikan $currentTab lebih awal agar bisa dipakai di pool bar --}}
@php $currentTab = request('tab', 'subnets'); @endphp

{{-- ===== POOL TAB BAR: hanya tampil di tab Static & Active Leases ===== --}}
@if($currentTab !== 'subnets')
<div class="pool-tab-bar mb-3" id="poolTabBar">

    {{-- Semua Pool --}}
    <a href="{{ route('dhcp.index', array_merge(request()->except(['pool_id', 'static_page', 'lease_page']), ['pool_id' => 'all'])) }}"
       class="pool-tab-pill {{ $selectedPool === 'all' ? 'active' : '' }}"
       title="Semua Subnet Pool">
        <span class="ptab-dot"></span>
        <span>Semua Pool</span>
        <span class="ptab-live" id="poolLiveAll">{{ $totalActiveLeases ?? $activeLeases->total() }} Live</span>
    </a>

    {{-- Per-Subnet Pills --}}
    @foreach($subnets as $subnet)
    @php
        $isActivePool = (string)$selectedPool === (string)$subnet->id;
        $activeCountForSubnet = $activeLeaseCounts[$subnet->id] ?? 0;
    @endphp
    <a href="{{ route('dhcp.index', array_merge(request()->except(['pool_id', 'static_page', 'lease_page']), ['pool_id' => $subnet->id])) }}"
       class="pool-tab-pill {{ $isActivePool ? 'active' : '' }}"
       title="{{ $subnet->name }} — {{ $subnet->subnet }}">
        <span class="ptab-dot"></span>
        <span>{{ $subnet->name }}</span>
        @if($subnet->vlan_id)
            <span class="ptab-vlan">V{{ $subnet->vlan_id }}</span>
        @endif
        @if($subnet->static_leases_count > 0)
            <span class="ptab-count">{{ $subnet->static_leases_count }}s</span>
        @endif
        <span class="ptab-live" id="poolLiveSubnet_{{ $subnet->id }}" style="{{ $activeCountForSubnet > 0 ? '' : 'display:none;' }}">
            {{ $activeCountForSubnet }} Live
        </span>
    </a>
    @endforeach

</div>
@endif

<!-- ===================== TABS NAVIGATION ===================== -->

<ul class="nav nav-tabs nav-tabs-dhcp mb-3" id="dhcpTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link {{ $currentTab === 'subnets' ? 'active' : '' }}" href="{{ route('dhcp.index', array_merge(request()->except(['tab', 'static_page', 'lease_page']), ['tab' => 'subnets'])) }}">
            <i class="fa-solid fa-layer-group"></i> Subnets & IP Pools ({{ $subnets->count() }})
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $currentTab === 'static' ? 'active' : '' }}" href="{{ route('dhcp.index', array_merge(request()->except(['tab', 'static_page', 'lease_page']), ['tab' => 'static'])) }}">
            <i class="fa-solid fa-thumbtack"></i> Static IP Reservations ({{ $staticLeases->total() }})
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $currentTab === 'leases' ? 'active' : '' }}" href="{{ route('dhcp.index', array_merge(request()->except(['tab', 'static_page', 'lease_page']), ['tab' => 'leases'])) }}">
            <i class="fa-solid fa-clock-rotate-left"></i> Active Leases
        </a>
    </li>
</ul>

<div class="tab-content" id="dhcpTabsContent">
    <!-- ===================== TAB 1: SUBNETS ===================== -->
    <div class="tab-pane fade {{ $currentTab === 'subnets' ? 'show active' : '' }}" id="subnets-pane" role="tabpanel">
        <div class="dhcp-card">
            <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-white"><i class="fa-solid fa-list me-2"></i>Daftar Subnet & IP Range</span>
                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#addSubnetModal">
                    <i class="fa-solid fa-plus me-1"></i>Tambah Subnet Baru
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 dhcp-table">
                    <thead>
                        <tr>
                            <th>Subnet Name</th>
                            <th>VLAN</th>
                            <th>Network CIDR</th>
                            <th>Pool Range (Start - End)</th>
                            <th>Gateway / Router</th>
                            <th>DNS Servers</th>
                            <th>Capacity</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subnets as $subnet)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-white">{{ $subnet->name }}</span>
                                        @if(!$subnet->is_active)
                                            <span class="badge bg-secondary text-warning border border-warning" style="font-size: 0.7rem;">Nonaktif</span>
                                        @endif
                                    </div>
                                    @if($subnet->description)
                                        <div class="text-secondary small">{{ $subnet->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($subnet->vlan_id)
                                        <span class="vlan-tag">VLAN {{ $subnet->vlan_id }}</span>
                                    @else
                                        <span class="badge bg-secondary">Untagged</span>
                                    @endif
                                </td>
                                <td><span class="ip-tag">{{ $subnet->subnet }}</span></td>
                                <td>
                                    @if(!empty($subnet->pool_start) && !empty($subnet->pool_end))
                                        <span class="text-light font-monospace">{{ $subnet->pool_start }}</span>
                                        <span class="text-muted mx-1">➔</span>
                                        <span class="text-light font-monospace">{{ $subnet->pool_end }}</span>
                                    @else
                                        <span class="badge border" style="background:rgba(99,102,241,0.15);color:#a5b4fc;border-color:rgba(99,102,241,0.3)!important;">
                                            <i class="fa-solid fa-lock me-1"></i>Static Only (No Dynamic Pool)
                                        </span>
                                    @endif
                                </td>
                                <td><span class="text-info font-monospace">{{ $subnet->gateway }}</span></td>
                                <td><span class="text-secondary small">{{ $subnet->dns_servers }}</span></td>
                                <td>
                                    @if($subnet->total_pool_ips > 0)
                                        <span class="badge bg-dark border border-secondary text-light">
                                            {{ $subnet->total_pool_ips }} IPs
                                        </span>
                                    @else
                                        <span class="badge bg-dark border border-secondary text-secondary">
                                            Static Only
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-info btn-edit-subnet" 
                                            data-id="{{ $subnet->id }}"
                                            data-name="{{ $subnet->name }}"
                                            data-vlan="{{ $subnet->vlan_id }}"
                                            data-subnet="{{ $subnet->subnet }}"
                                            data-start="{{ $subnet->pool_start }}"
                                            data-end="{{ $subnet->pool_end }}"
                                            data-gateway="{{ $subnet->gateway }}"
                                            data-dns="{{ $subnet->dns_servers }}"
                                            data-lease="{{ $subnet->lease_time }}"
                                            data-desc="{{ $subnet->description }}"
                                            data-active="{{ $subnet->is_active ? 1 : 0 }}">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <form action="{{ route('dhcp.subnets.destroy', $subnet->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus subnet ini? Reservasi static di dalamnya juga akan terlepas.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-secondary">
                                    <i class="fa-solid fa-circle-exclamation fs-3 mb-2 d-block text-muted"></i>
                                    Belum ada subnet DHCP yang dibuat. Klik tombol <strong>Tambah Subnet Baru</strong> untuk memulai.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================== TAB 2: STATIC LEASES ===================== -->
    <div class="tab-pane fade {{ $currentTab === 'static' ? 'show active' : '' }}" id="static-pane" role="tabpanel">
        <div class="dhcp-card">
            <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-white"><i class="fa-solid fa-thumbtack text-info me-2"></i>Daftar Static IP Reservations</span>
                    <span class="badge bg-dark border border-secondary text-info">{{ $staticLeases->total() }} Reserved</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#importStaticModal">
                        <i class="fa-solid fa-file-import me-1"></i>Import CSV
                    </button>
                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#addStaticModal">
                        <i class="fa-solid fa-plus me-1"></i>Reservasi IP Baru
                    </button>
                </div>
            </div>

            <!-- Filter & Search Toolbar (Static Leases) -->
            <form method="GET" action="{{ route('dhcp.index') }}" class="d-flex gap-2 p-3 border-bottom border-secondary flex-wrap align-items-center" style="background:rgba(15,23,42,0.4);">
                <input type="hidden" name="tab" value="static">
                <div class="flex-grow-1" style="min-width:220px; max-width:360px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control form-control-dark form-control-sm"
                               placeholder="Cari IP, MAC, hostname, device..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <select name="pool_id" class="form-select form-control-dark form-select-sm" style="width:190px;" onchange="this.form.submit()">
                    <option value="all" {{ $selectedPool === 'all' ? 'selected' : '' }}>Semua Subnet Pool</option>
                    @foreach($subnets as $s)
                        <option value="{{ $s->id }}" {{ (string)$selectedPool === (string)$s->id ? 'selected' : '' }}>
                            {{ $s->name }} ({{ $s->subnet }})
                        </option>
                    @endforeach
                </select>
                <div class="d-flex align-items-center gap-1.5 ms-auto">
                    <label class="text-secondary small text-nowrap mb-0" style="font-size:0.78rem;">Limit:</label>
                    <select name="per_page" class="form-select form-control-dark form-select-sm" style="width:115px;" onchange="this.form.submit()">
                        <option value="15" {{ (int)request('per_page', 15) === 15 ? 'selected' : '' }}>15 / hal</option>
                        <option value="50" {{ (int)request('per_page', 15) === 50 ? 'selected' : '' }}>50 / hal</option>
                        <option value="100" {{ (int)request('per_page', 15) === 100 ? 'selected' : '' }}>100 / hal</option>
                    </select>
                </div>
                @if(request()->hasAny(['search', 'pool_id']) && (request('pool_id', 'all') !== 'all' || request('search')))
                    <a href="{{ route('dhcp.index', ['tab' => 'static', 'per_page' => $perPage]) }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </form>

            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 dhcp-table">
                    <thead>
                        <tr>
                            <th>Reserved IP</th>
                            <th>MAC Address</th>
                            <th>Hostname / Label</th>
                            <th>Subnet Group</th>
                            <th>Linked Device</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staticLeases as $lease)
                            <tr>
                                <td><span class="ip-tag fw-bold">{{ $lease->ip_address }}</span></td>
                                <td><span class="mac-tag">{{ $lease->mac_address }}</span></td>
                                <td>
                                    <span class="text-white">{{ $lease->hostname ?: '-' }}</span>
                                    @if($lease->description)
                                        <div class="text-secondary small">{{ $lease->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($lease->subnet)
                                        <span class="badge bg-dark border border-secondary">{{ $lease->subnet->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($lease->device)
                                        <span class="text-info"><i class="fa-solid fa-laptop me-1"></i>{{ $lease->device->device_name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($lease->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Disabled</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-info btn-edit-static"
                                            data-id="{{ $lease->id }}"
                                            data-subnet="{{ $lease->dhcp_subnet_id }}"
                                            data-device="{{ $lease->device_id }}"
                                            data-mac="{{ $lease->mac_address }}"
                                            data-ip="{{ $lease->ip_address }}"
                                            data-hostname="{{ $lease->hostname }}"
                                            data-desc="{{ $lease->description }}"
                                            data-active="{{ $lease->is_active ? 1 : 0 }}">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <form action="{{ route('dhcp.static.destroy', $lease->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus reservasi static IP ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">
                                    <i class="fa-solid fa-circle-info fs-3 mb-2 d-block text-muted"></i>
                                    Belum ada Static IP reservation yang sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer (Static Leases) -->
            @if($staticLeases->total() > 0)
            <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top flex-wrap gap-3" style="border-color:#334155 !important;">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="text-secondary small">
                        Showing <strong class="text-light">{{ $staticLeases->firstItem() ?? 0 }}–{{ $staticLeases->lastItem() ?? 0 }}</strong> of <strong class="text-info">{{ $staticLeases->total() }}</strong> reservasi
                    </span>
                    <div class="d-flex align-items-center gap-1.5 ms-2">
                        <span class="text-muted" style="font-size:0.75rem;">Limit:</span>
                        <div class="btn-group btn-group-sm">
                            @foreach([15, 50, 100] as $size)
                                <a href="{{ route('dhcp.index', array_merge(request()->query(), ['tab' => 'static', 'per_page' => $size, 'static_page' => 1])) }}"
                                   class="btn btn-sm {{ (int)request('per_page', 15) === $size ? 'btn-info text-white fw-bold' : 'btn-outline-secondary text-secondary' }}"
                                   style="font-size:0.75rem; padding: 2px 9px;">
                                    {{ $size }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div>
                    @if($staticLeases->hasPages())
                        {{ $staticLeases->links() }}
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- ===================== TAB 3: ACTIVE LEASES ===================== -->
    <div class="tab-pane fade {{ $currentTab === 'leases' ? 'show active' : '' }}" id="leases-pane" role="tabpanel">
        <div class="dhcp-card">
            <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-white"><i class="fa-solid fa-clock-rotate-left text-success me-2"></i>Active Dynamic Leases (Live from Kea)</span>
                    <span class="badge bg-success-subtle text-success border border-success" id="totalActiveClientsBadge">{{ $totalActiveLeases ?? $activeLeases->total() }} Active Clients</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge d-inline-flex align-items-center" id="livePollingIndicator" style="background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3);font-size:0.75rem;" title="Pembaruan realtime aktif">
                        <i class="fa-solid fa-circle text-success me-1.5 fa-fade" style="font-size:0.5rem;"></i><span id="liveStatusText">Realtime</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="btnRefreshLeasesNow" title="Segarkan data leases sekarang" style="font-size:0.75rem; height:26px;">
                        <i class="fa-solid fa-arrows-rotate me-1"></i>Refresh
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2.5 d-inline-flex align-items-center gap-1.5"
                            data-bs-toggle="modal" data-bs-target="#clearLeaseModal"
                            title="Bersihkan dynamic leases yang menumpuk / pool penuh" style="font-size:0.75rem; height:26px;">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>Clear Leases</span>
                    </button>
                </div>
            </div>

            <!-- Filter & Search Toolbar (Active Leases) dengan Tombol Pilihan Pool yang Rapi -->
            <form method="GET" action="{{ route('dhcp.index') }}" class="p-3 border-bottom border-secondary" style="background:rgba(15,23,42,0.4);">
                <input type="hidden" name="tab" value="leases">
                <input type="hidden" name="pool_id" value="{{ request('pool_id', 'all') }}">

                <!-- Row Search & Controls -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width:400px; min-width:220px;">
                        <div class="input-group input-group-sm w-100">
                            <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" name="search" class="form-control form-control-dark form-control-sm"
                                   placeholder="Cari IP, MAC, host..."
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <div class="d-flex align-items-center gap-1.5">
                            <label class="text-secondary small text-nowrap mb-0" style="font-size:0.78rem;">Limit:</label>
                            <select name="per_page" class="form-select form-control-dark form-select-sm" style="width:115px;" onchange="this.form.submit()">
                                <option value="15" {{ (int)request('per_page', 15) === 15 ? 'selected' : '' }}>15 / hal</option>
                                <option value="50" {{ (int)request('per_page', 15) === 50 ? 'selected' : '' }}>50 / hal</option>
                                <option value="100" {{ (int)request('per_page', 15) === 100 ? 'selected' : '' }}>100 / hal</option>
                            </select>
                        </div>
                        @if(request()->hasAny(['search', 'pool_id']) && (request('pool_id', 'all') !== 'all' || request('search')))
                            <a href="{{ route('dhcp.index', ['tab' => 'leases', 'per_page' => $perPage]) }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                                <i class="fa-solid fa-rotate-left me-1"></i>Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 dhcp-table">
                    <thead>
                        <tr>
                            <th>Leased IP</th>
                            <th>Subnet Pool</th>
                            <th>Client MAC Address</th>
                            <th>Client Hostname</th>
                            <th>Lease Expiration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="activeLeasesTbody">
                        @forelse($activeLeases as $al)
                            <tr>
                                <td><span class="ip-tag fw-bold">{{ $al['ip'] }}</span></td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-info">
                                        {{ $al['subnet_name'] }}
                                        @if(!empty($al['vlan_id']))
                                            <span class="text-secondary">(VLAN {{ $al['vlan_id'] }})</span>
                                        @endif
                                    </span>
                                </td>
                                <td><span class="mac-tag">{{ $al['mac'] }}</span></td>
                                <td><span class="text-light">{{ $al['hostname'] }}</span></td>
                                <td><span class="text-secondary small font-monospace">{{ $al['expire_at'] }}</span></td>
                                <td>
                                    @if($al['is_active'])
                                        <span class="badge bg-success-subtle text-success border border-success">Valid</span>
                                    @else
                                        <span class="badge bg-secondary">Expired</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-secondary">
                                    <i class="fa-solid fa-network-wired fs-3 mb-2 d-block text-muted"></i>
                                    Belum ada active dynamic lease yang sesuai filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer (Active Leases) -->
            @if($activeLeases->total() > 0)
            <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top flex-wrap gap-3" style="border-color:#334155 !important;">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="text-secondary small">
                        Showing <strong class="text-light">{{ $activeLeases->firstItem() ?? 0 }}–{{ $activeLeases->lastItem() ?? 0 }}</strong> of <strong class="text-info">{{ $activeLeases->total() }}</strong> active clients
                    </span>
                    <div class="d-flex align-items-center gap-1.5 ms-2">
                        <span class="text-muted" style="font-size:0.75rem;">Limit:</span>
                        <div class="btn-group btn-group-sm">
                            @foreach([15, 50, 100] as $size)
                                <a href="{{ route('dhcp.index', array_merge(request()->query(), ['tab' => 'leases', 'per_page' => $size, 'lease_page' => 1])) }}"
                                   class="btn btn-sm {{ (int)request('per_page', 15) === $size ? 'btn-info text-white fw-bold' : 'btn-outline-secondary text-secondary' }}"
                                   style="font-size:0.75rem; padding: 2px 9px;">
                                    {{ $size }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div>
                    @if($activeLeases->hasPages())
                        {{ $activeLeases->links() }}
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- ===================== MODAL: TAMBAH SUBNET ===================== -->
<div class="modal fade" id="addSubnetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border border-secondary text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-plus text-info me-2"></i>Tambah Subnet DHCP</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('dhcp.subnets.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nama Subnet *</label>
                            <input type="text" name="name" class="form-control form-control-dark" placeholder="e.g. VLAN 20 - Management" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">VLAN ID (optional)</label>
                            <input type="number" name="vlan_id" class="form-control form-control-dark" placeholder="e.g. 20" min="1" max="4094">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Network Subnet (CIDR) *</label>
                            <input type="text" name="subnet" class="form-control form-control-dark font-monospace" placeholder="e.g. 192.168.20.0/24" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pool Start IP <span class="text-secondary small fw-normal">(Opsional jika Static-Only)</span></label>
                            <input type="text" name="pool_start" class="form-control form-control-dark font-monospace" placeholder="e.g. 192.168.20.50 (kosongkan jika static only)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pool End IP <span class="text-secondary small fw-normal">(Opsional)</span></label>
                            <input type="text" name="pool_end" class="form-control form-control-dark font-monospace" placeholder="e.g. 192.168.20.200">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Gateway *</label>
                            <input type="text" name="gateway" class="form-control form-control-dark font-monospace" placeholder="e.g. 192.168.20.1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lease Time (detik)</label>
                            <input type="number" name="lease_time" class="form-control form-control-dark" value="86400" min="300" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">DNS Servers</label>
                            <input type="text" name="dns_servers" class="form-control form-control-dark" value="1.1.1.1, 8.8.8.8">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan (opsional)</label>
                            <textarea name="description" class="form-control form-control-dark" rows="2" placeholder="Catatan segmen jaringan"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" id="addSubnetActive" value="1" checked>
                                <label class="form-check-label text-light small" for="addSubnetActive">Subnet Aktif (Melayani DHCP Pool)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info"><i class="fa-solid fa-save me-1"></i>Simpan Subnet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MODAL: EDIT SUBNET ===================== -->
<div class="modal fade" id="editSubnetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border border-secondary text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-pen text-info me-2"></i>Edit Subnet DHCP</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSubnetForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nama Subnet *</label>
                            <input type="text" name="name" id="editSubnetName" class="form-control form-control-dark" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">VLAN ID</label>
                            <input type="number" name="vlan_id" id="editSubnetVlan" class="form-control form-control-dark" min="1" max="4094">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Network Subnet (CIDR) *</label>
                            <input type="text" name="subnet" id="editSubnetCidr" class="form-control form-control-dark font-monospace" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pool Start IP <span class="text-secondary small fw-normal">(Opsional jika Static-Only)</span></label>
                            <input type="text" name="pool_start" id="editSubnetStart" class="form-control form-control-dark font-monospace" placeholder="Kosongkan jika static only">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pool End IP <span class="text-secondary small fw-normal">(Opsional)</span></label>
                            <input type="text" name="pool_end" id="editSubnetEnd" class="form-control form-control-dark font-monospace">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Gateway *</label>
                            <input type="text" name="gateway" id="editSubnetGateway" class="form-control form-control-dark font-monospace" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lease Time (detik)</label>
                            <input type="number" name="lease_time" id="editSubnetLease" class="form-control form-control-dark" min="300" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">DNS Servers</label>
                            <input type="text" name="dns_servers" id="editSubnetDns" class="form-control form-control-dark">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan</label>
                            <textarea name="description" id="editSubnetDesc" class="form-control form-control-dark" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" id="editSubnetActive" value="1">
                                <label class="form-check-label text-light small" for="editSubnetActive">Subnet Aktif (Melayani DHCP Pool)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info"><i class="fa-solid fa-save me-1"></i>Update Subnet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MODAL: TAMBAH STATIC LEASE ===================== -->
<div class="modal fade" id="addStaticModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border border-secondary text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-thumbtack text-info me-2"></i>Reservasi Static IP</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('dhcp.static.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Subnet Jaringan *</label>
                            <select name="dhcp_subnet_id" class="form-select form-control-dark" required>
                                <option value="">-- Pilih Subnet --</option>
                                @foreach($subnets as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->subnet }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Pilih Device Terdaftar <span class="text-muted fw-normal">(opsional)</span></span>
                                <span class="text-info small" style="font-size:0.75rem;"><i class="fa-solid fa-keyboard me-1"></i>Bisa ketik nama / MAC</span>
                            </label>

                            <!-- Live Search Input Box -->
                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-secondary text-info"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" id="deviceSearchInput" class="form-control form-control-dark" placeholder="Ketik nama device, MAC address, atau lokasi untuk mencari..." autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" id="clearDeviceSearch" title="Clear search"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                                <input type="hidden" name="device_id" id="selectedDeviceId" value="">

                                <!-- Dropdown Live Results List -->
                                <div id="deviceSearchResults" class="list-group shadow-lg position-absolute w-100 mt-1" style="display:none; max-height:220px; overflow-y:auto; z-index:1060; background:#0f172a; border:1px solid #334155; border-radius:10px;">
                                </div>
                            </div>

                            <!-- Selected Device Chip / Badge -->
                            <div id="selectedDeviceBadge" class="mt-2" style="display:none;">
                                <span class="badge bg-info-subtle text-info border border-info px-2.5 py-1.5 d-inline-flex align-items-center gap-2" style="font-size:0.8rem;">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span id="selectedDeviceName"></span>
                                    <button type="button" class="btn-close btn-close-white ms-1" style="font-size:0.55rem;" id="unselectDeviceBtn" title="Lepas kaitan device"></button>
                                </span>
                            </div>
                            <div class="form-text">Cari perangkat untuk mengisi MAC & Hostname secara otomatis, atau ketik langsung di bawah.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">MAC Address *</label>
                            <input type="text" name="mac_address" id="staticMacInput" class="form-control form-control-dark font-monospace" placeholder="AA:BB:CC:DD:EE:FF" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Static IP Address *</span>
                                <button type="button" class="btn btn-link p-0 text-info small text-decoration-none" id="btnSuggestIpDhcp" style="font-size:0.75rem;">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Suggest IP
                                </button>
                            </label>
                            <input type="text" name="ip_address" id="addStaticIpInput" class="form-control form-control-dark font-monospace" placeholder="e.g. 192.168.20.10" required>
                            <div class="form-text" id="addStaticIpSuggestInfo" style="font-size:0.75rem;"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Hostname / Device Label</label>
                            <input type="text" name="hostname" id="staticHostnameInput" class="form-control form-control-dark" placeholder="e.g. Printer-HQ">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi (opsional)</label>
                            <textarea name="description" class="form-control form-control-dark" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info"><i class="fa-solid fa-save me-1"></i>Simpan Reservasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MODAL: EDIT STATIC LEASE ===================== -->
<div class="modal fade" id="editStaticModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border border-secondary text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-pen text-info me-2"></i>Edit Reservasi Static IP</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editStaticForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Subnet Jaringan *</label>
                            <select name="dhcp_subnet_id" id="editStaticSubnet" class="form-select form-control-dark" required>
                                @foreach($subnets as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->subnet }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Kaitkan Device Terdaftar <span class="text-muted fw-normal">(opsional)</span></span>
                                <span class="text-info small" style="font-size:0.75rem;"><i class="fa-solid fa-keyboard me-1"></i>Bisa ketik nama / MAC</span>
                            </label>
                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-secondary text-info"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" id="editDeviceSearchInput" class="form-control form-control-dark" placeholder="Ketik nama device atau MAC untuk ganti kaitan..." autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" id="editClearDeviceSearch" title="Clear"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                                <input type="hidden" name="device_id" id="editSelectedDeviceId" value="">
                                <div id="editDeviceSearchResults" class="list-group shadow-lg position-absolute w-100 mt-1" style="display:none; max-height:220px; overflow-y:auto; z-index:1060; background:#0f172a; border:1px solid #334155; border-radius:10px;">
                                </div>
                            </div>
                            <div id="editSelectedDeviceBadge" class="mt-2" style="display:none;">
                                <span class="badge bg-info-subtle text-info border border-info px-2.5 py-1.5 d-inline-flex align-items-center gap-2" style="font-size:0.8rem;">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span id="editSelectedDeviceName"></span>
                                    <button type="button" class="btn-close btn-close-white ms-1" style="font-size:0.55rem;" id="editUnselectDeviceBtn" title="Lepas kaitan"></button>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">MAC Address *</label>
                            <input type="text" name="mac_address" id="editStaticMac" class="form-control form-control-dark font-monospace" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Static IP Address *</span>
                                <button type="button" class="btn btn-link p-0 text-info small text-decoration-none" id="btnSuggestIpDhcpEdit" style="font-size:0.75rem;">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Suggest IP
                                </button>
                            </label>
                            <input type="text" name="ip_address" id="editStaticIp" class="form-control form-control-dark font-monospace" required>
                            <div class="form-text" id="editStaticIpSuggestInfo" style="font-size:0.75rem;"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Hostname / Device Label</label>
                            <input type="text" name="hostname" id="editStaticHostname" class="form-control form-control-dark">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" id="editStaticDesc" class="form-control form-control-dark" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="editStaticActive" value="1">
                                <label class="form-check-label" for="editStaticActive">Status Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info"><i class="fa-solid fa-save me-1"></i>Update Reservasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MODAL: IMPORT STATIC IP CSV ===================== -->
<div class="modal fade" id="importStaticModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border border-secondary text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-file-import text-info me-2"></i>Import Reservasi Static IP (CSV)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('dhcp.static.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3 p-3 rounded-3" style="background:rgba(56,189,248,0.06); border:1px dashed rgba(56,189,248,0.3);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold text-info small"><i class="fa-solid fa-circle-info me-1"></i>Format Kolom CSV:</span>
                            <a href="{{ route('dhcp.static.template') }}" class="btn btn-xs btn-outline-info" style="font-size:0.75rem;">
                                <i class="fa-solid fa-download me-1"></i>Download Template CSV
                            </a>
                        </div>
                        <code class="text-secondary small d-block font-monospace mb-2" style="font-size:0.78rem;">
                            mac_address, ip_address, subnet, hostname, description
                        </code>
                        <ul class="text-muted small mb-0 ps-3" style="font-size:0.75rem; line-height:1.6;">
                            <li><code>mac_address</code> & <code>ip_address</code> wajib diisi.</li>
                            <li><code>subnet</code> opsional (nama subnet / VLAN). Jika kosong, sistem otomatis mencocokkan pool dari range IP.</li>
                            <li>Jika MAC sudah ada di daftar Device, otomatis terhubung (link device).</li>
                            <li>Duplikat IP atau MAC di reservasi static akan dilewati secara otomatis.</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pilih File CSV *</label>
                        <input type="file" name="csv_file" class="form-control form-control-dark" accept=".csv,.txt" required>
                        <div class="form-text text-secondary small">Maksimal ukuran file 4MB (.csv)</div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info"><i class="fa-solid fa-upload me-1"></i>Import Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MODAL: CLEAR DYNAMIC LEASES ===================== -->
<div class="modal fade" id="clearLeaseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border border-danger border-opacity-50 text-light shadow-lg">
            <div class="modal-header border-secondary">
                <h5 class="modal-title font-outfit fw-bold text-danger">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Clear Dynamic Leases
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('dhcp.leases.clear') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="p-3 mb-3 rounded-3" style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25);">
                        <div class="d-flex gap-2">
                            <i class="fa-solid fa-circle-exclamation text-danger mt-1"></i>
                            <div class="small text-secondary" style="line-height:1.6;">
                                Tindakan ini akan menghapus catatan <strong>Active Dynamic Lease</strong> dari engine Kea DHCPv4 untuk membebaskan IP Pool yang penuh.
                                <br><br>
                                <span class="text-light"><strong>Catatan Penting:</strong></span>
                                <ul class="mb-0 ps-3 mt-1 text-light">
                                    <li><strong>Reservasi Static IP TIDAK AKAN TERHAPUS</strong> (tetap aman).</li>
                                    <li>Container Kea DHCP akan dimuat ulang seketika.</li>
                                    <li>Perangkat aktif yang terhubung akan meminta renewal IP baru saat siklus sewa habis.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Lingkup Subnet yang Dibersihkan *</label>
                        <select name="pool_id" class="form-select form-control-dark" id="clearLeasePoolSelect" required>
                            <option value="all" {{ $selectedPool === 'all' ? 'selected' : '' }}>
                                🔴 Semua Subnet Pool (Seluruh {{ $totalActiveLeases ?? 0 }} Dynamic Lease Aktif)
                            </option>
                            @foreach($subnets as $s)
                                @php $cLease = $activeLeaseCounts[$s->id] ?? 0; @endphp
                                <option value="{{ $s->id }}" {{ (string)$selectedPool === (string)$s->id ? 'selected' : '' }}>
                                    {{ $s->name }} @if($s->vlan_id) (VLAN {{ $s->vlan_id }}) @endif — {{ $cLease }} Lease Aktif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text text-secondary small">
                            Pilih apakah ingin mengosongkan seluruh dynamic lease, atau hanya subnet VLAN tertentu yang pool-nya sedang penuh.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger fw-bold" onclick="return confirm('Apakah Anda yakin ingin mengosongkan dynamic leases ini? Client yang aktif akan meminta request IP baru.')">
                        <i class="fa-solid fa-trash-can me-1"></i>Hapus Dynamic Leases
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const registeredDevices = @json($devices);

    // Edit Subnet Modal Handler
    document.querySelectorAll('.btn-edit-subnet').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const form = document.getElementById('editSubnetForm');
            form.action = `/dhcp/subnets/${id}`;

            document.getElementById('editSubnetName').value    = this.dataset.name || '';
            document.getElementById('editSubnetVlan').value    = this.dataset.vlan || '';
            document.getElementById('editSubnetCidr').value    = this.dataset.subnet || '';
            document.getElementById('editSubnetStart').value   = this.dataset.start || '';
            document.getElementById('editSubnetEnd').value     = this.dataset.end || '';
            document.getElementById('editSubnetGateway').value = this.dataset.gateway || '';
            document.getElementById('editSubnetDns').value     = this.dataset.dns || '';
            document.getElementById('editSubnetLease').value   = this.dataset.lease || '86400';
            document.getElementById('editSubnetDesc').value    = this.dataset.desc || '';
            document.getElementById('editSubnetActive').checked = (this.dataset.active == '1');

            new bootstrap.Modal(document.getElementById('editSubnetModal')).show();
        });
    });

    // Helper: Setup Searchable Device Picker
    function setupDevicePicker(inputId, resultsId, hiddenId, badgeId, badgeNameId, clearId, unselectId, macTargetId, hostnameTargetId) {
        const input       = document.getElementById(inputId);
        const results     = document.getElementById(resultsId);
        const hidden      = document.getElementById(hiddenId);
        const badge       = document.getElementById(badgeId);
        const badgeName   = document.getElementById(badgeNameId);
        const clearBtn    = document.getElementById(clearId);
        const unselectBtn = document.getElementById(unselectId);
        const macInput    = document.getElementById(macTargetId);
        const hostInput   = document.getElementById(hostnameTargetId);

        if (!input || !results) return;

        function renderResults(query) {
            const q = (query || '').toLowerCase().trim();
            const filtered = registeredDevices.filter(d => {
                const name = (d.device_name || '').toLowerCase();
                const mac  = (d.mac_address || '').toLowerCase();
                const loc  = (d.location || '').toLowerCase();
                const ssid = (d.ssid || '').toLowerCase();
                return name.includes(q) || mac.includes(q) || loc.includes(q) || ssid.includes(q);
            }).slice(0, 20);

            if (filtered.length === 0) {
                results.innerHTML = `
                    <div class="p-3 text-secondary small text-center">
                        <i class="fa-solid fa-circle-question me-1"></i> Tidak ada perangkat yang cocok "${query}"
                    </div>
                `;
                results.style.display = 'block';
                return;
            }

            let html = '';
            filtered.forEach(d => {
                html += `
                    <button type="button" class="list-group-item list-group-item-action text-light border-secondary d-flex justify-content-between align-items-center pick-item"
                            style="background:#0f172a;"
                            data-id="${d.id}" data-name="${d.device_name}" data-mac="${d.mac_address}">
                        <div>
                            <div class="fw-semibold text-white small"><i class="fa-solid fa-laptop text-info me-1.5"></i>${d.device_name}</div>
                            <div class="font-monospace text-secondary" style="font-size:0.75rem;">${d.mac_address} ${d.location ? ' • ' + d.location : ''}</div>
                        </div>
                        <span class="badge bg-dark border border-secondary text-info small" style="font-size:0.7rem;">Pilih</span>
                    </button>
                `;
            });
            results.innerHTML = html;
            results.style.display = 'block';

            results.querySelectorAll('.pick-item').forEach(item => {
                item.addEventListener('click', function () {
                    const id   = this.dataset.id;
                    const name = this.dataset.name;
                    const mac  = this.dataset.mac;

                    if (hidden) hidden.value = id;
                    if (macInput) macInput.value = mac;
                    if (hostInput && (!hostInput.value || hostInput.value.trim() === '')) hostInput.value = name;

                    if (badge && badgeName) {
                        badgeName.textContent = `${name} (${mac})`;
                        badge.style.display = 'block';
                    }
                    input.value = '';
                    results.style.display = 'none';
                });
            });
        }

        input.addEventListener('input', function () {
            if (this.value.trim().length > 0) {
                renderResults(this.value);
            } else {
                results.style.display = 'none';
            }
        });

        input.addEventListener('focus', function () {
            renderResults(this.value);
        });

        document.addEventListener('click', function (e) {
            if (!input.contains(e.target) && !results.contains(e.target)) {
                results.style.display = 'none';
            }
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                input.value = '';
                results.style.display = 'none';
            });
        }

        if (unselectBtn) {
            unselectBtn.addEventListener('click', function () {
                if (hidden) hidden.value = '';
                if (badge) badge.style.display = 'none';
            });
        }
    }

    // Initialize Searchable Pickers for Add and Edit Modals
    setupDevicePicker('deviceSearchInput', 'deviceSearchResults', 'selectedDeviceId', 'selectedDeviceBadge', 'selectedDeviceName', 'clearDeviceSearch', 'unselectDeviceBtn', 'staticMacInput', 'staticHostnameInput');
    setupDevicePicker('editDeviceSearchInput', 'editDeviceSearchResults', 'editSelectedDeviceId', 'editSelectedDeviceBadge', 'editSelectedDeviceName', 'editClearDeviceSearch', 'editUnselectDeviceBtn', 'editStaticMac', 'editStaticHostname');

    // Edit Static Lease Modal Handler
    document.querySelectorAll('.btn-edit-static').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const form = document.getElementById('editStaticForm');
            form.action = `/dhcp/static-leases/${id}`;

            document.getElementById('editStaticSubnet').value   = this.dataset.subnet || '';
            document.getElementById('editStaticMac').value      = this.dataset.mac || '';
            document.getElementById('editStaticIp').value       = this.dataset.ip || '';
            document.getElementById('editStaticHostname').value = this.dataset.hostname || '';
            document.getElementById('editStaticDesc').value     = this.dataset.desc || '';
            document.getElementById('editStaticActive').checked = this.dataset.active == '1';

            const deviceId = this.dataset.device;
            const editHidden = document.getElementById('editSelectedDeviceId');
            const editBadge  = document.getElementById('editSelectedDeviceBadge');
            const editBadgeName = document.getElementById('editSelectedDeviceName');

            if (deviceId && editHidden) {
                editHidden.value = deviceId;
                const dev = registeredDevices.find(d => d.id == deviceId);
                if (dev && editBadge && editBadgeName) {
                    editBadgeName.textContent = `${dev.device_name} (${dev.mac_address})`;
                    editBadge.style.display = 'block';
                }
            } else {
                if (editHidden) editHidden.value = '';
                if (editBadge) editBadge.style.display = 'none';
            }

            new bootstrap.Modal(document.getElementById('editStaticModal')).show();
        });
    });

    // -------------------------------------------------------------
    // Suggest IP Handler in DHCP Static Lease Modals
    // -------------------------------------------------------------
    function handleDhcpSuggestIp(subnetSelectEl, ipInputEl, infoEl, btnEl) {
        if (!subnetSelectEl || !ipInputEl) return;
        const subnetId = subnetSelectEl.value;
        if (!subnetId) {
            if (infoEl) infoEl.innerHTML = '<span class="text-warning">Pilih subnet terlebih dahulu.</span>';
            return;
        }

        if (btnEl) {
            btnEl.disabled = true;
            btnEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Mencari...';
        }
        if (infoEl) infoEl.innerHTML = '<span class="text-secondary"><i class="fa-solid fa-spinner fa-spin me-1"></i>Mencari IP kosong...</span>';

        fetch(`/dhcp/suggest-ip?subnet_id=${subnetId}`)
            .then(res => res.json())
            .then(data => {
                if (data.suggested_ip) {
                    ipInputEl.value = data.suggested_ip;
                    if (infoEl) {
                        const note = data.is_static_only ? '(Static-only subnet)' : '(Pool range)';
                        infoEl.innerHTML = `<span class="text-success"><i class="fa-solid fa-check me-1"></i>IP disarankan: <strong>${data.suggested_ip}</strong> <span class="text-muted" style="font-size:0.7rem;">${note}</span></span>`;
                    }
                } else {
                    if (infoEl) infoEl.innerHTML = '<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>Tidak ada IP kosong di subnet ini.</span>';
                }
            })
            .catch(() => {
                if (infoEl) infoEl.innerHTML = '<span class="text-danger">Gagal menghubungi server.</span>';
            })
            .finally(() => {
                if (btnEl) {
                    btnEl.disabled = false;
                    btnEl.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles me-1"></i>Suggest IP';
                }
            });
    }

    const addSubnetSelect = document.querySelector('select[name="dhcp_subnet_id"]');
    const addStaticIp = document.getElementById('addStaticIpInput');
    const addStaticInfo = document.getElementById('addStaticIpSuggestInfo');
    const btnSuggestAdd = document.getElementById('btnSuggestIpDhcp');

    if (btnSuggestAdd) {
        btnSuggestAdd.addEventListener('click', () => handleDhcpSuggestIp(addSubnetSelect, addStaticIp, addStaticInfo, btnSuggestAdd));
    }
    if (addSubnetSelect) {
        addSubnetSelect.addEventListener('change', () => {
            if (!addStaticIp.value) {
                handleDhcpSuggestIp(addSubnetSelect, addStaticIp, addStaticInfo, btnSuggestAdd);
            }
        });
    }

    const editSubnetSelect = document.getElementById('editStaticSubnet');
    const editStaticIp = document.getElementById('editStaticIp');
    const editStaticInfo = document.getElementById('editStaticIpSuggestInfo');
    const btnSuggestEdit = document.getElementById('btnSuggestIpDhcpEdit');

    if (btnSuggestEdit) {
        btnSuggestEdit.addEventListener('click', () => handleDhcpSuggestIp(editSubnetSelect, editStaticIp, editStaticInfo, btnSuggestEdit));
    }

    // -------------------------------------------------------------
    // Realtime Auto-Polling for Active Dynamic Leases (Every 5 seconds)
    // -------------------------------------------------------------
    let leasesPollTimer = null;
    let isFetchingLeases = false;

    function fetchLiveActiveLeases() {
        if (isFetchingLeases) return;
        const tbody = document.getElementById('activeLeasesTbody');
        if (!tbody) return;

        // Ambil query parameters aktif saat ini
        const urlParams = new URLSearchParams(window.location.search);
        const poolId = urlParams.get('pool_id') || 'all';
        const searchVal = urlParams.get('search') || '';
        const perPage = urlParams.get('per_page') || '15';
        const leasePage = urlParams.get('lease_page') || '1';

        const liveUrl = `/dhcp/leases/live?pool_id=${encodeURIComponent(poolId)}&search=${encodeURIComponent(searchVal)}&per_page=${encodeURIComponent(perPage)}&lease_page=${encodeURIComponent(leasePage)}`;

        isFetchingLeases = true;
        const statusText = document.getElementById('liveStatusText');

        fetch(liveUrl)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    tbody.innerHTML = data.html_rows;

                    // 1. Update badge di atas tabel (Active Clients)
                    const totalBadge = document.getElementById('totalActiveClientsBadge');
                    if (totalBadge) {
                        totalBadge.textContent = `${data.total_active} Active Clients`;
                    }

                    // 2. Update Card Besar di atas (Active Dynamic Leases: [X] Online)
                    const statBig = document.getElementById('statActiveDynamicLeases');
                    if (statBig) {
                        statBig.textContent = data.total_active;
                    }

                    // 3. Update Tombol Pool di Navigasi Baris Atas (#poolTabBar)
                    const poolAll = document.getElementById('poolLiveAll');
                    if (poolAll) {
                        poolAll.textContent = `${data.total_active} Live`;
                    }

                    if (data.active_counts) {
                        for (const [subId, count] of Object.entries(data.active_counts)) {
                            const subPill = document.getElementById(`poolLiveSubnet_${subId}`);
                            if (subPill) {
                                subPill.textContent = `${count} Live`;
                                subPill.style.display = count > 0 ? '' : 'none';
                            }

                            // 4. Update Tombol Subnet di Toolbar Filter dalam Tab Leases
                            const innerSubPill = document.getElementById(`innerPoolLive_${subId}`);
                            if (innerSubPill) {
                                innerSubPill.textContent = count;
                            }
                        }
                    }

                    const innerAll = document.getElementById('innerPoolLiveAll');
                    if (innerAll) {
                        innerAll.textContent = data.total_active;
                    }

                    // 5. Update status realtime indicator (tetap bersih bertuliskan 'Realtime', jam ditaruh di title tooltip)
                    const indicator = document.getElementById('livePollingIndicator');
                    if (indicator) {
                        indicator.setAttribute('title', `Pembaruan realtime aktif (Terakhir disinkronkan pukul ${data.timestamp})`);
                    }
                    if (statusText) {
                        statusText.textContent = 'Realtime';
                    }
                }
            })
            .catch(err => {
                console.warn('Live leases sync error:', err);
                if (statusText) statusText.textContent = 'Offline';
            })
            .finally(() => {
                isFetchingLeases = false;
            });
    }

    function startLeasesPolling() {
        if (!leasesPollTimer) {
            leasesPollTimer = setInterval(fetchLiveActiveLeases, 5000);
        }
    }

    function stopLeasesPolling() {
        if (leasesPollTimer) {
            clearInterval(leasesPollTimer);
            leasesPollTimer = null;
        }
    }

    // Cek apakah tab saat ini adalah leases
    const currentActiveTab = '{{ $currentTab }}';
    if (currentActiveTab === 'leases') {
        startLeasesPolling();
    }

    // Listen tab switch
    document.querySelectorAll('.nav-tabs-dhcp .nav-link').forEach(tabLink => {
        tabLink.addEventListener('click', function() {
            const href = this.getAttribute('href') || '';
            if (href.includes('tab=leases')) {
                startLeasesPolling();
            } else {
                stopLeasesPolling();
            }
        });
    });

    // Manual refresh button
    const btnManualRefresh = document.getElementById('btnRefreshLeasesNow');
    if (btnManualRefresh) {
        btnManualRefresh.addEventListener('click', function() {
            btnManualRefresh.classList.add('disabled');
            const icon = btnManualRefresh.querySelector('i');
            if (icon) icon.classList.add('fa-spin');

            fetchLiveActiveLeases();

            setTimeout(() => {
                btnManualRefresh.classList.remove('disabled');
                if (icon) icon.classList.remove('fa-spin');
            }, 800);
        });
    }
});
</script>
@endpush
@endsection
