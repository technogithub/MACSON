<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Device extends Model
{
    use HasFactory;

    protected $table = 'devices';

    protected $fillable = [
        'mac_address',
        'raw_mac',
        'ssid',
        'device_name',
        'location',
        'description',
        'status',
        'created_by'
    ];

    /**
     * Relationship: SSIDs authorized for this device
     */
    public function ssids(): BelongsToMany
    {
        return $this->belongsToMany(Ssid::class, 'device_ssids', 'device_id', 'ssid_id')
                    ->withTimestamps();
    }

    /**
     * Standardize MAC Address format to AA:BB:CC:DD:EE:FF
     * Accepts: AA-BB-CC-DD-EE-FF, aabbccddeeff, AA:BB:CC:DD:EE:FF, etc.
     */
    public static function formatMacAddress(string $mac): ?string
    {
        // Strip everything except hex characters
        $hexOnly = strtoupper(preg_replace('/[^a-fA-F0-9]/', '', $mac));

        if (strlen($hexOnly) !== 12) {
            return null; // Invalid length
        }

        // Format into AA:BB:CC:DD:EE:FF
        return implode(':', str_split($hexOnly, 2));
    }

    /**
     * Check if a MAC address + SSID combination is a duplicate in DB
     */
    public static function isDuplicate(string $formattedMac, string $ssid = 'ALL', ?int $excludeId = null): bool
    {
        $query = static::where('mac_address', $formattedMac)->where('ssid', $ssid);
        if ($excludeId) {
            $query->where('where', '!=', $excludeId);
        }
        return $query->exists();
    }
}
