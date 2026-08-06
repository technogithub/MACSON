<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * Check if user is Super Admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'Super Admin';
    }

    /**
     * Check if user is Operator
     */
    public function isOperator(): bool
    {
        return $this->role === 'Operator';
    }
}
