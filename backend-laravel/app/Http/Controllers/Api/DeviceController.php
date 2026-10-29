<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    /**
     * Get All Devices
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit', 15);
        $devices = Device::latest()->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => $devices
        ]);
    }

    /**
     * Store New Device
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mac_address' => [
                'required',
                'string',
                'unique:devices,mac_address',
                // Regex validasi format MAC Address umum
                'regex:/^([0-9a-fA-F]{2}[:-]?){5}([0-9a-fA-F]{2})$/'
            ],
            'device_name' => 'required|string|max:100',
            'location'    => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status'      => 'in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $device = Device::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Device successfully created.',
            'data' => $device
        ], 201);
    }

    /**
     * Update Existing Device
     */
    public function update(Request $request, $id)
    {
        $device = Device::find($id);

        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Device not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'mac_address' => [
                'sometimes',
                'string',
                'unique:devices,mac_address,' . $device->id,
                'regex:/^([0-9a-fA-F]{2}[:-]?){5}([0-9a-fA-F]{2})$/'
            ],
            'device_name' => 'sometimes|string|max:100',
            'location'    => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status'      => 'in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $device->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Device successfully updated.',
            'data' => $device
        ]);
    }

    /**
     * Delete Device
     */
    public function destroy($id)
    {
        $device = Device::find($id);

        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Device not found.'], 404);
        }

        $device->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Device successfully deleted.'
        ]);
    }
}