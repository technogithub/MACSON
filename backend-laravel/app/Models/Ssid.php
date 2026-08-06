<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ssid extends Model
{
    use HasFactory;

    protected $table = 'ssids';

    protected $fillable = [
        'ssid_name',
        'vlan_id',
        'description',
        'status',
    ];

    /**
     * Relationship: Devices authorized for this SSID
     */
    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'device_ssids', 'ssid_id', 'device_id')
                    ->withTimestamps();
    }
}
