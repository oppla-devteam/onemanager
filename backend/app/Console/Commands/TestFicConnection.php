<?php

namespace App\Console\Commands;

use App\Models\FattureInCloudConnection;
use App\Services\FattureInCloudService;
use Illuminate\Console\Command;

class TestFicConnection extends Command
{
    protected $signature = 'fic:test {user_id? : The user ID to test connection for}';
    protected $description = 'Test Fatture in Cloud OAuth connection and API access';

    public function handle(FattureInCloudService $ficService)
    {
        $this->info('🔍 Testing Fatture in Cloud Connection...');
        $this->newLine();

        // Get user ID
        $userId = $this->argument('user_id');
        if (!$userId) {
            $userId = $this->ask('Enter user ID to test', '1');
        }

        // Find connection
        $connection = FattureInCloudConnection::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$connection) {
            $this->error("❌ No active FIC connection found for user {$userId}");
            $this->newLine();
            
            // Show all connections
            $allConnections = FattureInCloudConnection::where('user_id', $userId)->get();
            if ($allConnections->count() > 0) {
                $this->warn("Found {$allConnections->count()} inactive connection(s):");
                foreach ($allConnections as $conn) {
                    $this->line("  - ID: {$conn->id}, Active: " . ($conn->is_active ? 'Yes' : 'No') . ", Company: {$conn->company_name}");
                }
            }
            
            return 1;
        }

        $this->info("Connection found:");
        $this->line("   ID: {$connection->id}");
        $this->line("   Company: {$connection->company_name}");
        $this->line("   FIC Company ID: {$connection->fic_company_id}");
        $this->line("   Token expires: {$connection->token_expires_at}");
        $this->line("   Refresh expires: {$connection->refresh_token_expires_at}");
        $this->newLine();

        // Check token status
        if ($connection->isTokenExpired()) {
            $this->warn("⚠️  Access token is EXPIRED");
            if ($connection->isRefreshTokenExpired()) {
                $this->error("❌ Refresh token is also EXPIRED - need to re-authenticate");
                return 1;
            } else {
                $this->info("🔄 Refresh token is valid - attempting refresh...");
                try {
                    $newTokenData = $ficService->refreshToken($connection);
                    if ($newTokenData) {
                        $connection->update([
                            'access_token' => $newTokenData['access_token'],
                            'token_expires_at' => $newTokenData['token_expires_at'],
                        ]);
                        $this->info("Token refreshed successfully!");
                    } else {
                        $this->error("❌ Token refresh failed");
                        return 1;
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Token refresh error: " . $e->getMessage());
                    return 1;
                }
            }
        } else {
            $this->info("Access token is valid");
        }

        $this->newLine();

        // Test API call - get companies
        $this->info("🧪 Testing API: Get user companies...");
        try {
            $companies = $ficService->getUserCompanies($connection);
            if ($companies) {
                $this->info("API call successful! Found " . count($companies) . " company(ies):");
                foreach ($companies as $company) {
                    $this->line("   - {$company['name']} (ID: {$company['id']})");
                }
            } else {
                $this->error("❌ API call returned no companies");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ API call failed: " . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info("All tests passed! FIC connection is working correctly.");
        
        return 0;
    }
}
