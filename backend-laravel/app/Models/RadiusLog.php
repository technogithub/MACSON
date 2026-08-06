<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadiusLog extends Model
{
    protected $table = 'radius_log';
    public $timestamps = false;

    protected $fillable = [
        'log_date',
        'mac_address',
        'ssid',
        'username',
        'nas_ip',
        'nas_port',
        'auth_result',
        'reason',
    ];

    protected $casts = [
        'log_date' => 'datetime',
    ];
}
