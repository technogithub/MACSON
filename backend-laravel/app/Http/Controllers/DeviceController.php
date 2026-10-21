<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DhcpStaticLease;
use App\Models\DhcpSubnet;
use App\Models\Ssid;
use App\Services\DhcpConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class DeviceController extends Controller
{
    protected DhcpConfigService $dhcpService;

    public function __construct(DhcpConfigService $dhcpService)
    {
        $this->dhcpService = $dhcpService;
    }

    /**
     * Display listing of devices with Search, SSID Filter & Pagination
     */
    public function index(Request $request)
    {
        $query = Device::with(['staticLease.subnet']);

        // Search Filter (MAC, SSID, Name, Location, Description)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('mac_address', 'like', "%{$search}%")
                  ->orWhere('ssid', 'like', "%{$search}%")
                  ->orWhere('device_name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // SSID Filter
        if ($request->filled('ssid') && $request->ssid !== 'all') {
            $query->where('ssid', $request->ssid);
        }

        // Status Filter (active / inactive)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Sorting
        $allowedSorts = [
            'device_name' => 'device_name',
            'mac_address' => 'mac_address',
            'ssid'        => 'ssid',
            'vlan_id'     => 'vlan_id',
            'description' => 'description',
            'status'      => 'status',
            'id'          => 'id',
            'created_at'  => 'created_at',
        ];

        $sort = $request->input('sort', 'id');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (array_key_exists($sort, $allowedSorts)) {
            $query->orderBy($allowedSorts[$sort], $direction);
        } else {
            $sort = 'id';
            $direction = 'desc';
            $query->orderBy('id', 'desc');
        }

        // Per Page selection (15, 50, 100 - default 15)
        $perPage = in_array((int)$request->input('per_page'), [15, 50, 100]) ? (int)$request->input('per_page') : 15;

        $devices        = $query->paginate($perPage)->withQueryString();
        $availableSsids = Device::distinct()->pluck('ssid')->toArray();
        $masterSsids    = Ssid::orderBy('vlan_id', 'asc')->get();

        // Calculate global statistics across ALL pages
        $totalCount    = Device::count();
        $activeCount   = Device::where('status', 'active')->count();
        $inactiveCount = Device::where('status', 'inactive')->count();

        // Primary SSIDs requested for big menu navigation tabs
        $primarySsids = ['SJA REG', 'SJA AP', 'DEVICE OPS AP'];

        // Real-time device counts grouped by SSID
        $ssidCounts = Device::selectRaw('ssid, count(*) as count')
            ->groupBy('ssid')
            ->pluck('count', 'ssid')
            ->toArray();

        // Additional SSIDs outside the primary list (if any exist)
        $masterNames = $masterSsids->pluck('ssid_name')->toArray();
        $otherSsids  = array_values(array_filter(
            array_unique(array_merge($availableSsids, $masterNames)),
            fn($s) => !in_array($s, $primarySsids) && $s !== 'ALL' && !empty($s)
        ));

        $selectedSsid = $request->input('ssid', 'all');

        $dhcpSubnets = DhcpSubnet::orderBy('name')->get(['id', 'name', 'vlan_id', 'pool_start', 'pool_end', 'subnet', 'is_active']);

        return view('devices.index', compact(
            'devices',
            'availableSsids',
            'masterSsids',
            'totalCount',
            'activeCount',
            'inactiveCount',
            'primarySsids',
            'ssidCounts',
            'otherSsids',
            'perPage',
            'selectedSsid',
            'dhcpSubnets',
            'sort',
            'direction'
        ));
    }

    /**
     * Store new Device with MAC & SSID validation & Duplicate check
     */
    public function store(Request $request)
    {
        $rawMac = $request->input('mac_address');
        $ssid   = trim($request->input('ssid', 'ALL'));
        $formattedMac = Device::formatMacAddress($rawMac);

        if (!$formattedMac) {
            return redirect()->back()->with('error', 'Invalid MAC Address format! Allowed formats: AA:BB:CC:DD:EE:FF, AA-BB-CC-DD-EE-FF, aabbccddeeff')->withInput();
        }

        if (Device::isDuplicate($formattedMac, $ssid)) {
            return redirect()->back()->with('error', "Duplicate MAC Address! MAC {$formattedMac} already registered for SSID '{$ssid}'.")->withInput();
        }

        $request->validate([
            'device_name' => 'required|string|max:100',
            'ssid'        => 'required|string|max:64',
            'location'    => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'vlan_id'     => 'nullable|integer|between:1,4094',
            'status'      => 'required|in:active,inactive',
        ]);

        $device = Device::create([
            'mac_address' => $formattedMac,
            'raw_mac'     => $rawMac,
            'ssid'        => $ssid,
            'device_name' => $request->device_name,
            'location'    => $request->location,
            'description' => $request->description,
            'vlan_id'     => $request->vlan_id ?: null,
            'status'      => $request->status,
        ]);

        // Proses reservasi IP jika diminta
        $noReserveSsids = ['SJA REG', 'ALL'];
        if ($request->boolean('reserve_ip') && !in_array(strtoupper($ssid), $noReserveSsids)) {
            $subnetId  = $request->input('reserve_subnet_id');
            $ipAddress = trim($request->input('reserve_ip_address', ''));
            $hostname  = trim($request->input('reserve_hostname', $request->device_name));
            $desc      = trim($request->input('reserve_description', ''));

            if ($subnetId && $ipAddress && filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                // Cek duplikat IP di tabel
                $duplicate = DhcpStaticLease::where('ip_address', $ipAddress)->first();
                if (!$duplicate) {
                    $lease = DhcpStaticLease::create([
                        'dhcp_subnet_id' => $subnetId,
                        'device_id'      => $device->id,
                        'mac_address'    => $formattedMac,
                        'ip_address'     => $ipAddress,
                        'hostname'       => $hostname ?: $request->device_name,
                        'description'    => $desc ?: null,
                        'is_active'      => true,
                    ]);

                    $this->dhcpService->saveHostToKeaDb($lease->mac_address, $lease->ip_address, $lease->dhcp_subnet_id, $lease->hostname, $lease->id);
                    $this->dhcpService->syncConfigFile();
                }
            }
        }

        return redirect()->route('devices.index')->with('success', "Device {$formattedMac} ({$ssid}) successfully added.");
    }

    /**
     * Update existing Device
     */
    public function update(Request $request, int $id)
    {
        $device = Device::findOrFail($id);
        $rawMac = $request->input('mac_address');
        $ssid   = trim($request->input('ssid', 'ALL'));
        $formattedMac = Device::formatMacAddress($rawMac);

        if (!$formattedMac) {
            return redirect()->back()->with('error', 'Invalid MAC Address format!');
        }

        if (Device::isDuplicate($formattedMac, $ssid, $id)) {
            return redirect()->back()->with('error', "Duplicate MAC Address! {$formattedMac} is already assigned to SSID '{$ssid}'.");
        }

        $request->validate([
            'device_name' => 'required|string|max:100',
            'ssid'        => 'required|string|max:64',
            'location'    => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'vlan_id'     => 'nullable|integer|between:1,4094',
            'status'      => 'required|in:active,inactive',
        ]);

        $oldSsid = $device->ssid;
        $noReserveSsids = ['SJA REG', 'ALL'];
        $isNoReserveTarget = in_array(strtoupper($ssid), $noReserveSsids);

        if ($isNoReserveTarget) {
            // Jika SSID berubah ke SJA REG atau ALL, hapus reservasi yang terhubung ke device ini
            $this->dhcpService->deleteHostFromKeaDb($device->mac_address);
            DhcpStaticLease::where('device_id', $device->id)->delete();
            $this->dhcpService->syncConfigFile();
        } else {
            // SSID mengizinkan reservasi (misal SJA AP, DEVICE OPS AP, dll)
            if ($request->boolean('reserve_ip')) {
                $subnetId  = $request->input('reserve_subnet_id');
                $ipAddress = trim($request->input('reserve_ip_address', ''));
                $hostname  = trim($request->input('reserve_hostname', $request->device_name));
                $desc      = trim($request->input('reserve_description', ''));

                if ($subnetId && $ipAddress && filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                    // Cek duplikat IP di tabel untuk device lain
                    $duplicate = DhcpStaticLease::where('ip_address', $ipAddress)
                        ->where('device_id', '!=', $device->id)
                        ->first();

                    if ($duplicate) {
                        return redirect()->back()->with('error', "IP Address {$ipAddress} sudah digunakan oleh device lain!")->withInput();
                    }

                    if ($device->mac_address !== $formattedMac) {
                        $this->dhcpService->deleteHostFromKeaDb($device->mac_address);
                    }

                    $lease = DhcpStaticLease::updateOrCreate(
                        ['device_id' => $device->id],
                        [
                            'dhcp_subnet_id' => $subnetId,
                            'mac_address'    => $formattedMac,
                            'ip_address'     => $ipAddress,
                            'hostname'       => $hostname ?: $request->device_name,
                            'description'    => $desc ?: null,
                            'is_active'      => true,
                        ]
                    );

                    $this->dhcpService->saveHostToKeaDb($lease->mac_address, $lease->ip_address, $lease->dhcp_subnet_id, $lease->hostname, $lease->id);
                    $this->dhcpService->syncConfigFile();
                }
            } else {
                // User menonaktifkan reservasi IP untuk device ini
                $this->dhcpService->deleteHostFromKeaDb($device->mac_address);
                DhcpStaticLease::where('device_id', $device->id)->delete();
                $this->dhcpService->syncConfigFile();
            }
        }

        $device->update([
            'mac_address' => $formattedMac,
            'raw_mac'     => $rawMac,
            'ssid'        => $ssid,
            'device_name' => $request->device_name,
            'location'    => $request->location,
            'description' => $request->description,
            'vlan_id'     => $request->vlan_id ?: null,
            'status'      => $request->status,
        ]);

        return redirect()->route('devices.index')->with('success', "Device {$formattedMac} ({$ssid}) updated successfully.");
    }

    /**
     * Delete Device
     */
    public function destroy(int $id)
    {
        $device = Device::findOrFail($id);
        $mac    = $device->mac_address;

        // Bersihkan Static IP reservation terkait
        $this->dhcpService->deleteHostFromKeaDb($mac);
        DhcpStaticLease::where('device_id', $device->id)->delete();
        $this->dhcpService->syncConfigFile();

        $device->delete();

        return redirect()->route('devices.index')->with('success', "Device {$mac} deleted successfully.");
    }

    /**
     * Toggle Device Status (PATCH)
     */
    public function toggleStatus(int $id)
    {
        $device = Device::findOrFail($id);
        $device->status = $device->status === 'active' ? 'inactive' : 'active';
        $device->save();

        return redirect()->back()->with('success', "Device {$device->mac_address} status updated to {$device->status}.");
    }

    /**
     * Import CSV File with Multi-SSID Validation & Duplicate Skip
     * Format: MAC Address, SSID, Device Name, Location, Description, Status, VLAN ID
    /**
     * Import CSV File with Multi-SSID Validation, Line Normalization, Header Detection & Comprehensive Error Handling
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|max:4096',
        ]);

        try {
            $file = $request->file('csv_file');
            if (!$file) {
                return redirect()->route('devices.index')->with('error', 'No file uploaded.');
            }

            $path = $file->getRealPath() ?: $file->getPathname();
            $rawContent = @file_get_contents($path);

            if ($rawContent === false || strlen(trim($rawContent)) === 0) {
                return redirect()->route('devices.index')->with('error', 'Could not read CSV file or file is empty.');
            }

            // Strip UTF-8 BOM if present
            $rawContent = preg_replace('/^\xEF\xBB\xBF/', '', $rawContent);

            // Detect & Convert Encoding (UTF-16LE, UTF-16BE, Windows-1252, ANSI to UTF-8)
            $encoding = mb_detect_encoding($rawContent, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'UTF-16', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $rawContent = mb_convert_encoding($rawContent, 'UTF-8', $encoding);
            }

            // Clean null bytes & standardize line endings
            $rawContent = str_replace("\x00", '', $rawContent);
            $rawContent = str_replace(["\r\n", "\r"], "\n", $rawContent);

            // Auto-detect delimiter from the first line
            $firstLine = strtok($rawContent, "\n") ?: '';
            $delimiter = ',';
            if (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) {
                $delimiter = ';';
            } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
                $delimiter = "\t";
            }

            // Stream parse content using temp memory buffer and fgetcsv
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $rawContent);
            rewind($stream);

            $rows = [];
            while (($row = fgetcsv($stream, 0, $delimiter)) !== false) {
                // Filter out empty rows
                if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) {
                    continue;
                }
                $rows[] = array_map('trim', $row);
            }
            fclose($stream);

            if (empty($rows)) {
                return redirect()->route('devices.index')->with('error', 'CSV file contains no valid data rows.');
            }

            // Helper to check if a string looks like a MAC address (12 hex characters)
            $isMacString = function ($val) {
                $hexOnly = preg_replace('/[^a-fA-F0-9]/', '', (string)$val);
                return strlen($hexOnly) === 12;
            };

            $firstRowData = $rows[0];
            $firstCell    = $firstRowData[0] ?? '';

            // Check if first row is header or actual data
            $isHeader = !$isMacString($firstCell);

            $macIdx  = 0;
            $ssidIdx = 1;
            $nameIdx = 2;
            $locIdx  = 3;
            $descIdx = 4;
            $statIdx = 5;
            $vlanIdx = 6;
            $ipIdx   = null;
            $subIdx  = null;

            if ($isHeader) {
                $headers = array_map(function($h) {
                    return strtolower(trim(preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF]/u', '', $h)));
                }, $firstRowData);

                foreach ($headers as $idx => $hName) {
                    if (str_contains($hName, 'mac')) {
                        $macIdx = $idx;
                    } elseif (str_contains($hName, 'ssid')) {
                        $ssidIdx = $idx;
                    } elseif (str_contains($hName, 'name') || str_contains($hName, 'device')) {
                        $nameIdx = $idx;
                    } elseif (str_contains($hName, 'loc')) {
                        $locIdx = $idx;
                    } elseif (str_contains($hName, 'desc')) {
                        $descIdx = $idx;
                    } elseif (str_contains($hName, 'stat')) {
                        $statIdx = $idx;
                    } elseif (str_contains($hName, 'vlan')) {
                        $vlanIdx = $idx;
                    } elseif (str_contains($hName, 'ip') || str_contains($hName, 'address')) {
                        $ipIdx = $idx;
                    } elseif (str_contains($hName, 'sub') || str_contains($hName, 'pool')) {
                        $subIdx = $idx;
                    }
                }
                array_shift($rows); // Remove header row from dataset
            }

            set_time_limit(300); // 5 minutes execution limit for large CSVs
            ini_set('memory_limit', '512M');

            // Load existing MAC + SSID combinations and Master SSIDs into memory for fast lookup
            $existingDevices = Device::select('mac_address', 'ssid')->get()
                ->mapWithKeys(fn($item) => [$item->mac_address . '___' . $item->ssid => true])
                ->toArray();

            $existingSsids      = Ssid::pluck('ssid_name')->mapWithKeys(fn($s) => [strtoupper($s) => true])->toArray();
            $existingStaticIps  = DhcpStaticLease::pluck('ip_address')->mapWithKeys(fn($ip) => [trim($ip) => true])->toArray();
            $existingStaticMacs = DhcpStaticLease::pluck('mac_address')->mapWithKeys(fn($m) => [strtoupper(str_replace(['-', '.'], ':', trim($m))) => true])->toArray();
            $allSubnets         = DhcpSubnet::all();

            $importedCount       = 0;
            $reservedIpCount     = 0;
            $skippedCount        = 0;
            $invalidCount        = 0;

            $devicesToInsert     = [];
            $newSsidsToInsert    = [];
            $pendingReservations = []; // [$formattedMac => ['ip' => ..., 'subnet_id' => ..., 'hostname' => ..., 'desc' => ...]]
            $now                 = now();

            foreach ($rows as $row) {
                // Determine raw MAC by first checking designated macIdx, or fallback to searching first cell matching 12 hex chars
                $rawMac = trim($row[$macIdx] ?? '');
                if (empty($rawMac) || !Device::formatMacAddress($rawMac)) {
                    foreach ($row as $cell) {
                        $cellTrim = trim($cell);
                        if (Device::formatMacAddress($cellTrim)) {
                            $rawMac = $cellTrim;
                            break;
                        }
                    }
                }

                if (empty($rawMac)) {
                    continue;
                }

                $formattedMac = Device::formatMacAddress($rawMac);
                if (!$formattedMac) {
                    $invalidCount++;
                    continue;
                }

                $ssid        = trim($row[$ssidIdx] ?? 'ALL');
                $deviceName  = trim($row[$nameIdx] ?? 'Imported Device');
                $location    = isset($row[$locIdx]) ? trim($row[$locIdx]) : '';
                $description = isset($row[$descIdx]) ? trim($row[$descIdx]) : 'CSV Import';
                $statusVal   = isset($row[$statIdx]) ? strtolower(trim($row[$statIdx])) : 'active';
                $status      = ($statusVal === 'inactive' || $statusVal === 'blocked') ? 'inactive' : 'active';
                $vlanRaw     = isset($row[$vlanIdx]) ? trim($row[$vlanIdx]) : '';
                $vlanId      = (is_numeric($vlanRaw) && (int)$vlanRaw >= 1 && (int)$vlanRaw <= 4094) ? (int)$vlanRaw : null;
                $rawIp       = $ipIdx !== null && isset($row[$ipIdx]) ? trim($row[$ipIdx]) : '';
                $rawSub      = $subIdx !== null && isset($row[$subIdx]) ? trim($row[$subIdx]) : '';

                $ssidKey = empty($ssid) ? 'ALL' : $ssid;
                $dupKey  = $formattedMac . '___' . $ssidKey;
                $isValidIp = !empty($rawIp) && filter_var($rawIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);

                if (isset($existingDevices[$dupKey])) {
                    // Jika device sudah ada, cek apakah user menyertakan IP dan belum memiliki reservasi static
                    if ($isValidIp && !isset($existingStaticIps[$rawIp]) && !isset($existingStaticMacs[$formattedMac])) {
                        $matchedSubnet = DhcpSubnet::findByHintOrIp($rawSub ?: (string)$vlanId, $rawIp, $allSubnets);
                        if ($matchedSubnet) {
                            $pendingReservations[$formattedMac] = [
                                'ip'        => $rawIp,
                                'subnet_id' => $matchedSubnet->id,
                                'hostname'  => $deviceName,
                                'desc'      => $description,
                            ];
                            $existingStaticIps[$rawIp] = true;
                            $existingStaticMacs[$formattedMac] = true;
                        }
                    }
                    $skippedCount++;
                    continue;
                }

                // Register duplicate key in memory to prevent duplicates within the same CSV file
                $existingDevices[$dupKey] = true;

                // Collect SSIDs to insert if not existing yet
                if (!empty($ssidKey) && strtoupper($ssidKey) !== 'ALL' && !isset($existingSsids[strtoupper($ssidKey)])) {
                    $newSsidsToInsert[strtoupper($ssidKey)] = [
                        'ssid_name'   => $ssidKey,
                        'description' => 'Auto-created via CSV Import',
                        'status'      => 'active',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                    $existingSsids[strtoupper($ssidKey)] = true;
                }

                $devicesToInsert[] = [
                    'mac_address' => $formattedMac,
                    'raw_mac'     => $rawMac,
                    'ssid'        => $ssidKey,
                    'device_name' => empty($deviceName) ? 'Imported Device' : $deviceName,
                    'location'    => $location,
                    'description' => $description,
                    'vlan_id'     => $vlanId,
                    'status'      => $status,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];

                // Jika IP valid disertakan dan belum direservasi, simpan ke antrian reservasi
                if ($isValidIp && !isset($existingStaticIps[$rawIp]) && !isset($existingStaticMacs[$formattedMac])) {
                    $matchedSubnet = DhcpSubnet::findByHintOrIp($rawSub ?: (string)$vlanId, $rawIp, $allSubnets);
                    if ($matchedSubnet) {
                        $pendingReservations[$formattedMac] = [
                            'ip'        => $rawIp,
                            'subnet_id' => $matchedSubnet->id,
                            'hostname'  => $deviceName,
                            'desc'      => $description,
                        ];
                        $existingStaticIps[$rawIp] = true;
                        $existingStaticMacs[$formattedMac] = true;
                    }
                }

                $importedCount++;
            }

            // Perform DB insertions in a single transaction with chunking
            \Illuminate\Support\Facades\DB::transaction(function() use ($newSsidsToInsert, $devicesToInsert, $pendingReservations, &$reservedIpCount, $now) {
                if (!empty($newSsidsToInsert)) {
                    Ssid::insert(array_values($newSsidsToInsert));
                }

                if (!empty($devicesToInsert)) {
                    foreach (array_chunk($devicesToInsert, 500) as $chunk) {
                        Device::insert($chunk);
                    }
                }

                // Buat static leases jika ada antrian reservasi IP
                if (!empty($pendingReservations)) {
                    $macs = array_keys($pendingReservations);
                    $deviceIds = Device::whereIn('mac_address', $macs)->pluck('id', 'mac_address')->toArray();

                    $staticLeasesToInsert = [];
                    foreach ($pendingReservations as $mac => $res) {
                        $devId = $deviceIds[$mac] ?? null;
                        $staticLeasesToInsert[] = [
                            'dhcp_subnet_id' => $res['subnet_id'],
                            'device_id'      => $devId,
                            'mac_address'    => $mac,
                            'ip_address'     => $res['ip'],
                            'hostname'       => !empty($res['hostname']) ? $res['hostname'] : null,
                            'description'    => !empty($res['desc']) ? $res['desc'] : 'Device CSV Import',
                            'is_active'      => true,
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ];
                        $reservedIpCount++;
                    }

                    foreach (array_chunk($staticLeasesToInsert, 500) as $chunk) {
                        DhcpStaticLease::insert($chunk);
                    }
                }
            });

            // Sinkronkan ke Kea DHCP jika ada reservasi static baru yang dibuat
            if ($reservedIpCount > 0) {
                $this->dhcpService->syncAllStaticLeasesToKeaDb();
                $this->dhcpService->syncConfigFile();
            }

            $msg = "Import completed! Added: {$importedCount} devices";
            if ($reservedIpCount > 0) {
                $msg .= ", {$reservedIpCount} static IP reservations created";
            }
            $msg .= ", Skipped duplicates: {$skippedCount}, Invalid MAC formats: {$invalidCount}.";
            return redirect()->route('devices.index')->with('success', $msg);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('CSV Import Error: ' . $e->getMessage());
            return redirect()->route('devices.index')->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download Template CSV untuk Import Devices (dengan opsi Reservasi IP)
     */
    public function templateCsv()
    {
        $fileName = 'devices_import_template.csv';
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

            fputcsv($file, ['MAC Address', 'SSID', 'Device Name', 'Location', 'Description', 'Status', 'VLAN ID', 'IP Address', 'Subnet']);
            fputcsv($file, ['00:11:22:33:44:55', 'CORP-WIFI', 'AP Lantai 1', 'Lobby', 'Access Point Lobby', 'active', '10', '192.168.10.50', 'POOL-CORP']);
            fputcsv($file, ['AA:BB:CC:DD:EE:FF', 'GUEST-WIFI', 'Tablet Resepsionis', 'Resepsionis', 'Device Resepsionis', 'active', '20', '192.168.20.10', 'POOL-GUEST']);
            fputcsv($file, ['11:22:33:44:55:66', 'ALL', 'Laptop Staff IT', 'Ruang IT', 'Tanpa Reservasi IP', 'active', '', '', '']);
            fclose($file);
        }, 200, $headers);
    }

    /**
     * Export Devices to CSV including VLAN ID and Reserved IP
     */
    public function exportCsv()
    {
        $devices     = Device::with(['staticLease.subnet'])->get();
        $csvFileName = 'mac_devices_export_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$csvFileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $callback = function() use ($devices) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // Header row
            fputcsv($file, ['MAC Address', 'Target SSID', 'Device Name', 'Location', 'Description', 'Status', 'VLAN ID', 'Reserved IP', 'Subnet Pool', 'ID', 'Created At']);

            foreach ($devices as $d) {
                fputcsv($file, [
                    $d->mac_address,
                    $d->ssid,
                    $d->device_name,
                    $d->location,
                    $d->description,
                    $d->status,
                    $d->vlan_id ?? '',
                    $d->staticLease ? $d->staticLease->ip_address : '',
                    $d->staticLease && $d->staticLease->subnet ? $d->staticLease->subnet->name : '',
                    $d->id,
                    $d->created_at,
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Suggest IP kosong dalam range subnet
     * Mendukung subnet dengan dynamic pool maupun subnet static-only (tanpa pool).
     * Jika static-only: menyarankan IP setelah reservasi terakhir, atau mulai dari x.x.x.10 jika kosong.
     */
    public function suggestIp(Request $request): \Illuminate\Http\JsonResponse
    {
        $subnetId = (int)$request->input('subnet_id');
        if (!$subnetId) {
            return response()->json(['suggested_ip' => null, 'error' => 'No subnet_id']);
        }

        $subnet = DhcpSubnet::find($subnetId);
        if (!$subnet) {
            return response()->json(['suggested_ip' => null, 'error' => 'Subnet not found']);
        }

        // Ambil semua IP static yang sudah terpakai di subnet ini
        $usedIps = DhcpStaticLease::where('dhcp_subnet_id', $subnetId)
            ->pluck('ip_address')
            ->map(fn($ip) => ip2long(trim($ip)))
            ->filter(fn($v) => $v !== false)
            ->values()
            ->toArray();

        // Gateway subnet agar tidak disarankan
        $gatewayLong = !empty($subnet->gateway) ? ip2long(trim($subnet->gateway)) : false;

        // Hitung network & broadcast dari CIDR (misal 192.168.10.0/24)
        $networkLong = false;
        $broadcastLong = false;
        if (!empty($subnet->subnet) && str_contains($subnet->subnet, '/')) {
            [$baseIpStr, $prefixStr] = explode('/', trim($subnet->subnet), 2);
            $baseLong = ip2long(trim($baseIpStr));
            $prefix   = (int)trim($prefixStr);
            if ($baseLong !== false && $prefix >= 8 && $prefix <= 30) {
                $mask          = $prefix === 0 ? 0 : (-1 << (32 - $prefix));
                $networkLong   = $baseLong & $mask;
                $broadcastLong = $networkLong | (~$mask & 0xFFFFFFFF);
            }
        }

        $suggested = null;

        // KASUS 1: Subnet memiliki Dynamic Pool Range terdefinisi
        if (!empty($subnet->pool_start) && !empty($subnet->pool_end)) {
            $startLong = ip2long(trim($subnet->pool_start));
            $endLong   = ip2long(trim($subnet->pool_end));

            if ($startLong !== false && $endLong !== false && $endLong >= $startLong) {
                for ($i = $startLong; $i <= $endLong; $i++) {
                    if (!in_array($i, $usedIps) && $i !== $gatewayLong) {
                        $suggested = long2ip($i);
                        break;
                    }
                }
            }
        }

        // KASUS 2: Subnet Static-Only (tidak punya pool range) atau pool belum menghasilkan IP
        if (!$suggested && $networkLong !== false && $broadcastLong !== false) {
            if (!empty($usedIps)) {
                // Ada reservasi sebelumnya: ambil IP tertinggi + 1
                $maxUsed = max($usedIps);
                $candidate = $maxUsed + 1;
                while ((in_array($candidate, $usedIps) || $candidate === $gatewayLong) && $candidate < $broadcastLong) {
                    $candidate++;
                }

                if ($candidate < $broadcastLong && $candidate > $networkLong) {
                    $suggested = long2ip($candidate);
                }
            }

            // Jika masih belum dapat (atau belum ada reservasi sama sekali): mulai dari x.x.x.10
            if (!$suggested) {
                $candidate = $networkLong + 10;
                while ((in_array($candidate, $usedIps) || $candidate === $gatewayLong) && $candidate < $broadcastLong) {
                    $candidate++;
                }

                if ($candidate < $broadcastLong && $candidate > $networkLong) {
                    $suggested = long2ip($candidate);
                }
            }
        }

        return response()->json([
            'suggested_ip'   => $suggested,
            'subnet_name'    => $subnet->name,
            'is_static_only' => empty($subnet->pool_start) || empty($subnet->pool_end),
        ]);
    }
}
