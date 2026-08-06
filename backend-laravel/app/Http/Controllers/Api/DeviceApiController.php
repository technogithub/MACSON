<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceApiController extends Controller
{
    /**
     * GET /api/device
     * Retrieve list of registered devices
     */
    public function index(Request $request)
    {
        $query = Device::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $devices = $query->orderBy('id', 'desc')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $devices
        ], 200);
    }

    /**
     * POST /api/device
     * Create new device via API
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mac_address' => 'required|string',
            'device_name' => 'required|string|max:100',
            'location' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $formattedMac = Device::formatMacAddress($request->mac_address);
        if (!$formattedMac) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid MAC Address format'
            ], 400);
        }

        if (Device::isDuplicate($formattedMac)) {
            return response()->json([
                'status' => 'error',
                'message' => "Duplicate MAC Address {$formattedMac} already exists"
            ], 409);
        }

        $device = Device::create([
            'mac_address' => $formattedMac,
            'raw_mac' => $request->mac_address,
            'device_name' => $request->device_name,
            'location' => $request->location,
            'description' => $request->description,
            'status' => $request->status
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Device created successfully',
            'data' => $device
        ], 201);
    }

    /**
     * PUT /api/device/{id}
     * Update device via API
     */
    public function update(Request $request, int $id)
    {
        $device = Device::find($id);

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'mac_address' => 'sometimes|required|string',
            'device_name' => 'sometimes|required|string|max:100',
            'location' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        if ($request->has('mac_address')) {
            $formattedMac = Device::formatMacAddress($request->mac_address);
            if (!$formattedMac) {
                return response()->json(['status' => 'error', 'message' => 'Invalid MAC Address format'], 400);
            }
            if (Device::isDuplicate($formattedMac, 'ALL', $id)) {
                return response()->json(['status' => 'error', 'message' => "MAC Address {$formattedMac} already belongs to another device"], 409);
            }
            $device->mac_address = $formattedMac;
            $device->raw_mac = $request->mac_address;
        }

        if ($request->has('device_name')) $device->device_name = $request->device_name;
        if ($request->has('location')) $device->location = $request->location;
        if ($request->has('description')) $device->description = $request->description;
        if ($request->has('status')) $device->status = $request->status;

        $device->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Device updated successfully',
            'data' => $device
        ], 200);
    }

    /**
     * DELETE /api/device/{id}
     * Remove device via API
     */
    public function destroy(int $id)
    {
        $device = Device::find($id);
        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Device not found'], 404);
        }

        $device->delete();
        return response()->json(['status' => 'success', 'message' => 'Device deleted successfully'], 200);
    }

    /**
     * GET /api/device/{id}
     * Show device via API
     */
    public function show(int $id)
    {
        $device = Device::find($id);
        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Device not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $device
        ], 200);
    }

    /**
     * POST /api/device/{mac}/verify
     * Verify device by MAC
     */
    public function verify(Request $request, string $mac)
    {
        $formattedMac = Device::formatMacAddress($mac);
        if (!$formattedMac) {
            return response()->json(['status' => 'error', 'message' => 'Invalid MAC Address format'], 400);
        }

        $device = Device::where('mac_address', $formattedMac)->first();
        
        if (!$device || $device->status !== 'active') {
            return response()->json(['status' => 'error', 'message' => 'Device not found or inactive'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $device
        ], 200);
    }
}
