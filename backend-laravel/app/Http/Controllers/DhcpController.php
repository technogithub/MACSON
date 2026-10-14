<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DhcpSubnet;
use App\Models\DhcpStaticLease;
use App\Services\DhcpConfigService;
use Illuminate\Http\Request;

class DhcpController extends Controller
{
    protected DhcpConfigService $dhcpService;

    public function __construct(DhcpConfigService $dhcpService)
    {
        $this->dhcpService = $dhcpService;
    }

    /**
     * Halaman Utama DHCP: Menampilkan Subnets, Static Leases, & Active Dynamic Leases dengan Filter Pool & Pagination
     */
    public function index(Request $request)
    {
        $currentTab   = $request->input('tab', 'subnets');
        $selectedPool = $request->input('pool_id', 'all');
        $perPage      = in_array((int)$request->input('per_page'), [15, 50, 100]) ? (int)$request->input('per_page') : 15;
        $search       = $request->input('search');

        $subnets = DhcpSubnet::withCount('staticLeases')->orderBy('id', 'asc')->get();

        // 1. Static IP Leases Query with pool filter & search
        $staticQuery = DhcpStaticLease::with(['subnet', 'device']);
        if ($selectedPool !== 'all') {
            $staticQuery->where('dhcp_subnet_id', $selectedPool);
        }
        if ($request->filled('search')) {
            $s = trim($search);
            $staticQuery->where(function($q) use ($s) {
                $q->where('ip_address', 'like', "%{$s}%")
                  ->orWhere('mac_address', 'like', "%{$s}%")
                  ->orWhere('hostname', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
                  ->orWhereHas('device', function($dq) use ($s) {
                      $dq->where('device_name', 'like', "%{$s}%");
                  });
            });
        }
        $staticLeases = $staticQuery->orderBy('id', 'desc')->paginate($perPage, ['*'], 'static_page')->withQueryString();

        // 2. Active Dynamic Leases from Kea CSV
        $rawLeases = $this->dhcpService->getActiveLeases();

        // Enrich each active lease with matched subnet
        $enrichedLeases = collect($rawLeases)->map(function ($al) use ($subnets) {
            $matchedSubnet = null;
            if (!empty($al['subnet_id'])) {
                $matchedSubnet = $subnets->firstWhere('id', (int)$al['subnet_id']);
            }
            if (!$matchedSubnet && !empty($al['ip']) && filter_var($al['ip'], FILTER_VALIDATE_IP)) {
                $ipLong = ip2long($al['ip']);
                foreach ($subnets as $s) {
                    $startLong = ip2long($s->pool_start);
                    $endLong   = ip2long($s->pool_end);
                    if ($startLong !== false && $endLong !== false && $ipLong >= min($startLong, $endLong) && $ipLong <= max($startLong, $endLong)) {
                        $matchedSubnet = $s;
                        break;
                    }
                }
            }

            $al['subnet_id']   = $matchedSubnet ? $matchedSubnet->id : ($al['subnet_id'] ?? 0);
            $al['subnet_name'] = $matchedSubnet ? $matchedSubnet->name : 'Unknown Pool';
            $al['vlan_id']     = $matchedSubnet ? $matchedSubnet->vlan_id : null;
            return $al;
        });

        // Calculate count of active dynamic leases per subnet
        $activeLeaseCounts = $enrichedLeases->groupBy('subnet_id')->map->count()->toArray();
        $totalActiveLeases = $enrichedLeases->count();

        // Filter active dynamic leases by selected pool
        $filteredLeases = $enrichedLeases;
        if ($selectedPool !== 'all') {
            $poolId = (int)$selectedPool;
            $filteredLeases = $filteredLeases->filter(fn($al) => (int)$al['subnet_id'] === $poolId);
        }

        // Filter active dynamic leases by search
        if ($request->filled('search')) {
            $s = strtolower(trim($search));
            $filteredLeases = $filteredLeases->filter(function ($al) use ($s) {
                return str_contains(strtolower($al['ip']), $s)
                    || str_contains(strtolower($al['mac']), $s)
                    || str_contains(strtolower($al['hostname']), $s)
                    || str_contains(strtolower($al['subnet_name']), $s);
            });
        }

        // Paginate active dynamic leases collection
        $leasePage = (int)$request->input('lease_page', 1);
        $totalFilteredLeases = $filteredLeases->count();
        $activeLeases = new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredLeases->forPage($leasePage, $perPage)->values(),
            $totalFilteredLeases,
            $perPage,
            $leasePage,
            ['path' => route('dhcp.index'), 'pageName' => 'lease_page', 'query' => $request->query()]
        );

        $devices = Device::select('id', 'mac_address', 'device_name', 'location', 'ssid')->orderBy('device_name')->get();

        return view('dhcp.index', compact(
            'subnets',
            'staticLeases',
            'devices',
            'activeLeases',
            'activeLeaseCounts',
            'totalActiveLeases',
            'selectedPool',
            'perPage',
            'currentTab'
        ));
    }

