<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckUsers extends Command
{
    protected $signature = 'users:check';
    protected $description = 'Check users in database';

    public function handle()
    {
        $users = User::all();
        $this->info('Total users: ' . $users->count());
        
        if ($users->count() > 0) {
            foreach ($users as $user) {
                $this->info('- ' . $user->email . ' (' . $user->name . ')');
            }
        } else {
            $this->warn('No users found!');
            $this->info('Creating default user...');
            
            User::create([
                'name' => 'Lorenzo Moschella',
                'email' => 'lorenzo.moschella@oppla.delivery',
                'password' => bcrypt('MoschellaILoveUk3A'),
            ]);
            
            $this->info('User created successfully!');
        }
    }
}
