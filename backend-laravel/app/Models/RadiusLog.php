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
        'username',
        'nas_ip',
        'nas_port',
        'auth_result',
        'reason',
        'auth_date'
    ];

    protected $casts = [
        'log_date' => 'datetime',
        'auth_date' => 'datetime',
    ];
}
