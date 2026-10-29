<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class DeviceImportExportController extends Controller
{
    public function importForm()
    {
        return view('devices.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $fileData = file_get_contents($file);
        $rows = array_map('str_getcsv', explode("\n", $fileData));
        $header = array_shift($rows); // Hapus baris pertama (header)

        $successCount = 0;
        $skipCount = 0;

        foreach ($rows as $row) {
            if (count($row) < 5) continue; // Pastikan kolom lengkap

            $mac = trim($row[0]);
            $name = trim($row[1]);
            $loc = trim($row[2]);
            $desc = trim($row[3]);
            $status = strtolower(trim($row[4]));

            // Validasi format MAC
            if (!preg_match('/^([0-9a-fA-F]{2}[:-]?){5}([0-9a-fA-F]{2})$/', $mac)) {
                $skipCount++;
                continue;
            }

            // Normalisasi sementara untuk cek duplikat (Mutator Laravel akan handle saat save)
            $normalizedMac = strtoupper(str_replace(['-', '.'], ':', $mac));

            if (Device::where('mac_address', $normalizedMac)->exists()) {
                $skipCount++;
                continue;
            }

            Device::create([
                'mac_address' => $mac,
                'device_name' => $name,
                'location'    => $loc,
                'description' => $desc,
                'status'      => in_array($status, ['active', 'inactive']) ? $status : 'active',
            ]);

            $successCount++;
        }

        return redirect()->route('devices.index')->with('success', "Import selesai. Berhasil: $successCount, Dilewati (Duplikat/Invalid): $skipCount");
    }

    public function export()
    {
        $devices = Device::all();
        $filename = "devices_" . date('Y-m-d_H-i') . ".csv";

        $handle = fopen('php://memory', 'w');
        fputcsv($handle, ['MAC Address', 'Device Name', 'Location', 'Description', 'Status']);

        foreach ($devices as $device) {
            fputcsv($handle, [
                $device->mac_address,
                $device->device_name,
                $device->location,
                $device->description,
                $device->status
            ]);
        }

        fseek($handle, 0);
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        return Response::stream(function () use ($handle) {
            fpassthru($handle);
        }, 200, $headers);
    }
}