<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if template already exists
        $exists = DB::table('contract_templates')
            ->where('code', 'oppla-subscription-cover')
            ->exists();

        if (!$exists) {
            // Run seeder to create template
            Artisan::call('db:seed', [
                '--class' => 'OpplaContractTemplateSeeder',
                '--force' => true,
            ]);

            \Log::info('[Migration] Oppla contract template seeded successfully');
        } else {
            \Log::info('[Migration] Oppla contract template already exists, skipping seed');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't delete template on rollback - it might be in use
        \Log::info('[Migration] Rollback: Oppla contract template not deleted (safety)');
    }
};
