<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@radius.local',
            'password' => Hash::make('AdminRadius123!'),
            'role' => 'Super Admin',
        ]);

        User::create([
            'name' => 'NOC Operator',
            'email' => 'operator@radius.local',
            'password' => Hash::make('Operator123!'),
            'role' => 'Operator',
        ]);
    }
}