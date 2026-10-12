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
     * Halaman Utama DHCP: Menampilkan Subnets, Static Leases, & Active Dynamic Leases
     */
    public function index(Request $request)
    {
        $subnets = DhcpSubnet::withCount('staticLeases')->orderBy('id', 'asc')->get();
        $staticLeases = DhcpStaticLease::with(['subnet', 'device'])->orderBy('id', 'desc')->get();
        $devices = Device::select('id', 'mac_address', 'device_name')->orderBy('device_name')->get();
        $activeLeases = $this->dhcpService->getActiveLeases();

        return view('dhcp.index', compact('subnets', 'staticLeases', 'devices', 'activeLeases'));
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
        ]);

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

        $validated['is_active'] = $request->has('is_active');
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
