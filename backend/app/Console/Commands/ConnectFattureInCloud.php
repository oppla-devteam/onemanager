<?php

namespace App\Console\Commands;

use App\Models\FattureInCloudConnection;
use App\Services\FattureInCloudService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ConnectFattureInCloud extends Command
{
    protected $signature = 'fic:connect {--user-id=1 : ID utente per la connessione}';
    protected $description = 'Connetti Fatture in Cloud tramite OAuth 2.0';

    public function handle()
    {
        $userId = $this->option('user-id');
        
        $ficService = app(FattureInCloudService::class);
        
        // Generate authorization URL
        $state = Str::random(40);
        $authUrl = $ficService->getAuthorizationUrl($state);
        
        $this->info('=== Connessione Fatture in Cloud ===');
        $this->line('');
        $this->info('1. Apri questo URL nel browser:');
        $this->line('');
        $this->line($authUrl);
        $this->line('');
        $this->info('2. Autorizza l\'applicazione');
        $this->info('3. Dopo l\'autorizzazione, verrai reindirizzato a:');
        $this->line(config('fatture_in_cloud.oauth.redirect_uri') . '?code=XXX&state=YYY');
        $this->line('');
        $this->info('4. Copia il parametro "code" dall\'URL di redirect');
        $this->line('');
        
        $code = $this->ask('Incolla qui il codice di autorizzazione (code)');
        
        if (!$code) {
            $this->error('Codice di autorizzazione mancante');
            return 1;
        }
        
        $inputState = $this->ask('Incolla qui lo state dall\'URL (per sicurezza)');
        
        if ($inputState !== $state) {
            $this->error('State non valido! Possibile attacco CSRF.');
            return 1;
        }
        
        $this->info('Scambio codice per token...');
        
        // Exchange code for token
        $tokenData = $ficService->exchangeCodeForToken($code);
        
        if (!$tokenData) {
            $this->error('Impossibile ottenere il token di accesso');
            return 1;
        }
        
        $this->info('Token ottenuto con successo!');
        
        // Get user companies
        $tempConnection = new FattureInCloudConnection([
            'access_token' => $tokenData['access_token'],
            'token_expires_at' => $tokenData['token_expires_at'],
        ]);
        
        $this->info('Recupero aziende associate...');
        $companies = $ficService->getUserCompanies($tempConnection);
        
        if (empty($companies)) {
            $this->error('Nessuna azienda trovata per questo account');
            return 1;
        }
        
        $this->info('Aziende trovate:');
        foreach ($companies as $index => $company) {
            $this->line("  {$index}. {$company['name']} (ID: {$company['id']})");
        }
        
        $companyIndex = 0;
        if (count($companies) > 1) {
            $companyIndex = (int) $this->ask('Seleziona azienda (numero)', '0');
        }
        
        $company = $companies[$companyIndex];
        
        // Store connection
        $this->info('Salvataggio connessione...');
        
        $connection = FattureInCloudConnection::updateOrCreate(
            [
                'user_id' => $userId,
                'fic_company_id' => $company['id'],
            ],
            [
                'company_name' => $company['name'],
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'token_expires_at' => $tokenData['token_expires_at'],
                'refresh_token_expires_at' => $tokenData['refresh_token_expires_at'],
                'scopes' => config('fatture_in_cloud.default_scopes'),
                'is_active' => true,
            ]
        );
        
        $this->info('');
        $this->info('✓ Connessione salvata con successo!');
        $this->line('');
        $this->table(
            ['Campo', 'Valore'],
            [
                ['ID Connessione', $connection->id],
                ['Azienda', $connection->company_name],
                ['ID Azienda FIC', $connection->fic_company_id],
                ['Token scade il', $connection->token_expires_at->format('Y-m-d H:i:s')],
                ['Attiva', $connection->is_active ? 'Sì' : 'No'],
            ]
        );
        
        $this->info('');
        $this->info('Ora puoi inviare fatture a Fatture in Cloud!');
        
        return 0;
    }
}
