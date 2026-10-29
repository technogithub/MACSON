<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class GuestBlacklist extends Model
{
    use HasFactory;

    protected $table = 'guest_blacklists';

    protected $fillable = [
        'mac_address',
        'device_name',
        'reason',
        'blocked_by',
        'blocked_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Helper: Sanitize any raw MAC input to AA:BB:CC:DD:EE:FF format
     */
    public static function sanitizeMac(string $mac): string
    {
        $clean = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac));
        if (strlen($clean) === 12) {
            return implode(':', str_split($clean, 2));
        }
        return strtoupper(trim($mac));
    }

    /**
     * Check if this ban has expired
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false; // Permanent
        }
        return Carbon::now()->greaterThan($this->expires_at);
    }

    /**
     * Check if currently actively blocking
     */
    public function isCurrentlyBlocking(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Relationship: Admin user who created the block
     */
    public function blockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }
}
