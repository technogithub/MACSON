<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnifiConfig extends Model
{
    use HasFactory;

    protected $table = 'unifi_configs';

    protected $fillable = [
        'controller_url',
        'site_id',
        'username',
        'password',
        'verify_ssl',
    ];

    protected $casts = [
        'verify_ssl' => 'boolean',
    ];
}
