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

        // Status Filter (Default: 'active' which shows Unused, Used, & Expired, excluding Revoked)
        $selectedStatus = $request->input('status', 'active');
        if ($selectedStatus === 'active') {
            $query->where('status', '!=', 'revoked');
        } elseif ($selectedStatus !== 'all') {
            $query->where('status', $selectedStatus);
        }

        $perPage = in_array((int)$request->input('per_page'), [20, 50, 100, 200]) ? (int)$request->input('per_page') : 20;

        $vouchers = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
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
            'duration_value'   => 'required|integer|min:1',
            'duration_unit'    => 'required|in:minutes,hours,days',
            'use_limit'        => 'required|integer|min:1',
            'quota_mb'         => 'nullable|integer|min:1',
            'down_kbps'        => 'nullable|integer|min:64',
            'up_kbps'          => 'nullable|integer|min:64',
            'note'             => 'nullable|string|max:100',
        ]);

        $count  = (int)$request->count;
        $val    = (int)$request->duration_value;
        $unit   = $request->duration_unit;

        // Calculate total duration in minutes
        if ($unit === 'days') {
            $durationMinutes = $val * 1440;
        } elseif ($unit === 'hours') {
            $durationMinutes = $val * 60;
        } else {
            $durationMinutes = $val;
        }

        $useLimit = (int)$request->use_limit;
        $quotaMB  = $request->filled('quota_mb') ? (int)$request->quota_mb : null;
        $downKbps = $request->filled('down_kbps') ? (int)$request->down_kbps : null;
        $upKbps   = $request->filled('up_kbps') ? (int)$request->up_kbps : null;
        $note     = $request->note ? trim($request->note) : 'Manual Batch';

        $batchId = 'batch_' . date('Ymd_His') . '_' . Str::random(4);

        $result = $this->uniFiService->createVouchers(
            $count, $durationMinutes, $useLimit, $quotaMB, $downKbps, $upKbps, $note
        );

        $vouchersToSave = [];
        $now = now();
        $isSynced = ($result['mode'] === 'unifi_api');
        $syncStatus = $isSynced ? 'synced' : 'pending_create';

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
                    'sync_status'      => $syncStatus,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }

            UnifiVoucher::insert($vouchersToSave);
        }

        $modeText = $isSynced ? 'UniFi Controller API (Synced)' : 'Local Engine (Offline Mode - Queued for Auto Sync)';
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
        
        // Revoke on UniFi Controller API by _id or code
        $revokedOnUnifi = $this->uniFiService->revokeVoucher($voucher->unifi_id, $voucher->code);

        $voucher->status = 'revoked';
        $voucher->sync_status = $revokedOnUnifi ? 'synced' : 'pending_revoke';
        $voucher->save();

        $msg = $revokedOnUnifi 
            ? "Voucher {$voucher->code} successfully revoked on both UniFi Controller and Local System." 
            : "Voucher {$voucher->code} marked as revoked locally (Queued for Auto-Sync when UniFi is Online).";

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Trigger Manual Sync & Import for Vouchers with UniFi Controller
     */
    public function syncNow()
    {
        $pendingStats = $this->uniFiService->syncPendingVouchers();
        $fullStats    = $this->uniFiService->syncAllVouchersFromUniFi();

        $msgParts = [];
        if ($fullStats['updated'] > 0) {
            $msgParts[] = "Updated {$fullStats['updated']} voucher status(es)";
        }
        if ($fullStats['imported'] > 0) {
            $msgParts[] = "Imported {$fullStats['imported']} new voucher(s) from UniFi";
        }
        if ($pendingStats['created'] > 0) {
            $msgParts[] = "Uploaded {$pendingStats['created']} pending creation(s)";
        }
        if ($pendingStats['revoked'] > 0) {
            $msgParts[] = "Processed {$pendingStats['revoked']} pending revocation(s)";
        }

        if (!empty($msgParts)) {
            $details = implode(', ', $msgParts);
            return redirect()->back()->with('success', "UniFi Controller Sync Completed! {$details}. (Total UniFi: {$fullStats['total_unifi']})");
        }

        return redirect()->back()->with('info', "UniFi Sync Completed. All {$fullStats['total_unifi']} vouchers are up-to-date with UniFi Controller.");
    }

    /**
     * Batch Revoke Selected Vouchers via Checklist
     */
    public function batchRevoke(Request $request)
    {
        $request->validate([
            'voucher_ids'   => 'required|array',
            'voucher_ids.*' => 'integer|exists:unifi_vouchers,id',
        ]);

        $vouchers = UnifiVoucher::whereIn('id', $request->voucher_ids)->get();
        $revokedCount = 0;

        foreach ($vouchers as $v) {
            $revokedOnUnifi = $this->uniFiService->revokeVoucher($v->unifi_id, $v->code);
            $v->update([
                'status'      => 'revoked',
                'sync_status' => $revokedOnUnifi ? 'synced' : 'pending_revoke',
            ]);
            $revokedCount++;
        }

        return redirect()->back()->with('success', "Successfully revoked {$revokedCount} selected voucher(s)!");
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
