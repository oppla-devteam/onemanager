<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixClientAddressData extends Command
{
    protected $signature = 'clients:fix-addresses {--dry-run : Mostra cosa verrebbe corretto senza salvare}';
    protected $description = 'Corregge dati indirizzo malformati nei clienti (CAP in città, province errate)';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 Modalità DRY-RUN - Nessuna modifica verrà salvata');
        }

        $fixed = 0;
        $clients = Client::whereNotNull('citta')->get();

        foreach ($clients as $client) {
            $changes = [];
            $originalCity = $client->citta;
            $originalCap = $client->cap;
            $originalProvincia = $client->provincia;

            // 1. Correggi città con CAP incorporato (es. "57125 Livorno" o "57125 Livor")
            if (preg_match('/^(\d{5})\s*(.+)$/', $client->citta, $matches)) {
                $extractedCap = $matches[1];
                $extractedCity = trim($matches[2]);
                
                // Se CAP è null, usa quello estratto
                if (empty($client->cap)) {
                    $client->cap = $extractedCap;
                    $changes[] = "CAP: null -> {$extractedCap}";
                }
                
                // Correggi nome città
                $client->citta = $this->fixCityName($extractedCity, $extractedCap);
                $changes[] = "Città: '{$originalCity}' -> '{$client->citta}'";
            }

            // 2. Correggi città troncate basandosi sul CAP
            if (!empty($client->cap)) {
                $client->citta = $this->fixCityName($client->citta, $client->cap);
                if ($client->citta !== $originalCity) {
                    $changes[] = "Città: '{$originalCity}' -> '{$client->citta}'";
                }
            }

            // 3. Correggi provincia basandosi sulla città/CAP
            if (!empty($client->cap) || !empty($client->citta)) {
                $correctProvincia = $this->getCorrectProvincia($client->citta, $client->cap);
                if ($correctProvincia && $correctProvincia !== $client->provincia) {
                    $client->provincia = $correctProvincia;
                    $changes[] = "Provincia: '{$originalProvincia}' -> '{$correctProvincia}'";
                }
            }

            // Salva se ci sono modifiche
            if (!empty($changes) && !$isDryRun) {
                $client->save();
                $fixed++;
                $this->info("Cliente #{$client->id} - {$client->ragione_sociale}:");
                foreach ($changes as $change) {
                    $this->line("   {$change}");
                }
            } elseif (!empty($changes)) {
                $this->warn("🔸 Cliente #{$client->id} - {$client->ragione_sociale} (SAREBBE corretto):");
                foreach ($changes as $change) {
                    $this->line("   {$change}");
                }
                $fixed++;
            }
        }

        if ($isDryRun) {
            $this->info("\n📊 {$fixed} clienti necessitano correzioni (DRY-RUN - nessuna modifica salvata)");
        } else {
            $this->info("\n✨ {$fixed} clienti corretti con successo!");
        }

        return 0;
    }

    /**
     * Corregge nomi città troncati o malformati
     */
    private function fixCityName(string $city, ?string $cap = null): string
    {
        // Mappa città comuni troncate
        $cityMap = [
            'Livor' => 'Livorno',
            'Livorn' => 'Livorno',
            'Pis' => 'Pisa',
            'Firenz' => 'Firenze',
            'Rom' => 'Roma',
            'Milan' => 'Milano',
            'Torin' => 'Torino',
            'Napol' => 'Napoli',
            'Bologn' => 'Bologna',
            'Genov' => 'Genova',
        ];

        $city = trim($city);

        // Cerca corrispondenza parziale
        foreach ($cityMap as $truncated => $full) {
            if (stripos($city, $truncated) === 0) {
                return $full;
            }
        }

        // Basandosi sul CAP (prefissi città toscane comuni)
        if ($cap) {
            $capPrefixes = [
                '571' => 'Livorno',  // 57100-57128
                '561' => 'Pisa',      // 56100-56128
                '501' => 'Firenze',   // 50100-50145
            ];

            $prefix = substr($cap, 0, 3);
            if (isset($capPrefixes[$prefix])) {
                // Se la città è completamente sconosciuta o molto corta, usa il CAP
                if (strlen($city) < 4 || in_array(strtolower($city), ['n/a', 'nd', '-'])) {
                    return $capPrefixes[$prefix];
                }
            }
        }

        return $city;
    }

    /**
     * Determina la provincia corretta basandosi su città o CAP
     */
    private function getCorrectProvincia(?string $city, ?string $cap): ?string
    {
        if (empty($city) && empty($cap)) {
            return null;
        }

        // Mappa città -> provincia
        $provinciaMap = [
            'Livorno' => 'LI',
            'Pisa' => 'PI',
            'Firenze' => 'FI',
            'Roma' => 'RM',
            'Milano' => 'MI',
            'Torino' => 'TO',
            'Napoli' => 'NA',
            'Bologna' => 'BO',
            'Genova' => 'GE',
        ];

        // Cerca per nome città
        if ($city) {
            foreach ($provinciaMap as $cityName => $prov) {
                if (stripos($city, $cityName) !== false) {
                    return $prov;
                }
            }
        }

        // Basandosi sul CAP
        if ($cap) {
            $capProvinciaMap = [
                '571' => 'LI', // Livorno
                '561' => 'PI', // Pisa
                '501' => 'FI', // Firenze
                '00' => 'RM',  // Roma
                '20' => 'MI',  // Milano
                '10' => 'TO',  // Torino
                '80' => 'NA',  // Napoli
                '40' => 'BO',  // Bologna
                '16' => 'GE',  // Genova
            ];

            // Prova con 3 cifre
            $prefix3 = substr($cap, 0, 3);
            if (isset($capProvinciaMap[$prefix3])) {
                return $capProvinciaMap[$prefix3];
            }

            // Prova con 2 cifre
            $prefix2 = substr($cap, 0, 2);
            if (isset($capProvinciaMap[$prefix2])) {
                return $capProvinciaMap[$prefix2];
            }
        }

        return null;
    }
}