    /**
     * Simpan Subnet / Pool Baru
     */
    public function storeSubnet(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'vlan_id'     => 'nullable|integer|between:1,4094',
            'subnet'      => 'required|string|max:50|unique:dhcp_subnets,subnet',
            'pool_start'  => 'required|ipv4',
            'pool_end'    => 'required|ipv4',
            'gateway'     => 'required|ipv4',
            'dns_servers' => 'nullable|string|max:255',
            'lease_time'  => 'required|integer|min:300',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        DhcpSubnet::create($validated);
        $this->dhcpService->syncConfigFile();

        return redirect()->route('dhcp.index', ['tab' => 'subnets'])
            ->with('success', "Subnet {$validated['name']} berhasil ditambahkan dan disinkronkan ke Kea DHCP.");
    }

    /**
     * Update Subnet / Pool
     */
    public function updateSubnet(Request $request, int $id)
    {
        $subnet = DhcpSubnet::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'vlan_id'     => 'nullable|integer|between:1,4094',
            'subnet'      => 'required|string|max:50|unique:dhcp_subnets,subnet,' . $id,
            'pool_start'  => 'required|ipv4',
            'pool_end'    => 'required|ipv4',
            'gateway'     => 'required|ipv4',
            'dns_servers' => 'nullable|string|max:255',
            'lease_time'  => 'required|integer|min:300',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : ($subnet->is_active ?? true);
        $subnet->update($validated);
        $this->dhcpService->syncConfigFile();

        return redirect()->route('dhcp.index', ['tab' => 'subnets'])
            ->with('success', "Subnet {$subnet->name} berhasil diperbarui.");
    }

    /**
     * Hapus Subnet
     */
    public function destroySubnet(int $id)
    {
        $subnet = DhcpSubnet::findOrFail($id);
        $name = $subnet->name;
        $subnet->delete();

        $this->dhcpService->syncConfigFile();

        return redirect()->route('dhcp.index', ['tab' => 'subnets'])
            ->with('success', "Subnet {$name} berhasil dihapus.");
    }

    /**
     * Simpan Reservasi Static IP
     */
    public function storeStaticLease(Request $request)
    {
        $validated = $request->validate([
            'dhcp_subnet_id' => 'required|exists:dhcp_subnets,id',
            'device_id'      => 'nullable|exists:devices,id',
            'mac_address'    => 'required|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'ip_address'     => 'required|ipv4|unique:dhcp_static_leases,ip_address',
            'hostname'       => 'nullable|string|max:100',
            'description'    => 'nullable|string',
        ]);

        $formattedMac = strtoupper(str_replace(['-', '.'], ':', trim($validated['mac_address'])));
        $validated['mac_address'] = $formattedMac;

        DhcpStaticLease::create($validated);
        $this->dhcpService->syncConfigFile();

        return redirect()->route('dhcp.index', ['tab' => 'static'])
            ->with('success', "Reservasi Static IP {$validated['ip_address']} ({$formattedMac}) berhasil disimpan.");
    }

    /**
     * Update Reservasi Static IP
     */
    public function updateStaticLease(Request $request, int $id)
    {
        $lease = DhcpStaticLease::findOrFail($id);

        $validated = $request->validate([
            'dhcp_subnet_id' => 'required|exists:dhcp_subnets,id',
            'device_id'      => 'nullable|exists:devices,id',
            'mac_address'    => 'required|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'ip_address'     => 'required|ipv4|unique:dhcp_static_leases,ip_address,' . $id,
            'hostname'       => 'nullable|string|max:100',
            'description'    => 'nullable|string',
            'is_active'      => 'nullable|boolean',
        ]);

        $formattedMac = strtoupper(str_replace(['-', '.'], ':', trim($validated['mac_address'])));
        $validated['mac_address'] = $formattedMac;
        $validated['is_active']   = $request->has('is_active');

        $lease->update($validated);
        $this->dhcpService->syncConfigFile();

        return redirect()->route('dhcp.index', ['tab' => 'static'])
            ->with('success', "Reservasi Static IP {$lease->ip_address} berhasil diperbarui.");
    }

    /**
     * Hapus Reservasi Static IP
     */
    public function destroyStaticLease(int $id)
    {
        $lease = DhcpStaticLease::findOrFail($id);
        $ip = $lease->ip_address;
        $lease->delete();

        $this->dhcpService->syncConfigFile();

        return redirect()->route('dhcp.index', ['tab' => 'static'])
            ->with('success', "Reservasi Static IP {$ip} berhasil dihapus.");
    }

    /**
     * Manual Trigger Sync Config & Reload Service
     */
    public function syncConfig()
    {
        $ok = $this->dhcpService->syncConfigFile();

        if ($ok) {
            return redirect()->back()->with('success', "Konfigurasi Kea DHCPv4 berhasil disinkronkan & diterapkan.");
        }

        return redirect()->back()->with('error', "Gagal menulis file konfigurasi Kea DHCP. Periksa permission folder.");
    }
}
