<?php

namespace App\Console\Commands;

use App\Models\FattureInCloudConnection;
use Illuminate\Console\Command;

class CleanupFicConnections extends Command
{
    protected $signature = 'fic:cleanup {--force : Skip confirmation}';
    protected $description = 'Cleanup failed or incomplete FIC OAuth connections';

    public function handle()
    {
        $this->info('🔍 Searching for incomplete FIC connections...');
        
        // Trova connessioni incomplete (senza company_id o access_token)
        $incompleteConnections = FattureInCloudConnection::where(function ($query) {
            $query->whereNull('fic_company_id')
                  ->orWhereNull('access_token')
                  ->orWhere('access_token', '');
        })->get();
        
        if ($incompleteConnections->isEmpty()) {
            $this->info('No incomplete connections found.');
            return 0;
        }
        
        $this->warn("Found {$incompleteConnections->count()} incomplete connection(s):");
        $this->newLine();
        
        $incompleteConnections->each(function ($conn) {
            $this->line("ID: {$conn->id}");
            $this->line("  User ID: {$conn->user_id}");
            $this->line("  Company: " . ($conn->company_name ?: '(none)'));
            $this->line("  FIC Company ID: " . ($conn->fic_company_id ?: '(none)'));
            $this->line("  Has token: " . (!empty($conn->access_token) ? 'Yes' : 'No'));
            $this->line("  Active: " . ($conn->is_active ? 'Yes' : 'No'));
            $this->line("  Created: {$conn->created_at}");
            $this->newLine();
        });
        
        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to delete these incomplete connections?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }
        
        $deleted = 0;
        foreach ($incompleteConnections as $conn) {
            try {
                $conn->delete();
                $this->info("Deleted connection ID {$conn->id}");
                $deleted++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to delete connection ID {$conn->id}: {$e->getMessage()}");
            }
        }
        
        $this->newLine();
        $this->info("Cleanup completed. Deleted {$deleted} connection(s).");
        $this->warn('Users can now retry the FIC OAuth authorization.');
        
        return 0;
    }
}
