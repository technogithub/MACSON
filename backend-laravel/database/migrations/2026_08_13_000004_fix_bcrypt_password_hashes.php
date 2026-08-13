<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // Re-hash default admin and operator passwords using standard Bcrypt
        $passwordHash = Hash::make('password');

        DB::table('users')->where('email', 'admin@radius.local')->update([
            'password' => $passwordHash,
        ]);

        DB::table('users')->where('email', 'operator@radius.local')->update([
            'password' => $passwordHash,
        ]);
    }

    public function down(): void
    {
        //
    }
};
