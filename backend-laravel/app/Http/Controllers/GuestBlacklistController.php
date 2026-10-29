<?php

namespace App\Http\Controllers;

use App\Models\GuestBlacklist;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GuestBlacklistController extends Controller
{
    /**
     * Display listing of blacklisted guest MAC addresses
     */
    public function index(Request $request)
    {
        $query = GuestBlacklist::with('blockedByUser')->orderBy('id', 'desc');

        // Search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('mac_address', 'like', "%{$search}%")
                  ->orWhere('device_name', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'active') {
                $query->where('is_active', true)
                      ->where(function ($q) {
                          $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
                      });
            } elseif ($status === 'expired') {
                $query->whereNotNull('expires_at')->where('expires_at', '<=', Carbon::now());
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Statistics
        $totalCount   = GuestBlacklist::count();
        $activeCount  = GuestBlacklist::where('is_active', true)
                            ->where(function ($q) {
                                $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
                            })->count();
        $expiredCount = GuestBlacklist::whereNotNull('expires_at')
                            ->where('expires_at', '<=', Carbon::now())
                            ->count();

        $blacklists = $query->paginate(15)->withQueryString();

        return view('guest-blacklist.index', compact(
            'blacklists',
            'totalCount',
            'activeCount',
            'expiredCount'
        ));
    }

    /**
     * Store or update a blacklisted MAC address
     */
    public function store(Request $request)
    {
        $request->validate([
            'mac_address' => 'required|string|min:12|max:20',
            'device_name' => 'nullable|string|max:100',
            'reason'      => 'nullable|string|max:255',
            'duration'    => 'required|in:permanent,1d,7d,30d,custom',
            'custom_expires_at' => 'required_if:duration,custom|nullable|date|after:now',
        ]);

        $cleanMac = GuestBlacklist::sanitizeMac($request->mac_address);

        // Validasi format MAC setelah disanitasi
        if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $cleanMac)) {
            return redirect()->back()->withInput()->with('error', "Format MAC address tidak valid: '{$request->mac_address}'");
        }

        // Hitung masa berlaku
        $expiresAt = null;
        switch ($request->duration) {
            case '1d':
                $expiresAt = Carbon::now()->addDay();
                break;
            case '7d':
                $expiresAt = Carbon::now()->addDays(7);
                break;
            case '30d':
                $expiresAt = Carbon::now()->addDays(30);
                break;
            case 'custom':
                $expiresAt = Carbon::parse($request->custom_expires_at);
                break;
            case 'permanent':
            default:
                $expiresAt = null;
                break;
        }

        GuestBlacklist::updateOrCreate(
            ['mac_address' => $cleanMac],
            [
                'device_name' => $request->device_name,
                'reason'      => $request->reason,
                'blocked_by'  => auth()->id(),
                'blocked_at'  => Carbon::now(),
                'expires_at'  => $expiresAt,
                'is_active'   => true,
            ]
        );

        return redirect()->back()->with('success', "Perangkat MAC {$cleanMac} berhasil dimasukkan ke daftar Blacklist!");
    }

    /**
     * Toggle active/inactive status
     */
    public function toggle($id)
    {
        $item = GuestBlacklist::findOrFail($id);
        $item->is_active = !$item->is_active;
        $item->save();

        $statusStr = $item->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Status blokir untuk MAC {$item->mac_address} berhasil {$statusStr}.");
    }

    /**
     * Remove entry from blacklist
     */
    public function destroy($id)
    {
        $item = GuestBlacklist::findOrFail($id);
        $mac = $item->mac_address;
        $item->delete();

        return redirect()->back()->with('success', "MAC {$mac} telah dihapus dari Blacklist. Perangkat kini diizinkan konek kembali.");
    }
}
