<?php

namespace App\Http\Controllers;

use App\Models\Ssid;
use Illuminate\Http\Request;

class SsidController extends Controller
{
    /**
     * Display listing of SSIDs with dynamic VLAN tags
     */
    public function index(Request $request)
    {
        $query = Ssid::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('ssid_name', 'like', "%{$search}%")
                  ->orWhere('vlan_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $ssids = $query->withCount('devices')->orderBy('id', 'asc')->paginate(15)->withQueryString();

        return view('ssids.index', compact('ssids'));
    }

    /**
     * Store a newly created SSID definition
     */
    public function store(Request $request)
    {
        $request->validate([
            'ssid_name' => 'required|string|max:64|unique:ssids,ssid_name',
            'vlan_id' => 'nullable|integer|between:1,4094',
            'description' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        Ssid::create([
            'ssid_name' => trim($request->ssid_name),
            'vlan_id' => $request->vlan_id,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', "SSID '{$request->ssid_name}' successfully added!");
    }

    /**
     * Update specified SSID definition
     */
    public function update(Request $request, $id)
    {
        $ssid = Ssid::findOrFail($id);

        $request->validate([
            'ssid_name' => "required|string|max:64|unique:ssids,ssid_name,{$id}",
            'vlan_id' => 'nullable|integer|between:1,4094',
            'description' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        $ssid->update([
            'ssid_name' => trim($request->ssid_name),
            'vlan_id' => $request->vlan_id,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', "SSID '{$ssid->ssid_name}' successfully updated!");
    }

    /**
     * Remove specified SSID definition
     */
    public function destroy($id)
    {
        $ssid = Ssid::findOrFail($id);
        $name = $ssid->ssid_name;
        $ssid->delete();

        return redirect()->back()->with('success', "SSID '{$name}' deleted!");
    }
}
