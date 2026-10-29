<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;

class DeviceWebController extends Controller
{
    public function index()
    {
        $devices = Device::latest()->get();
        return view('devices.index', compact('devices'));
    }

    public function create()
    {
        return view('devices.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'mac_address' => ['required', 'string', 'unique:devices,mac_address', 'regex:/^([0-9a-fA-F]{2}[:-]?){5}([0-9a-fA-F]{2})$/'],
            'device_name' => 'required|string|max:100',
            'location'    => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive'
        ], [
            'mac_address.regex' => 'Format MAC Address tidak valid.'
        ]);

        Device::create($request->all());

        return redirect()->route('devices.index')->with('success', 'Device berhasil ditambahkan.');
    }

    public function edit(Device $device)
    {
        return view('devices.edit', compact('device'));
    }

    public function update(Request $request, Device $device)
    {
        $request->validate([
            'mac_address' => ['required', 'string', 'unique:devices,mac_address,' . $device->id, 'regex:/^([0-9a-fA-F]{2}[:-]?){5}([0-9a-fA-F]{2})$/'],
            'device_name' => 'required|string|max:100',
            'location'    => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive'
        ]);

        $device->update($request->all());

        return redirect()->route('devices.index')->with('success', 'Device berhasil diperbarui.');
    }

    public function destroy(Device $device)
    {
        $device->delete();
        return redirect()->route('devices.index')->with('success', 'Device berhasil dihapus.');
    }
}