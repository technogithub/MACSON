<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print UniFi Vouchers</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 20px;
        }
        .no-print {
            margin-bottom: 20px;
            text-align: center;
        }
        .btn-print {
            background: #0ea5e9;
            color: white;
            border: none;
            padding: 10px 24px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
        }
        .voucher-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }
        .voucher-card {
            background: white;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: relative;
        }
        .voucher-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: bold;
        }
        .voucher-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 1.4rem;
            font-weight: 900;
            color: #0f172a;
            margin: 10px 0;
            letter-spacing: 2px;
            background: #f1f5f9;
            padding: 6px;
            border-radius: 6px;
        }
        .voucher-meta {
            font-size: 0.8rem;
            color: #475569;
        }
        @media print {
            .no-print { display: none; }
            body { background: white; padding: 0; }
            .voucher-card { break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn-print">🖨️ Print Vouchers Now</button>
</div>

<div class="voucher-grid">
    @forelse($vouchers as $v)
        <div class="voucher-card">
            <div class="voucher-title">GUEST WI-FI VOUCHER</div>
            <div class="voucher-code">{{ $v->formatted_code }}</div>
            <div class="voucher-meta">
                <strong>Duration:</strong> 
                @if($v->duration_minutes >= 1440)
                    {{ round($v->duration_minutes / 1440, 1) }} Day(s)
                @elseif($v->duration_minutes >= 60)
                    {{ round($v->duration_minutes / 60, 1) }} Hour(s)
                @else
                    {{ $v->duration_minutes }} Mins
                @endif
                <br>
                <strong>Quota:</strong> {{ $v->quota_mb ? $v->quota_mb.' MB' : 'Unlimited' }}
            </div>
            @if($v->note)
                <div style="font-size:0.7rem; color:#94a3b8; margin-top:6px;">{{ $v->note }}</div>
            @endif
        </div>
    @empty
        <p style="text-align:center; width:100%;">No unused vouchers available for print.</p>
    @endforelse
</div>

</body>
</html>
