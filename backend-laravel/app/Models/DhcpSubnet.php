<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DhcpSubnet extends Model
{
    use HasFactory;

    protected $table = 'dhcp_subnets';

    protected $fillable = [
        'name',
        'vlan_id',
        'subnet',
        'pool_start',
        'pool_end',
        'gateway',
        'dns_servers',
        'lease_time',
        'description',
        'is_active',
    ];

    protected $casts = [
        'vlan_id'    => 'integer',
        'lease_time' => 'integer',
        'is_active'  => 'boolean',
    ];

    /**
     * Relasi ke Static Leases yang berada di dalam subnet ini
     */
    public function staticLeases(): HasMany
    {
        return $this->hasMany(DhcpStaticLease::class, 'dhcp_subnet_id');
    }

    /**
     * Hitung total IP yang dialokasikan dalam pool
     */
    public function getTotalPoolIpsAttribute(): int
    {
        $startLong = ip2long($this->pool_start);
        $endLong   = ip2long($this->pool_end);

        if ($startLong === false || $endLong === false || $endLong < $startLong) {
            return 0;
        }

        return ($endLong - $startLong) + 1;
    }
}
