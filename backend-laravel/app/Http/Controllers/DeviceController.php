<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Ssid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class DeviceController extends Controller
{
    /**
     * Display listing of devices with Search, SSID Filter & Pagination
     */
    public function index(Request $request)
    {
        $query = Device::query();

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

        $devices        = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $availableSsids = Device::distinct()->pluck('ssid')->toArray();

        return view('devices.index', compact('devices', 'availableSsids'));
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

        Device::create([
            'mac_address' => $formattedMac,
            'raw_mac'     => $rawMac,
            'ssid'        => $ssid,
            'device_name' => $request->device_name,
            'location'    => $request->location,
            'description' => $request->description,
            'vlan_id'     => $request->vlan_id ?: null,
            'status'      => $request->status,
        ]);

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
     * Import CSV File with Multi-SSID Validation, Line Normalization & Comprehensive Error Handling
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

            $path    = $file->getRealPath() ?: $file->getPathname();
            $content = @file_get_contents($path);

            if ($content === false || strlen(trim($content)) === 0) {
                return redirect()->route('devices.index')->with('error', 'Could not read CSV file or file is empty.');
            }

            // Strip UTF-8 BOM if present
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

            // Detect & Convert Encoding (UTF-16LE, UTF-16BE, Windows-1252, ANSI to UTF-8)
            $encoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'UTF-16', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }

            // Clean null bytes
            $content = str_replace("\x00", '', $content);

            // Standardize line endings (\r\n, \r -> \n)
            $content = str_replace(["\r\n", "\r"], "\n", $content);
            $rawLines = explode("\n", $content);
            $lines = [];
            foreach ($rawLines as $l) {
                if (strlen(trim($l)) > 0) {
                    $lines[] = trim($l);
                }
            }

            if (empty($lines)) {
                return redirect()->route('devices.index')->with('error', 'CSV file contains no valid data lines.');
            }

            // Auto detect delimiter (; vs , vs \t)
            $firstLine = $lines[0];
            $delimiter = ',';
            if (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) {
                $delimiter = ';';
            } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
                $delimiter = "\t";
            }

            $firstRowData = str_getcsv($firstLine, $delimiter);
            $firstCell    = trim($firstRowData[0] ?? '');

            // Helper to check if string contains 12 hex characters (valid MAC)
            $isMacString = function($val) {
                $hexOnly = preg_replace('/[^a-fA-F0-9]/', '', (string)$val);
                return strlen($hexOnly) === 12;
            };

            // Check if first row is a header or actual MAC data
            $isHeader   = !$isMacString($firstCell);
            $startIndex = $isHeader ? 1 : 0;

            $macIdx  = 0;
            $ssidIdx = 1;
            $nameIdx = 2;
            $locIdx  = 3;
            $descIdx = 4;
            $statIdx = 5;
            $vlanIdx = 6;

            if ($isHeader) {
                $headers = array_map(function($h) { return strtolower(trim($h)); }, $firstRowData);
                foreach ($headers as $idx => $hName) {
                    if (str_contains($hName, 'mac')) $macIdx = $idx;
                    elseif (str_contains($hName, 'ssid')) $ssidIdx = $idx;
                    elseif (str_contains($hName, 'name') || str_contains($hName, 'device')) $nameIdx = $idx;
                    elseif (str_contains($hName, 'loc')) $locIdx = $idx;
                    elseif (str_contains($hName, 'desc')) $descIdx = $idx;
                    elseif (str_contains($hName, 'stat')) $statIdx = $idx;
                    elseif (str_contains($hName, 'vlan')) $vlanIdx = $idx;
                }
            }

            $importedCount = 0;
            $skippedCount  = 0;
            $invalidCount  = 0;

            for ($i = $startIndex; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                if (empty($line)) continue;

                $row = str_getcsv($line, $delimiter);

                $rawMac      = trim($row[$macIdx] ?? '');
                $ssid        = trim($row[$ssidIdx] ?? 'ALL');
                $deviceName  = trim($row[$nameIdx] ?? 'Imported Device');
                $location    = isset($row[$locIdx]) ? trim($row[$locIdx]) : '';
                $description = isset($row[$descIdx]) ? trim($row[$descIdx]) : 'CSV Import';
                $statusVal   = isset($row[$statIdx]) ? strtolower(trim($row[$statIdx])) : 'active';
                $status      = ($statusVal === 'inactive' || $statusVal === 'blocked') ? 'inactive' : 'active';
                $vlanId      = isset($row[$vlanIdx]) && is_numeric(trim($row[$vlanIdx])) ? (int)trim($row[$vlanIdx]) : null;

                if (empty($rawMac)) continue;

                $formattedMac = Device::formatMacAddress($rawMac);

                if (!$formattedMac) {
                    $invalidCount++;
                    continue;
                }

                if (Device::isDuplicate($formattedMac, $ssid)) {
                    $skippedCount++;
                    continue;
                }

                Device::create([
                    'mac_address' => $formattedMac,
                    'raw_mac'     => $rawMac,
                    'ssid'        => empty($ssid) ? 'ALL' : $ssid,
                    'device_name' => empty($deviceName) ? 'Imported Device' : $deviceName,
                    'location'    => $location,
                    'description' => $description,
                    'vlan_id'     => ($vlanId >= 1 && $vlanId <= 4094) ? $vlanId : null,
                    'status'      => $status,
                ]);

                $importedCount++;
            }

            $msg = "Import completed! Added: {$importedCount}, Skipped duplicates: {$skippedCount}, Invalid formats: {$invalidCount}.";
            return redirect()->route('devices.index')->with('success', $msg);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('CSV Import Error: ' . $e->getMessage());
            return redirect()->route('devices.index')->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Export Devices to CSV including VLAN ID
     */
    public function exportCsv()
    {
        $devices     = Device::all();
        $csvFileName = 'mac_devices_export_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$csvFileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $callback = function() use ($devices) {
            $file = fopen('php://output', 'w');
            // Header row
            fputcsv($file, ['MAC Address', 'Target SSID', 'Device Name', 'Location', 'Description', 'Status', 'VLAN ID', 'ID', 'Created At']);

            foreach ($devices as $d) {
                fputcsv($file, [
                    $d->mac_address,
                    $d->ssid,
                    $d->device_name,
                    $d->location,
                    $d->description,
                    $d->status,
                    $d->vlan_id ?? '',
                    $d->id,
                    $d->created_at,
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
