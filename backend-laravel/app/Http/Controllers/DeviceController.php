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
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file   = $request->file('csv_file');
        $handle = fopen($file->getPathname(), 'r');
        $headerRow = fgetcsv($handle);
        $headers   = array_map(function($h) { return strtolower(trim($h)); }, $headerRow ?: []);

        // Dynamic Header Index Detection
        $macIdx   = array_search('mac address', $headers) !== false ? array_search('mac address', $headers) : (array_search('mac', $headers) !== false ? array_search('mac', $headers) : 0);
        $ssidIdx  = array_search('target ssid', $headers) !== false ? array_search('target ssid', $headers) : (array_search('ssid', $headers) !== false ? array_search('ssid', $headers) : 1);
        $nameIdx  = array_search('device name', $headers) !== false ? array_search('device name', $headers) : (array_search('name', $headers) !== false ? array_search('name', $headers) : (array_search('device', $headers) !== false ? array_search('device', $headers) : 2));
        $locIdx   = array_search('location', $headers) !== false ? array_search('location', $headers) : 3;
        $descIdx  = array_search('description', $headers) !== false ? array_search('description', $headers) : 4;
        $statIdx  = array_search('status', $headers) !== false ? array_search('status', $headers) : 5;
        $vlanIdx  = array_search('vlan id', $headers) !== false ? array_search('vlan id', $headers) : (array_search('vlan', $headers) !== false ? array_search('vlan', $headers) : 6);

        $importedCount = 0;
        $skippedCount  = 0;
        $invalidCount  = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row) || !isset($row[$macIdx])) continue;

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

        fclose($handle);

        $msg = "Import completed! Added: {$importedCount}, Skipped duplicates: {$skippedCount}, Invalid formats: {$invalidCount}.";
        return redirect()->route('devices.index')->with('success', $msg);
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
