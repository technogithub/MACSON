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
            'pool_start'  => 'nullable|ipv4',
            'pool_end'    => 'nullable|ipv4',
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
            'pool_start'  => 'nullable|ipv4',
            'pool_end'    => 'nullable|ipv4',
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

        $created = DhcpStaticLease::create($validated);
        $this->dhcpService->saveHostToKeaDb($created->mac_address, $created->ip_address, $created->dhcp_subnet_id, $created->hostname, $created->id);
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
        $oldMac = $lease->mac_address;

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

        if ($oldMac !== $lease->mac_address) {
            $this->dhcpService->deleteHostFromKeaDb($oldMac);
        }

        if ($lease->is_active) {
            $this->dhcpService->saveHostToKeaDb($lease->mac_address, $lease->ip_address, $lease->dhcp_subnet_id, $lease->hostname, $lease->id);
        } else {
            $this->dhcpService->deleteHostFromKeaDb($lease->mac_address);
        }

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
        $this->dhcpService->deleteHostFromKeaDb($lease->mac_address);
        $lease->delete();

        $this->dhcpService->syncConfigFile();

        return redirect()->route('dhcp.index', ['tab' => 'static'])
            ->with('success', "Reservasi Static IP {$ip} berhasil dihapus.");
    }

    /**
     * Download CSV Template untuk Import Reservasi Static IP
     */
    public function templateCsv()
    {
        $fileName = 'dhcp_static_leases_template.csv';
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        return response()->stream(function() {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, ['mac_address', 'ip_address', 'subnet', 'hostname', 'description']);
            fputcsv($file, ['00:11:22:33:44:55', '10.14.27.50', 'SJA AP', 'AP-GUDANG-01', 'Access Point Gudang 1']);
            fputcsv($file, ['AA:BB:CC:DD:EE:FF', '10.14.28.10', 'TAB PWD', 'TAB-POS-01', 'Tablet Kasir Pos 1']);
            fputcsv($file, ['11:22:33:44:55:66', '10.14.27.51', '', '', 'Auto detect pool dari range IP']);
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Import Reservasi Static IP via CSV
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|max:4096',
        ]);

        try {
            $file = $request->file('csv_file');
            if (!$file) {
                return redirect()->route('dhcp.index', ['tab' => 'static'])->with('error', 'Tidak ada file CSV yang diunggah.');
            }

            $path = $file->getRealPath() ?: $file->getPathname();
            $rawContent = @file_get_contents($path);

            if ($rawContent === false || strlen(trim($rawContent)) === 0) {
                return redirect()->route('dhcp.index', ['tab' => 'static'])->with('error', 'File CSV kosong atau tidak dapat dibaca.');
            }

            // Strip UTF-8 BOM
            $rawContent = preg_replace('/^\xEF\xBB\xBF/', '', $rawContent);

            // Detect & Convert Encoding
            $encoding = mb_detect_encoding($rawContent, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'UTF-16', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $rawContent = mb_convert_encoding($rawContent, 'UTF-8', $encoding);
            }

            $rawContent = str_replace("\x00", '', $rawContent);
            $rawContent = str_replace(["\r\n", "\r"], "\n", $rawContent);

            // Auto-detect delimiter
            $firstLine = strtok($rawContent, "\n") ?: '';
            $delimiter = ',';
            if (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) {
                $delimiter = ';';
            } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
                $delimiter = "\t";
            }

            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $rawContent);
            rewind($stream);

            $rows = [];
            while (($row = fgetcsv($stream, 0, $delimiter)) !== false) {
                if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) {
                    continue;
                }
                $rows[] = array_map('trim', $row);
            }
            fclose($stream);

            if (empty($rows)) {
                return redirect()->route('dhcp.index', ['tab' => 'static'])->with('error', 'File CSV tidak memiliki baris data valid.');
            }

            // Cek apakah baris pertama header
            $firstRow = $rows[0];
            $firstCell = $firstRow[0] ?? '';
            $isMac = (bool) Device::formatMacAddress($firstCell);
            $isHeader = !$isMac;

            $macIdx  = 0;
            $ipIdx   = 1;
            $subIdx  = 2;
            $hostIdx = 3;
            $descIdx = 4;

            if ($isHeader) {
                $headers = array_map(function($h) {
                    return strtolower(trim(preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF]/u', '', $h)));
                }, $firstRow);

                foreach ($headers as $idx => $hName) {
                    if (str_contains($hName, 'mac')) {
                        $macIdx = $idx;
                    } elseif (str_contains($hName, 'desc') || str_contains($hName, 'ket')) {
                        // desc checked BEFORE ip to avoid 'description' matching str_contains('ip')
                        $descIdx = $idx;
                    } elseif (str_starts_with($hName, 'ip') || $hName === 'address') {
                        $ipIdx = $idx;
                    } elseif (str_contains($hName, 'sub') || str_contains($hName, 'pool') || str_contains($hName, 'vlan')) {
                        $subIdx = $idx;
                    } elseif (str_contains($hName, 'host') || str_contains($hName, 'name')) {
                        $hostIdx = $idx;
                    }
                }
                array_shift($rows);
            }

            set_time_limit(300);
            ini_set('memory_limit', '512M');

            // Preload existing static leases
            $existingIps = DhcpStaticLease::pluck('ip_address')->mapWithKeys(fn($ip) => [trim($ip) => true])->toArray();
            $existingMacs = DhcpStaticLease::pluck('mac_address')->mapWithKeys(fn($m) => [strtoupper(str_replace(['-', '.'], ':', trim($m))) => true])->toArray();
            $allSubnets = DhcpSubnet::all();
            $devices = Device::select('id', 'mac_address')->get()->mapWithKeys(fn($d) => [$d->mac_address => $d->id])->toArray();

            $importedCount = 0;
            $skippedDuplicates = 0;
            $invalidCount = 0;
            $noSubnetCount = 0;

            $leasesToInsert = [];
            $now = now();

            foreach ($rows as $row) {
                $rawMac  = trim($row[$macIdx] ?? '');
                $rawIp   = trim($row[$ipIdx] ?? '');
                $rawSub  = isset($row[$subIdx]) ? trim($row[$subIdx]) : '';
                $rawHost = isset($row[$hostIdx]) ? trim($row[$hostIdx]) : null;
                $rawDesc = isset($row[$descIdx]) ? trim($row[$descIdx]) : 'CSV Import';

                // Format & validasi MAC
                $formattedMac = Device::formatMacAddress($rawMac);
                if (!$formattedMac) {
                    $invalidCount++;
                    continue;
                }

                // Validasi IPv4
                if (!filter_var($rawIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $invalidCount++;
                    continue;
                }

                // Cek duplikat IP atau MAC di static leases
                if (isset($existingIps[$rawIp]) || isset($existingMacs[$formattedMac])) {
                    $skippedDuplicates++;
                    continue;
                }

                // Cari subnet yang cocok (by hint nama/vlan/cidr atau auto-detect dari IP)
                $matchedSubnet = DhcpSubnet::findByHintOrIp($rawSub, $rawIp, $allSubnets);
                if (!$matchedSubnet) {
                    $noSubnetCount++;
                    continue;
                }

                $deviceId = $devices[$formattedMac] ?? null;

                $existingIps[$rawIp] = true;
                $existingMacs[$formattedMac] = true;

                $leasesToInsert[] = [
                    'dhcp_subnet_id' => $matchedSubnet->id,
                    'device_id'      => $deviceId,
                    'mac_address'    => $formattedMac,
                    'ip_address'     => $rawIp,
                    'hostname'       => !empty($rawHost) ? $rawHost : null,
                    'description'    => !empty($rawDesc) ? $rawDesc : 'CSV Import',
                    'is_active'      => true,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];

                $importedCount++;
            }

            if (!empty($leasesToInsert)) {
                \Illuminate\Support\Facades\DB::transaction(function() use ($leasesToInsert) {
                    foreach (array_chunk($leasesToInsert, 500) as $chunk) {
                        DhcpStaticLease::insert($chunk);
                    }
                });

                $this->dhcpService->syncAllStaticLeasesToKeaDb();
                $this->dhcpService->syncConfigFile();
            }

            $msg = "Import static IP selesai! Berhasil ditambahkan: {$importedCount}";
            if ($skippedDuplicates > 0) $msg .= ", Duplikat dilewati: {$skippedDuplicates}";
            if ($invalidCount > 0) $msg .= ", Format tidak valid: {$invalidCount}";
            if ($noSubnetCount > 0) $msg .= ", Gagal cocok subnet: {$noSubnetCount}";

            return redirect()->route('dhcp.index', ['tab' => 'static'])->with('success', $msg);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('DHCP Static Lease CSV Import Error: ' . $e->getMessage());
            return redirect()->route('dhcp.index', ['tab' => 'static'])->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    /**
     * Manual Trigger Sync Config & Reload Service
     */
    public function syncConfig()
    {
        $this->dhcpService->syncAllStaticLeasesToKeaDb();
        $ok = $this->dhcpService->syncConfigFile();

        if ($ok) {
            return redirect()->back()->with('success', "Konfigurasi & Database Kea DHCPv4 berhasil disinkronkan & diterapkan.");
        }

        return redirect()->back()->with('error', "Gagal menulis file konfigurasi Kea DHCP. Periksa permission folder.");
    }

    /**
     * Realtime Polling API untuk Active Dynamic Leases tanpa full page reload
     */
    public function liveLeases(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $subnets = DhcpSubnet::where('is_active', true)->get();
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

            // Hitung count per subnet
            $activeLeaseCounts = $enrichedLeases->groupBy('subnet_id')->map->count()->toArray();
            $totalActiveLeases = $enrichedLeases->count();

            // Filter by pool
            $selectedPool = $request->input('pool_id', 'all');
            $filteredLeases = $enrichedLeases;
            if ($selectedPool !== 'all') {
                $poolId = (int)$selectedPool;
                $filteredLeases = $filteredLeases->filter(fn($al) => (int)$al['subnet_id'] === $poolId);
            }

            // Filter by search
            if ($request->filled('search')) {
                $s = strtolower(trim($request->input('search')));
                $filteredLeases = $filteredLeases->filter(function ($al) use ($s) {
                    return str_contains(strtolower($al['ip']), $s)
                        || str_contains(strtolower($al['mac']), $s)
                        || str_contains(strtolower($al['hostname']), $s)
                        || str_contains(strtolower($al['subnet_name']), $s);
                });
            }

            $perPage = (int)$request->input('per_page', 15);
            $leasePage = (int)$request->input('lease_page', 1);
            $pagedLeases = $filteredLeases->forPage($leasePage, $perPage)->values();

            // Render HTML rows
            $htmlRows = '';
            if ($pagedLeases->isEmpty()) {
                $htmlRows = '<tr><td colspan="6" class="text-center py-4 text-secondary">' .
                    '<i class="fa-solid fa-network-wired fs-3 mb-2 d-block text-muted"></i>' .
                    'Belum ada active dynamic lease yang sesuai filter.</td></tr>';
            } else {
                foreach ($pagedLeases as $al) {
                    $vlanBadge = !empty($al['vlan_id']) ? '<span class="text-secondary">(VLAN ' . e($al['vlan_id']) . ')</span>' : '';
                    $statusBadge = $al['is_active']
                        ? '<span class="badge bg-success-subtle text-success border border-success">Valid</span>'
                        : '<span class="badge bg-secondary">Expired</span>';

                    $htmlRows .= '<tr>' .
                        '<td><span class="ip-tag fw-bold">' . e($al['ip']) . '</span></td>' .
                        '<td><span class="badge bg-dark border border-secondary text-info">' . e($al['subnet_name']) . ' ' . $vlanBadge . '</span></td>' .
                        '<td><span class="mac-tag">' . e($al['mac']) . '</span></td>' .
                        '<td><span class="text-light">' . e($al['hostname'] ?: '-') . '</span></td>' .
                        '<td><span class="text-secondary small font-monospace">' . e($al['expire_at']) . '</span></td>' .
                        '<td>' . $statusBadge . '</td>' .
                        '</tr>';
                }
            }

            return response()->json([
                'success'        => true,
                'total_active'   => $totalActiveLeases,
                'filtered_count' => $filteredLeases->count(),
                'active_counts'  => $activeLeaseCounts,
                'html_rows'      => $htmlRows,
                'timestamp'      => now()->format('H:i:s'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear Dynamic Leases (Semua atau per-Subnet)
     */
    public function clearLeases(Request $request)
    {
        try {
            $poolId = $request->input('pool_id', 'all');
            $subnetId = ($poolId !== 'all' && is_numeric($poolId)) ? (int)$poolId : null;

            $subnet = $subnetId ? DhcpSubnet::find($subnetId) : null;
            $clearedCount = $this->dhcpService->clearLeases($subnetId);

            $targetName = $subnet ? "Subnet {$subnet->name}" : "Semua Subnet Pool";
            $msg = "Berhasil membersihkan {$clearedCount} active dynamic lease pada {$targetName}. Layanan Kea DHCP telah dimuat ulang.";

            return redirect()->route('dhcp.index', ['tab' => 'leases', 'pool_id' => $poolId])->with('success', $msg);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('DHCP Clear Leases Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membersihkan leases: ' . $e->getMessage());
        }
    }
}
