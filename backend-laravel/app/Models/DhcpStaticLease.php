<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DhcpStaticLease extends Model
{
    use HasFactory;

    protected $table = 'dhcp_static_leases';

    protected $fillable = [
        'dhcp_subnet_id',
        'device_id',
        'mac_address',
        'ip_address',
        'hostname',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Mutator & Accessor: Pastikan format MAC Address selalu uppercase dan pemisah titik dua (AA:BB:CC:DD:EE:FF)
     */
    protected function macAddress(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtoupper(str_replace(['-', '.'], ':', trim($value))),
        );
    }

    /**
     * Relasi ke Subnet
     */
    public function subnet(): BelongsTo
    {
        return $this->belongsTo(DhcpSubnet::class, 'dhcp_subnet_id');
    }

    /**
     * Relasi opsional ke Device (tabel devices eksisting)
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }
}
