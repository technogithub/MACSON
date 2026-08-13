<?php

namespace App\Http\Controllers;

use App\Models\UnifiConfig;
use App\Models\UnifiVoucher;
use App\Services\UniFiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    protected UniFiService $uniFiService;

    public function __construct(UniFiService $uniFiService)
    {
        $this->uniFiService = $uniFiService;
    }

    /**
     * Display listing of UniFi Vouchers with Search & Filter
     */
    public function index(Request $request)
    {
        $query = UnifiVoucher::query();

        // Search Filter (Code, Note, Batch ID)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%")
                  ->orWhere('batch_id', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $vouchers = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();
        $config   = $this->uniFiService->getConfig();

        // Voucher Stats
        $stats = [
            'total'    => UnifiVoucher::count(),
            'unused'   => UnifiVoucher::where('status', 'unused')->count(),
            'used'     => UnifiVoucher::where('status', 'used')->count(),
            'expired'  => UnifiVoucher::where('status', 'expired')->count(),
            'revoked'  => UnifiVoucher::where('status', 'revoked')->count(),
        ];

        return view('vouchers.index', compact('vouchers', 'config', 'stats'));
    }

    /**
     * Batch Generate Vouchers via Form
     */
    public function store(Request $request)
    {
        $request->validate([
            'count'            => 'required|integer|min:1|max:500',
            'duration_minutes' => 'required|integer|min:5',
            'use_limit'        => 'required|integer|min:1',
            'quota_mb'         => 'nullable|integer|min:1',
            'down_kbps'        => 'nullable|integer|min:64',
            'up_kbps'          => 'nullable|integer|min:64',
            'note'             => 'nullable|string|max:100',
        ]);

        $count           = (int)$request->count;
        $durationMinutes = (int)$request->duration_minutes;
        $useLimit        = (int)$request->use_limit;
        $quotaMB         = $request->filled('quota_mb') ? (int)$request->quota_mb : null;
        $downKbps        = $request->filled('down_kbps') ? (int)$request->down_kbps : null;
        $upKbps          = $request->filled('up_kbps') ? (int)$request->up_kbps : null;
        $note            = $request->note ? trim($request->note) : 'Manual Batch';

        $batchId = 'batch_' . date('Ymd_His') . '_' . Str::random(4);

        $result = $this->uniFiService->createVouchers(
            $count, $durationMinutes, $useLimit, $quotaMB, $downKbps, $upKbps, $note
        );

        $vouchersToSave = [];
        $now = now();

        if (!empty($result['data'])) {
            foreach ($result['data'] as $vData) {
                $code    = isset($vData['code']) ? str_replace('-', '', $vData['code']) : sprintf('%05d%05d', rand(10000, 99999), rand(10000, 99999));
                $unifiId = $vData['_id'] ?? null;
                
                $vouchersToSave[] = [
                    'unifi_id'         => $unifiId,
                    'code'             => $code,
                    'duration_minutes' => $durationMinutes,
                    'quota_mb'         => $quotaMB,
                    'down_kbps'        => $downKbps,
                    'up_kbps'          => $upKbps,
                    'use_limit'        => $useLimit,
                    'used_count'       => 0,
                    'note'             => $note,
                    'batch_id'         => $batchId,
                    'status'           => 'unused',
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }

            UnifiVoucher::insert($vouchersToSave);
        }

        $modeText = ($result['mode'] === 'unifi_api') ? 'UniFi Controller API' : 'Local Engine (Offline Mode)';
        return redirect()->route('vouchers.index')->with('success', "Successfully generated {$count} UniFi Vouchers via {$modeText}. Batch ID: {$batchId}");
    }

    /**
     * Print Vouchers (A4 / Thermal Slip Preview)
     */
    public function print(Request $request)
    {
        $query = UnifiVoucher::query();

        if ($request->filled('batch')) {
            $query->where('batch_id', $request->batch);
        } elseif ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $query->whereIn('id', $ids);
        } else {
            $query->where('status', 'unused')->limit(50);
        }

        $vouchers = $query->get();
        return view('vouchers.print', compact('vouchers'));
    }

    /**
     * Revoke / Cancel a Single Voucher
     */
    public function destroy(int $id)
    {
        $voucher = UnifiVoucher::findOrFail($id);
        
        // Revoke on UniFi Controller API if unifi_id or code exists
        $targetId = $voucher->unifi_id ?: $voucher->code;
        $revokedOnUnifi = $this->uniFiService->revokeVoucher($targetId);

        $voucher->status = 'revoked';
        $voucher->save();

        $msg = $revokedOnUnifi 
            ? "Voucher {$voucher->code} successfully revoked on both UniFi Controller and Local System." 
            : "Voucher {$voucher->code} marked as revoked locally.";

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Update UniFi Controller Connection Config
     */
    public function updateConfig(Request $request)
    {
        $request->validate([
            'controller_url' => 'required|url',
            'site_id'        => 'required|string',
            'username'       => 'required|string',
            'password'       => 'required|string',
        ]);

        $config = $this->uniFiService->getConfig();
        $config->update([
            'controller_url' => rtrim($request->controller_url, '/'),
            'site_id'        => $request->site_id,
            'username'       => $request->username,
            'password'       => $request->password,
            'verify_ssl'     => $request->has('verify_ssl'),
        ]);

        return redirect()->back()->with('success', 'UniFi Controller configuration updated successfully.');
    }
}
