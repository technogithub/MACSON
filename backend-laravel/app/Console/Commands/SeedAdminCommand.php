<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SeedAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-admin 
                            {--email=admin@macson.local : User login email}
                            {--password=Admin@2026! : User login password}
                            {--name=Super Administrator : User full name}
                            {--role=Super Admin : User role (Super Admin or Operator)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed or update an administrator account safely in the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email    = trim($this->option('email'));
        $password = $this->option('password');
        $name     = trim($this->option('name'));
        $role     = trim($this->option('role'));

        if (empty($email) || empty($password)) {
            $this->error('Email and password cannot be empty!');
            return Command::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long!');
            return Command::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->name     = $name;
            $user->password = Hash::make($password);
            $user->role     = $role;
            $user->save();
            $this->info("Successfully updated existing user: {$email} (Role: {$role})");
        } else {
            User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => Hash::make($password),
                'role'     => $role,
            ]);
            $this->info("Successfully created new user: {$email} (Role: {$role})");
        }

        return Command::SUCCESS;
    }
}
