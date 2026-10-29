@extends('layouts.app')

@section('title', 'Blacklist Guest')

@section('content')
<style>
.blacklist-card {
    background: var(--macson-card-bg);
    border: 1px solid var(--macson-border);
    border-radius: 16px;
    padding: 24px;
    backdrop-filter: blur(12px);
    transition: all 0.2s ease;
}
.stat-card {
    background: var(--macson-card-bg);
    border: 1px solid var(--macson-border);
    border-radius: 14px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.2s, border-color 0.2s;
}
.stat-card:hover {
    transform: translateY(-2px);
    border-color: var(--macson-border-hover);
}
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}
.stat-val {
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1.1;
    font-family: 'Outfit', sans-serif;
}
.stat-lbl {
    font-size: 0.78rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}
.mac-badge {
    font-family: 'Courier New', monospace;
    font-weight: 700;
    font-size: 0.92rem;
    color: #f43f5e;
    background: rgba(244, 63, 94, 0.1);
    border: 1px solid rgba(244, 63, 94, 0.25);
    padding: 4px 10px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-block-mac {
    background: linear-gradient(135deg, #f43f5e, #e11d48);
    border: none;
    border-radius: 10px;
    padding: 9px 20px;
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
    transition: all 0.2s;
    box-shadow: 0 4px 15px rgba(244, 63, 94, 0.3);
}
.btn-block-mac:hover {
    background: linear-gradient(135deg, #fb7185, #f43f5e);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(244, 63, 94, 0.4);
}
.info-banner {
    background: rgba(15, 23, 42, 0.6);
    border-left: 4px solid #f43f5e;
    border-radius: 8px;
    padding: 14px 18px;
    font-size: 0.85rem;
    color: #cbd5e1;
}
</style>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold font-outfit m-0 text-light d-flex align-items-center gap-2">
            <i class="fa-solid fa-ban text-danger"></i>
            Blacklist Perangkat Tamu (Guest)
        </h3>
        <p class="text-secondary small m-0 mt-1">
            Perangkat di daftar ini akan ditolak seketika di Layer 2 (Access-Reject), tidak mendapat IP DHCP, dan tidak bisa masuk portal voucher.
        </p>
    </div>
    <div>
        <button type="button" class="btn btn-block-mac" data-bs-toggle="modal" data-bs-target="#addBlacklistModal">
            <i class="fa-solid fa-plus me-1"></i>Blokir MAC Baru
        </button>
    </div>
</div>

<!-- Info Banner -->
<div class="info-banner mb-4">
    <div class="d-flex align-items-center gap-2 mb-1 fw-bold text-danger">
        <i class="fa-solid fa-shield-halved"></i>
        <span>Prinsip Kerja Blacklist Tamu (Guest SSID)</span>
    </div>
    <div>
        Untuk SSID bertipe <strong>Blacklist</strong> (misal SSID Voucher Tamu), FreeRADIUS akan <strong>mengizinkan semua device</strong> secara otomatis, <strong>KECUALI</strong> MAC address yang terdaftar aktif di bawah. MAC yang diblokir langsung diputus sebelum request DHCP diproses.
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(244,63,94,0.12); color:#f43f5e; border:1px solid rgba(244,63,94,0.25);">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div>
                <div class="stat-val text-light">{{ number_format($totalCount) }}</div>
                <div class="stat-lbl">Total Blacklisted</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3);">
                <i class="fa-solid fa-shield-xmark"></i>
            </div>
            <div>
                <div class="stat-val text-danger">{{ number_format($activeCount) }}</div>
                <div class="stat-lbl">Aktif Diblokir</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(100,116,139,0.12); color:#94a3b8; border:1px solid rgba(100,116,139,0.2);">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div>
                <div class="stat-val text-secondary">{{ number_format($expiredCount) }}</div>
                <div class="stat-lbl">Masa Blokir Habis</div>
            </div>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="blacklist-card">
    <!-- Filter & Search Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4">
        <div class="btn-group" role="group">
            <a href="{{ route('blacklist.index') }}" class="btn btn-sm {{ !request('status') ? 'btn-danger' : 'btn-outline-secondary' }}">
                Semua ({{ $totalCount }})
            </a>
            <a href="{{ route('blacklist.index', ['status' => 'active', 'search' => request('search')]) }}" class="btn btn-sm {{ request('status') === 'active' ? 'btn-danger' : 'btn-outline-secondary' }}">
                Aktif ({{ $activeCount }})
            </a>
            <a href="{{ route('blacklist.index', ['status' => 'expired', 'search' => request('search')]) }}" class="btn btn-sm {{ request('status') === 'expired' ? 'btn-danger' : 'btn-outline-secondary' }}">
                Kadaluarsa ({{ $expiredCount }})
            </a>
        </div>

        <form method="GET" action="{{ route('blacklist.index') }}" class="d-flex gap-2" style="max-width: 380px;">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-dark border-secondary text-secondary">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="search" class="form-control form-control-dark" placeholder="Cari MAC, perangkat, alasan..." value="{{ request('search') }}">
                @if(request('search'))
                    <a href="{{ route('blacklist.index', ['status' => request('status')]) }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </div>
            <button type="submit" class="btn btn-sm btn-outline-danger">Cari</button>
        </form>
    </div>

    <!-- Blacklist Table -->
    <div class="table-responsive">
        <table class="table align-middle text-light mb-0" style="border-collapse:separate; border-spacing:0 8px;">
            <thead>
                <tr class="text-secondary small text-uppercase" style="letter-spacing:0.5px;">
                    <th style="width:22%;">MAC Address</th>
                    <th style="width:20%;">Nama Perangkat</th>
                    <th style="width:24%;">Alasan Blokir</th>
                    <th style="width:14%;">Masa Berlaku</th>
                    <th style="width:10%;">Status</th>
                    <th style="width:10%;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($blacklists as $item)
                    @php
                        $isExpired = $item->isExpired();
                        $isBlocking = $item->isCurrentlyBlocking();
                    @endphp
                    <tr style="background:rgba(15, 23, 42, 0.45); border-radius:10px;">
                        <td class="ps-3 py-3" style="border-top-left-radius:10px; border-bottom-left-radius:10px;">
                            <div class="mac-badge">
                                <i class="fa-solid fa-ban" style="font-size:0.75rem;"></i>
                                {{ $item->mac_address }}
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold text-light small">{{ $item->device_name ?: '—' }}</div>
                            <div class="text-secondary" style="font-size:0.75rem;">
                                Diblokir: {{ $item->blocked_at ? $item->blocked_at->format('d M Y, H:i') : '—' }}
                            </div>
                        </td>
                        <td>
                            <div class="small text-secondary">{{ $item->reason ?: 'Tidak ada keterangan' }}</div>
                            @if($item->blockedByUser)
                                <div class="text-muted" style="font-size:0.72rem;">
                                    Oleh: <span class="text-info">{{ $item->blockedByUser->name }}</span>
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($item->expires_at === null)
                                <span class="badge" style="background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); color:#fca5a5; font-size:0.75rem;">
                                    <i class="fa-solid fa-infinity me-1"></i>Permanen
                                </span>
                            @elseif($isExpired)
                                <span class="badge" style="background:rgba(100,116,139,0.15); border:1px solid rgba(100,116,139,0.3); color:#94a3b8; font-size:0.75rem;">
                                    <i class="fa-solid fa-hourglass-end me-1"></i>Habis ({{ $item->expires_at->diffForHumans() }})
                                </span>
                            @else
                                <span class="badge" style="background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.3); color:#fcd34d; font-size:0.75rem;">
                                    <i class="fa-solid fa-stopwatch me-1"></i>Hingga {{ $item->expires_at->format('d M H:i') }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($isBlocking)
                                <span class="badge bg-danger" style="font-size:0.75rem;">
                                    <i class="fa-solid fa-lock me-1"></i>BLOCKED
                                </span>
                            @elseif(!$item->is_active)
                                <span class="badge bg-secondary" style="font-size:0.75rem;">Nonaktif</span>
                            @else
                                <span class="badge bg-dark text-muted" style="font-size:0.75rem;">Expired</span>
                            @endif
                        </td>
                        <td class="text-center pe-3" style="border-top-right-radius:10px; border-bottom-right-radius:10px;">
                            <div class="d-flex gap-1 justify-content-center">
                                <!-- Toggle Active/Inactive -->
                                <form action="{{ route('blacklist.toggle', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $item->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $item->is_active ? 'Jeda Blokir' : 'Aktifkan Blokir' }}" style="width:32px; height:32px; padding:0;">
                                        <i class="fa-solid {{ $item->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                    </button>
                                </form>

                                <!-- Delete / Unblock -->
                                <form action="{{ route('blacklist.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membuka blokir MAC {{ $item->mac_address }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Buka Blokir Permanen" style="width:32px; height:32px; padding:0;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-secondary mb-2" style="font-size:2.5rem;">
                                <i class="fa-solid fa-shield-heart text-success"></i>
                            </div>
                            <h5 class="fw-bold text-light">Tidak Ada Perangkat yang Diblokir</h5>
                            <p class="text-secondary small mb-0">Semua perangkat tamu dapat terhubung ke SSID Guest secara normal.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($blacklists->hasPages())
    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-secondary">
        <div class="text-secondary small">
            Menampilkan {{ $blacklists->firstItem() }}–{{ $blacklists->lastItem() }} dari {{ $blacklists->total() }} data
        </div>
        <div>
            {{ $blacklists->links() }}
        </div>
    </div>
    @endif
</div>

<!-- ===================== MODAL: Tambah Blacklist ===================== -->
<div class="modal fade" id="addBlacklistModal" tabindex="-1" aria-labelledby="addBlacklistModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#0f172a; border:1px solid rgba(244,63,94,0.3); border-radius:16px;">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title font-outfit fw-bold text-light" id="addBlacklistModalLabel">
                    <i class="fa-solid fa-ban text-danger me-2"></i>Blokir MAC Perangkat Tamu
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('blacklist.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold">MAC Address Target *</label>
                        <input type="text" name="mac_address" id="modalMacInput" class="form-control form-control-dark" placeholder="e.g. AA:BB:CC:DD:EE:FF atau aabbccddeeff" required autofocus style="font-family:'Courier New', monospace; font-weight:700; color:#f43f5e;">
                        <div class="form-text text-secondary">Bisa berupa format colons (:), strip (-), atau tanpa pemisah (otomatis dirapikan).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold">Nama / Tipe Perangkat (Opsional)</label>
                        <input type="text" name="device_name" class="form-control form-control-dark" placeholder="e.g. Samsung A54, iPhone 13, Laptop User X">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold">Alasan Pemblokiran</label>
                        <select name="reason_preset" id="reasonPreset" class="form-select form-control-dark mb-2" onchange="applyReasonPreset(this.value)">
                            <option value="">-- Pilih Alasan Umum atau Ketik Sendiri --</option>
                            <option value="Spam DHCP Pool / Eksploitasi IP">Spam DHCP Pool / Eksploitasi IP</option>
                            <option value="Pelanggaran Ketentuan / TOS Wifi Tamu">Pelanggaran Ketentuan / TOS Wifi Tamu</option>
                            <option value="Perangkat Mencurigakan / Rogue Device">Perangkat Mencurigakan / Rogue Device</option>
                            <option value="Banned / Permintaan Khusus Manajemen">Banned / Permintaan Khusus Manajemen</option>
                        </select>
                        <input type="text" name="reason" id="reasonInput" class="form-control form-control-dark" placeholder="Keterangan alasan pemblokiran...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold">Masa Berlaku Blokir *</label>
                        <select name="duration" id="durationSelect" class="form-select form-control-dark" onchange="toggleCustomDuration(this.value)">
                            <option value="permanent" selected>Permanen (Sampai Dihapus Manual)</option>
                            <option value="1d">1 Hari (24 Jam)</option>
                            <option value="7d">7 Hari (1 Minggu)</option>
                            <option value="30d">30 Hari (1 Bulan)</option>
                            <option value="custom">Kustom Tanggal & Waktu...</option>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="customDurationBox">
                        <label class="form-label small text-warning fw-semibold">Batas Waktu Blokir</label>
                        <input type="datetime-local" name="custom_expires_at" class="form-control form-control-dark">
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-ban me-1"></i>Terapkan Blokir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function applyReasonPreset(val) {
    if (val) {
        document.getElementById('reasonInput').value = val;
    }
}

function toggleCustomDuration(val) {
    const box = document.getElementById('customDurationBox');
    if (val === 'custom') {
        box.classList.remove('d-none');
    } else {
        box.classList.add('d-none');
    }
}
</script>
@endsection
