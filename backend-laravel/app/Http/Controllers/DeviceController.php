<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

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

        $devices = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();
        $availableSsids = Device::distinct()->pluck('ssid')->toArray();

        return view('devices.index', compact('devices', 'availableSsids'));
    }

    /**
     * Store new Device with MAC & SSID validation & Duplicate check
     */
    public function store(Request $request)
    {
        $rawMac = $request->input('mac_address');
        $ssid = trim($request->input('ssid', 'ALL'));
        $formattedMac = Device::formatMacAddress($rawMac);

        if (!$formattedMac) {
            return redirect()->back()->with('error', 'Invalid MAC Address format! Allowed formats: AA:BB:CC:DD:EE:FF, AA-BB-CC-DD-EE-FF, aabbccddeeff')->withInput();
        }

        if (Device::isDuplicate($formattedMac, $ssid)) {
            return redirect()->back()->with('error', "Duplicate MAC Address! MAC {$formattedMac} already registered for SSID '{$ssid}'.")->withInput();
        }

        $request->validate([
            'device_name' => 'required|string|max:100',
            'ssid' => 'required|string|max:64',
            'location' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        Device::create([
            'mac_address' => $formattedMac,
            'raw_mac' => $rawMac,
            'ssid' => $ssid,
            'device_name' => $request->device_name,
            'location' => $request->location,
            'description' => $request->description,
            'status' => $request->status
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
        $ssid = trim($request->input('ssid', 'ALL'));
        $formattedMac = Device::formatMacAddress($rawMac);

        if (!$formattedMac) {
            return redirect()->back()->with('error', 'Invalid MAC Address format!');
        }

        if (Device::isDuplicate($formattedMac, $ssid, $id)) {
            return redirect()->back()->with('error', "Duplicate MAC Address! {$formattedMac} is already assigned to SSID '{$ssid}'.");
        }

        $request->validate([
            'device_name' => 'required|string|max:100',
            'ssid' => 'required|string|max:64',
            'location' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        $device->update([
            'mac_address' => $formattedMac,
            'raw_mac' => $rawMac,
            'ssid' => $ssid,
            'device_name' => $request->device_name,
            'location' => $request->location,
            'description' => $request->description,
            'status' => $request->status
        ]);

        return redirect()->route('devices.index')->with('success', "Device {$formattedMac} ({$ssid}) updated successfully.");
    }

    /**
     * Delete Device
     */
    public function destroy(int $id)
    {
        $device = Device::findOrFail($id);
        $mac = $device->mac_address;
        $device->delete();

        return redirect()->route('devices.index')->with('success', "Device {$mac} deleted successfully.");
    }

    /**
     * Toggle Device Status
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
     * Format: MAC Address,SSID,Device Name,Location,Description,Status
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle); // Header: MAC Address,SSID,Device Name,Location,Description,Status

        $importedCount = 0;
        $skippedCount = 0;
        $invalidCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) continue;

            $rawMac = trim($row[0]);
            $ssid = trim($row[1] ?? 'ALL');
            $deviceName = trim($row[2] ?? 'Imported Device');
            $location = trim($row[3] ?? '');
            $description = trim($row[4] ?? 'CSV Import');
            $status = strtolower(trim($row[5] ?? 'active')) === 'inactive' ? 'inactive' : 'active';

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
                'raw_mac' => $rawMac,
                'ssid' => empty($ssid) ? 'ALL' : $ssid,
                'device_name' => $deviceName,
                'location' => $location,
                'description' => $description,
                'status' => $status
            ]);

            $importedCount++;
        }

        fclose($handle);

        $msg = "Import completed! Successfully added: {$importedCount}, Skipped duplicates: {$skippedCount}, Invalid formats: {$invalidCount}.";
        return redirect()->route('devices.index')->with('success', $msg);
    }

    /**
     * Export Devices to CSV including SSID
     */
    public function exportCsv()
    {
        $devices = Device::all();
        $csvFileName = 'devices_export_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$csvFileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($devices) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'MAC Address', 'Target SSID', 'Device Name', 'Location', 'Description', 'Status', 'Created At']);

            foreach ($devices as $d) {
                fputcsv($file, [$d->id, $d->mac_address, $d->ssid, $d->device_name, $d->location, $d->description, $d->status, $d->created_at]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
