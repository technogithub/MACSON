@extends('layouts.app')

@section('title', 'UniFi Voucher Management')

@section('content')
<style>
/* ===== UNIFI VOUCHER PAGE - PREMIUM DARK STYLES ===== */
.page-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
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
    background: radial-gradient(circle, rgba(99,102,241,0.12) 0%, transparent 70%);
    pointer-events: none;
}
.search-input {
    background: #0f172a !important;
    border: 1px solid #334155 !important;
    border-radius: 10px !important;
    color: #f1f5f9 !important;
    height: 44px;
    font-size: 0.9rem;
}
.search-input:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.15) !important;
}
.voucher-card {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 16px;
    overflow: hidden;
}
.voucher-table thead th {
    background: #0f172a;
    border-color: #334155;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 14px 16px;
}
.voucher-table tbody td {
    padding: 14px 16px;
    border-color: #334155;
    vertical-align: middle;
}
.voucher-code-badge {
    font-family: 'JetBrains Mono', 'Courier New', monospace;
    font-size: 0.92rem;
    font-weight: 700;
    color: #818cf8;
    background: rgba(99,102,241,0.12);
    border: 1px solid rgba(99,102,241,0.3);
    border-radius: 8px;
    padding: 5px 12px;
    letter-spacing: 0.08em;
}
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
.btn-gen-voucher {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border: none;
    border-radius: 10px;
    padding: 8px 18px;
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
    box-shadow: 0 4px 15px rgba(99,102,241,0.3);
}
.btn-gen-voucher:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99,102,241,0.4);
    color: white;
}
</style>

<!-- Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1" style="color:#f1f5f9;">
                <i class="fa-solid fa-wifi text-indigo me-2" style="color:#818cf8;"></i>SJA SEMARANG HOTSPOT
            </h3>
            <p class="text-secondary small mb-0">Generate, manage, and print guest Wi-Fi hotspot vouchers for UniFi Controller</p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <!-- Sync Now Button -->
            <form action="{{ route('vouchers.sync') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-info btn-sm" title="Sync Pending Vouchers with UniFi Controller">
                    <i class="fa-solid fa-rotate me-1"></i> Sync UniFi Now
                </button>
            </form>
            @if(auth()->user()->isSuperAdmin())
            <!-- Controller Config Button -->
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#configModal">
                <i class="fa-solid fa-gear me-1"></i> UniFi Controller Config
            </button>
            @endif
            <!-- Print Unused Vouchers -->
            <a href="{{ route('vouchers.print') }}" target="_blank" class="btn btn-outline-warning btn-sm">
                <i class="fa-solid fa-print me-1"></i> Print Vouchers
            </a>
            <!-- Generate Voucher Button -->
            <button class="btn-gen-voucher btn" data-bs-toggle="modal" data-bs-target="#createVoucherModal">
                <i class="fa-solid fa-plus me-1"></i> Generate Vouchers
            </button>
        </div>
    </div>
</div>

<!-- Stats Strip -->
<div class="stats-strip">
    <div class="stat-chip">
        <i class="fa-solid fa-ticket text-info"></i>
        <span>Total:</span>
        <span class="num text-info">{{ $stats['total'] }}</span>
    </div>
    <div class="stat-chip">
        <i class="fa-solid fa-circle-check text-success"></i>
        <span>Unused:</span>
        <span class="num text-success">{{ $stats['unused'] }}</span>
    </div>
    <div class="stat-chip">
        <i class="fa-solid fa-user-check" style="color:#818cf8;"></i>
        <span>Used:</span>
        <span class="num" style="color:#818cf8;">{{ $stats['used'] }}</span>
    </div>
    <div class="stat-chip">
        <i class="fa-solid fa-ban text-danger"></i>
        <span>Revoked:</span>
        <span class="num text-danger">{{ $stats['revoked'] }}</span>
    </div>
</div>

