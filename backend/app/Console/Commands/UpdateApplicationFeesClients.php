<?php

namespace App\Console\Commands;

use App\Models\ApplicationFee;
use App\Models\Client;
use Illuminate\Console\Command;

class UpdateApplicationFeesClients extends Command
{
    protected $signature = 'stripe:update-clients';
    protected $description = 'Associa i client_id alle ApplicationFee esistenti in base all\'email';

    public function handle()
    {
        $this->info('Aggiornamento ApplicationFee con client_id...');

        // Trova tutte le ApplicationFee senza client_id ma con partner_email
        $fees = ApplicationFee::whereNull('client_id')
            ->whereNotNull('partner_email')
            ->get();

        $this->info("Trovate {$fees->count()} commissioni senza client_id");

        $updated = 0;
        $notFound = 0;

        $progressBar = $this->output->createProgressBar($fees->count());
        $progressBar->start();

        foreach ($fees as $fee) {
            $client = Client::where('email', $fee->partner_email)->first();
            
            if ($client) {
                $fee->client_id = $client->id;
                $fee->partner_name = $client->ragione_sociale;
                $fee->save();
                $updated++;
            } else {
                $notFound++;
                $this->newLine();
                $this->warn("Cliente non trovato per email: {$fee->partner_email}");
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✓ Aggiornate {$updated} commissioni");
        if ($notFound > 0) {
            $this->warn("⚠ {$notFound} commissioni senza cliente corrispondente");
        }

        return 0;
    }
}
