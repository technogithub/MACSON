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

    /**
     * Periksa apakah IP berada di dalam pool range saja (pool_start – pool_end).
     * TIDAK cek CIDR, sehingga aman dipakai saat subnet memiliki CIDR yang overlap dengan subnet lain.
     */
    public function isInPool(string $ip): bool
    {
        $ipLong    = ip2long(trim($ip));
        $startLong = ip2long((string)$this->pool_start);
        $endLong   = ip2long((string)$this->pool_end);

        if ($ipLong === false || $startLong === false || $endLong === false) {
            return false;
        }

        return $ipLong >= $startLong && $ipLong <= $endLong;
    }

    /**
     * Periksa apakah sebuah IP berada di dalam pool range atau CIDR subnet ini
     */
    public function containsIp(string $ip): bool
    {
        $ipLong = ip2long(trim($ip));
        if ($ipLong === false) {
            return false;
        }

        // Cek range pool
        $startLong = ip2long($this->pool_start);
        $endLong   = ip2long($this->pool_end);
        if ($startLong !== false && $endLong !== false && $ipLong >= $startLong && $ipLong <= $endLong) {
            return true;
        }

        // Cek network CIDR
        if (!empty($this->subnet) && str_contains($this->subnet, '/')) {
            [$net, $mask] = explode('/', $this->subnet);
            $netLong = ip2long(trim($net));
            $mask = (int)$mask;
            if ($netLong !== false && $mask >= 0 && $mask <= 32) {
                $maskLong = -1 << (32 - $mask);
                if (($ipLong & $maskLong) === ($netLong & $maskLong)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Cari subnet berdasarkan hint (nama, vlan, cidr, id) atau otomatis berdasarkan IP address
     *
     * @param string|null $hint
     * @param string $ip
     * @param iterable|null $subnets
     * @return self|null
     */
    public static function findByHintOrIp(?string $hint, string $ip, $subnets = null): ?self
    {
        $subnetList = $subnets ?? self::all();
        $cleanHint  = trim((string)$hint);
        $cleanIp    = trim($ip);

        // 1. Coba cocokkan dengan hint jika tersedia
        if (!empty($cleanHint)) {
            $lowerHint = strtolower($cleanHint);
            foreach ($subnetList as $s) {
                if (strtolower($s->name) === $lowerHint) {
                    return $s;
                }
                if ((string)$s->vlan_id === $cleanHint) {
                    return $s;
                }
                if ($s->subnet === $cleanHint) {
                    return $s;
                }
                if ((string)$s->id === $cleanHint) {
                    return $s;
                }
            }
        }

        // 2. Auto-detect berdasarkan IP address
        if (!empty($cleanIp) && filter_var($cleanIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            foreach ($subnetList as $s) {
                if ($s->containsIp($cleanIp)) {
                    return $s;
                }
            }
        }

        return null;
    }
}