<!-- Search & Filter Bar -->
<form method="GET" action="{{ route('vouchers.index') }}" class="d-flex gap-3 mb-3 flex-wrap align-items-center">
    <div class="flex-grow-1" style="min-width:240px; max-width:420px;">
        <input type="text" name="search" class="form-control search-input"
               placeholder="Search Voucher Code, Note, or Batch ID..."
               value="{{ request('search') }}" autocomplete="off">
    </div>
    <select name="status" class="form-select search-input" style="width:170px;" onchange="this.form.submit()">
        <option value="active" {{ request('status','active') === 'active' ? 'selected' : '' }}>Active Vouchers</option>
        <option value="unused" {{ request('status') === 'unused' ? 'selected' : '' }}>Unused Only</option>
        <option value="used" {{ request('status') === 'used' ? 'selected' : '' }}>Used Only</option>
        <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>Revoked Only</option>
        <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Status</option>
    </select>
    @if(request()->hasAny(['search','status']))
        <a href="{{ route('vouchers.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    @endif
</form>

<!-- Vouchers Table -->
<form id="batchRevokeForm" action="{{ route('vouchers.batchRevoke') }}" method="POST">
    @csrf
    <div class="voucher-card">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-secondary" id="batchActionBar" style="display: none !important; background: rgba(239,68,68,0.08);">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-square-check text-danger fs-5"></i>
                <span class="fw-semibold text-light small"><span id="selectedCount">0</span> Voucher(s) Selected</span>
            </div>
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Revoke all selected vouchers?')">
                <i class="fa-solid fa-ban me-1"></i> Revoke Selected Vouchers
            </button>
        </div>
        <div class="table-responsive">
            <table class="table voucher-table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:40px;" class="text-center">
                            <input type="checkbox" id="selectAllVouchers" class="form-check-input" title="Select All Vouchers">
                        </th>
                        <th>Voucher Code</th>
                        <th>Duration</th>
                        <th>Quota Limit</th>
                        <th>Speed Limit</th>
                        <th>Batch / Note</th>
                        <th>Status</th>
                        <th>UniFi Sync</th>
                        <th>Created At</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $v)
                        <tr>
                            <td class="text-center">
                                @if($v->status === 'unused')
                                    <input type="checkbox" name="voucher_ids[]" value="{{ $v->id }}" class="form-check-input voucher-checkbox">
                                @else
                                    <input type="checkbox" disabled class="form-check-input opacity-25">
                                @endif
                            </td>
                            <td>
                                <span class="voucher-code-badge">{{ $v->formatted_code }}</span>
                            </td>
                            <td>
                                <span class="fw-semibold text-light">
                                    @if($v->duration_minutes >= 1440)
                                        {{ round($v->duration_minutes / 1440, 1) }} Day(s)
                                    @elseif($v->duration_minutes >= 60)
                                        {{ round($v->duration_minutes / 60, 1) }} Hour(s)
                                    @else
                                        {{ $v->duration_minutes }} Min(s)
                                    @endif
                                </span>
                            </td>
                            <td>
                                @if($v->quota_mb)
                                    <span class="badge bg-secondary">{{ $v->quota_mb }} MB</span>
                                @else
                                    <span class="text-muted small">Unlimited</span>
                                @endif
                            </td>
                            <td>
                                @if($v->down_kbps || $v->up_kbps)
                                    <span class="badge bg-info-subtle text-info">
                                        ↓ {{ $v->down_kbps ? round($v->down_kbps/1024,1).'M' : '∞' }} / 
                                        ↑ {{ $v->up_kbps ? round($v->up_kbps/1024,1).'M' : '∞' }}
                                    </span>
                                @else
                                    <span class="text-muted small">Full Speed</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-light small">{{ $v->note ?? 'No Note' }}</div>
                                <div class="text-secondary" style="font-size:0.75rem;">{{ $v->batch_id }}</div>
                            </td>
                            <td>
                                @if($v->status === 'unused')
                                    <span class="badge bg-success" style="font-size:0.75rem;">Unused</span>
                                @elseif($v->status === 'used')
                                    <span class="badge bg-primary" style="font-size:0.75rem;">Used</span>
                                @elseif($v->status === 'revoked')
                                    <span class="badge bg-danger" style="font-size:0.75rem;">Revoked</span>
                                @else
                                    <span class="badge bg-secondary" style="font-size:0.75rem;">Expired</span>
                                @endif
                            </td>
                            <td>
                                @if(($v->sync_status ?? 'synced') === 'synced')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.72rem;"><i class="fa-solid fa-cloud-check me-1"></i>Synced</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:0.72rem;" title="Pending Sync to UniFi Controller"><i class="fa-solid fa-clock-rotate-left me-1"></i>Pending Sync</span>
                                @endif
                            </td>
                            <td class="text-secondary small">
                                {{ $v->created_at ? $v->created_at->format('Y-m-d H:i') : '—' }}
                            </td>
                            <td class="text-center">
                                @if($v->status === 'unused')
                                    <button type="submit" form="singleRevokeForm{{ $v->id }}" class="btn btn-sm btn-outline-danger" title="Revoke Voucher" onclick="return confirm('Revoke voucher {{ $v->code }}?')">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="text-muted mb-2" style="font-size:2rem;"><i class="fa-solid fa-ticket text-secondary"></i></div>
                                <p class="fw-semibold text-secondary mb-1">No Active UniFi Vouchers Found</p>
                                <p class="text-muted small">All active vouchers are clean. Select "All Status" or "Revoked" to view revoked history.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vouchers->hasPages())
            <div class="px-4 py-3 border-top" style="border-color:#334155 !important;">
                {{ $vouchers->links() }}
            </div>
        @endif
    </div>
