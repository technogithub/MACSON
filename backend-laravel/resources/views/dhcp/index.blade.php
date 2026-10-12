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
                <div class="fs-4 fw-bold text-white">{{ $staticLeases->count() }} <span class="fs-6 fw-normal text-muted">Reserved</span></div>
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
                <div class="fs-4 fw-bold text-white">{{ count($activeLeases) }} <span class="fs-6 fw-normal text-muted">Online</span></div>
            </div>
        </div>
    </div>
</div>

<!-- ===================== TABS NAVIGATION ===================== -->
@php
    $currentTab = request('tab', 'subnets');
@endphp
<ul class="nav nav-tabs nav-tabs-dhcp mb-3" id="dhcpTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link {{ $currentTab === 'subnets' ? 'active' : '' }}" id="subnets-tab" data-bs-toggle="tab" data-bs-target="#subnets-pane" type="button">
            <i class="fa-solid fa-layer-group"></i> Subnets & IP Pools
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link {{ $currentTab === 'static' ? 'active' : '' }}" id="static-tab" data-bs-toggle="tab" data-bs-target="#static-pane" type="button">
            <i class="fa-solid fa-thumbtack"></i> Static IP Reservations
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link {{ $currentTab === 'leases' ? 'active' : '' }}" id="leases-tab" data-bs-toggle="tab" data-bs-target="#leases-pane" type="button">
            <i class="fa-solid fa-clock-rotate-left"></i> Active Leases
        </button>
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
                                    <div class="fw-bold text-white">{{ $subnet->name }}</div>
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
                                    <span class="text-light">{{ $subnet->pool_start }}</span>
                                    <span class="text-muted mx-1">➔</span>
                                    <span class="text-light">{{ $subnet->pool_end }}</span>
                                </td>
                                <td><span class="text-info font-monospace">{{ $subnet->gateway }}</span></td>
                                <td><span class="text-secondary small">{{ $subnet->dns_servers }}</span></td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-light">
                                        {{ $subnet->total_pool_ips }} IPs
                                    </span>
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
            <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-white"><i class="fa-solid fa-thumbtack me-2"></i>Daftar Static IP Reservations</span>
                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#addStaticModal">
                    <i class="fa-solid fa-plus me-1"></i>Reservasi IP Baru
                </button>
            </div>
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
                                    Belum ada Static IP reservation. Klik <strong>Reservasi IP Baru</strong> untuk memetakan MAC ke IP tertentu.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================== TAB 3: ACTIVE LEASES ===================== -->
    <div class="tab-pane fade {{ $currentTab === 'leases' ? 'show active' : '' }}" id="leases-pane" role="tabpanel">
        <div class="dhcp-card">
            <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-white"><i class="fa-solid fa-clock-rotate-left me-2"></i>Active Dynamic Leases (Live from Kea)</span>
                <span class="badge bg-secondary">{{ count($activeLeases) }} Active Clients</span>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 dhcp-table">
                    <thead>
                        <tr>
                            <th>Leased IP</th>
                            <th>Client MAC Address</th>
                            <th>Client Hostname</th>
                            <th>Lease Expiration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activeLeases as $al)
                            <tr>
                                <td><span class="ip-tag">{{ $al['ip'] }}</span></td>
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
                                <td colspan="5" class="text-center py-4 text-secondary">
                                    <i class="fa-solid fa-network-wired fs-3 mb-2 d-block text-muted"></i>
                                    Belum ada active dynamic lease yang tercatat saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
                            <label class="form-label">Pool Start IP *</label>
                            <input type="text" name="pool_start" class="form-control form-control-dark font-monospace" placeholder="e.g. 192.168.20.50" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pool End IP *</label>
                            <input type="text" name="pool_end" class="form-control form-control-dark font-monospace" placeholder="e.g. 192.168.20.200" required>
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
                            <label class="form-label">Pool Start IP *</label>
                            <input type="text" name="pool_start" id="editSubnetStart" class="form-control form-control-dark font-monospace" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pool End IP *</label>
                            <input type="text" name="pool_end" id="editSubnetEnd" class="form-control form-control-dark font-monospace" required>
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
                            <label class="form-label">Pilih Device Terdaftar <span class="text-muted fw-normal">(opsional)</span></label>
                            <select id="pickDeviceSelect" name="device_id" class="form-select form-control-dark">
                                <option value="">-- Manual Input / Tidak Dikaitkan --</option>
                                @foreach($devices as $d)
                                    <option value="{{ $d->id }}" data-mac="{{ $d->mac_address }}" data-name="{{ $d->device_name }}">
                                        {{ $d->device_name }} ({{ $d->mac_address }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">MAC Address *</label>
                            <input type="text" name="mac_address" id="staticMacInput" class="form-control form-control-dark font-monospace" placeholder="AA:BB:CC:DD:EE:FF" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Static IP Address *</label>
                            <input type="text" name="ip_address" class="form-control form-control-dark font-monospace" placeholder="e.g. 192.168.20.10" required>
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
                        <div class="col-md-6">
                            <label class="form-label">MAC Address *</label>
                            <input type="text" name="mac_address" id="editStaticMac" class="form-control form-control-dark font-monospace" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Static IP Address *</label>
                            <input type="text" name="ip_address" id="editStaticIp" class="form-control form-control-dark font-monospace" required>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
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

            new bootstrap.Modal(document.getElementById('editSubnetModal')).show();
        });
    });

    // Auto-fill MAC & Hostname saat memilih Device terdaftar
    const pickDevice = document.getElementById('pickDeviceSelect');
    if (pickDevice) {
        pickDevice.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            if (selected.value) {
                document.getElementById('staticMacInput').value = selected.dataset.mac || '';
                document.getElementById('staticHostnameInput').value = selected.dataset.name || '';
            }
        });
    }

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

            new bootstrap.Modal(document.getElementById('editStaticModal')).show();
        });
    });
});
</script>
@endpush
@endsection
