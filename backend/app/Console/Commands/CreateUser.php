<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create 
                            {name : The name of the user}
                            {email : The email of the user}
                            {password : The password for the user}
                            {--role=viewer : The role of the user (admin, manager, viewer)}
                            {--permissions=* : The permissions for the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');
        $role = $this->option('role');
        $permissions = $this->option('permissions');

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists!");
            return 1;
        }

        // Default permissions if none provided
        if (empty($permissions)) {
            $permissions = ['clients', 'contracts', 'tasks'];
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'permissions' => $permissions,
            'email_verified_at' => now(),
        ]);

        $this->info("User created successfully!");
        $this->table(
            ['ID', 'Name', 'Email', 'Role', 'Permissions'],
            [[$user->id, $user->name, $user->email, $user->role, implode(', ', $user->permissions)]]
        );

        return 0;
    }
}
