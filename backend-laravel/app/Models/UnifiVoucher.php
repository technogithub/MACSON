<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnifiVoucher extends Model
{
    use HasFactory;

    protected $table = 'unifi_vouchers';

    protected $fillable = [
        'unifi_id',
        'code',
        'duration_minutes',
        'quota_mb',
        'down_kbps',
        'up_kbps',
        'use_limit',
        'used_count',
        'note',
        'batch_id',
        'status',
        'used_at',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'quota_mb'         => 'integer',
        'down_kbps'        => 'integer',
        'up_kbps'          => 'integer',
        'use_limit'        => 'integer',
        'used_count'       => 'integer',
        'used_at'          => 'datetime',
    ];

    /**
     * Helper to format code nicely e.g. 12345-67890
     */
    public function getFormattedCodeAttribute(): string
    {
        if (strlen($this->code) === 10) {
            return substr($this->code, 0, 5) . '-' . substr($this->code, 5);
        }
        return $this->code;
    }
}
