<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'lorenzo.moschella@oppla.delivery'],
            [
                'name' => 'Lorenzo Moschella',
                'password' => bcrypt('MoschellaILoveUk3A'),
            ]
        );

        // CRM & Business Data Seeders
        $this->call([
            AccountingCategorySeeder::class,
            ContractTemplateSeeder::class,
            PipelineStageSeeder::class,
        ]);
    }
}