</form>

<!-- Forms for single voucher revocation -->
@foreach($vouchers as $v)
    @if($v->status === 'unused')
        <form id="singleRevokeForm{{ $v->id }}" action="{{ route('vouchers.destroy', $v->id) }}" method="POST" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAllVouchers');
    const checkboxes = document.querySelectorAll('.voucher-checkbox');
    const batchBar = document.getElementById('batchActionBar');
    const countDisplay = document.getElementById('selectedCount');

    function updateBatchBar() {
        const checked = document.querySelectorAll('.voucher-checkbox:checked');
        const count = checked.length;
        if (count > 0) {
            batchBar.style.setProperty('display', 'flex', 'important');
            countDisplay.textContent = count;
        } else {
            batchBar.style.setProperty('display', 'none', 'important');
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBatchBar();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            if (!this.checked && selectAll) {
                selectAll.checked = false;
            }
            updateBatchBar();
        });
    });
});
</script>

<!-- MODAL: Generate Vouchers -->
<div class="modal fade" id="createVoucherModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-ticket text-indigo me-2"></i>Batch Generate UniFi Vouchers</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('vouchers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Quantity (Count) *</label>
                            <input type="number" name="count" class="form-control form-control-dark" value="1" min="1" max="500" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Duration *</label>
                            <div class="input-group">
                                <input type="number" name="duration_value" class="form-control form-control-dark" value="1" min="1" required>
                                <select name="duration_unit" class="form-select form-control-dark" style="max-width: 110px;">
                                    <option value="days" selected>Day(s)</option>
                                    <option value="hours">Hour(s)</option>
                                    <option value="minutes">Minute(s)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data Limit (MB) <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="number" name="quota_mb" class="form-control form-control-dark" placeholder="e.g. 1024 for 1GB">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Allowed Uses *</label>
                            <input type="number" name="use_limit" class="form-control form-control-dark" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Download Speed (Kbps)</label>
                            <input type="number" name="down_kbps" class="form-control form-control-dark" placeholder="e.g. 10240 (10 Mbps)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload Speed (Kbps)</label>
                            <input type="number" name="up_kbps" class="form-control form-control-dark" placeholder="e.g. 5120 (5 Mbps)">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Voucher Tag / Note</label>
                            <input type="text" name="note" class="form-control form-control-dark" placeholder="e.g. Guest VIP Event 2026">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-gen-voucher btn">Generate Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Controller Config -->
<div class="modal fade" id="configModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-gear text-info me-2"></i>UniFi Controller Connection</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('vouchers.config') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">UniFi Controller URL *</label>
                            <input type="url" name="controller_url" class="form-control form-control-dark" value="{{ $config->controller_url }}" placeholder="https://192.168.1.1:8443" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Site ID *</label>
                            <input type="text" name="site_id" class="form-control form-control-dark" value="{{ $config->site_id }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Admin Username *</label>
                            <input type="text" name="username" class="form-control form-control-dark" value="{{ $config->username }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Admin Password *</label>
                            <input type="password" name="password" class="form-control form-control-dark" value="{{ $config->password }}" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="verify_ssl" id="verifySsl" {{ $config->verify_ssl ? 'checked' : '' }}>
                                <label class="form-check-label small" for="verifySsl">Verify SSL Certificate (Uncheck for self-signed SSL)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
