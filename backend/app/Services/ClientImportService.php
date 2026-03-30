<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Partner;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientImportService
{
    /**
     * Importa titolari da array di dati CSV
     * 
     * @param array $rows Array di righe CSV con i dati dei titolari
     * @return array Risultati dell'importazione (created, updated, errors, assignments)
     */
    public function importClientsFromCsv(array $rows): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'assignments' => [
                'successful' => 0,
                'failed' => 0,
                'details' => []
            ]
        ];

        DB::beginTransaction();
        
        try {
            foreach ($rows as $index => $row) {
                try {
                    // Skip se non ha ragione sociale o P.IVA
                    if (empty($row['ragione_sociale']) || empty($row['p_iva'])) {
                        $results['skipped']++;
                        Log::info("Riga saltata " . ($index + 1) . ": Ragione sociale o P.IVA mancante", [
                            'ragione_sociale' => $row['ragione_sociale'] ?? 'N/A',
                            'piva' => $row['p_iva'] ?? 'N/A'
                        ]);
                        continue;
                    }

                    // Normalizza P.IVA (rimuovi spazi e "IT" prefix se presente)
                    $piva = $this->normalizePiva($row['p_iva']);
                    
                    // Cerca se esiste già un titolare con questa P.IVA
                    $client = Client::where('piva', $piva)->first();
                    
                    $clientData = [
                        'ragione_sociale' => trim($row['ragione_sociale']),
                        'piva' => $piva,
                        'codice_fiscale' => !empty($row['codice_fiscale_proprietario']) ? strtoupper(trim($row['codice_fiscale_proprietario'])) : null,
                        'telefono' => $row['telefono_proprietario'] ?? null,
                        'email' => $row['email_proprietario'] ?? null,
                        'indirizzo' => $row['indirizzo_legale'] ?? null,
                        'codice_destinatario' => $row['codice_destinatario'] ?? null,
                        'pec' => $row['pec'] ?? null,
                        'iban' => $row['iban'] ?? null,
                        'tipo_cliente' => $row['tipo_cliente'] ?? 'Partner OPPLA',
                        'source' => 'import_csv'
                    ];

                    // Estrai città, provincia, CAP dall'indirizzo (se presente)
                    if (!empty($row['indirizzo_legale'])) {
                        $addressParts = $this->parseAddress($row['indirizzo_legale']);
                        $clientData = array_merge($clientData, $addressParts);
                    }

                    if ($client) {
                        // Aggiorna solo se i dati sono più completi
                        $client->update($clientData);
                        $results['updated']++;
                        Log::info("Titolare aggiornato: {$client->ragione_sociale} (P.IVA: {$piva})");
                    } else {
                        // Crea nuovo titolare
                        $client = Client::create($clientData);
                        $results['created']++;
                        Log::info("Titolare creato: {$client->ragione_sociale} (P.IVA: {$piva})");
                    }

                    // Cerca partner da assegnare a questo titolare
                    if (!empty($row['nome_proprietario']) || !empty($row['email_proprietario'])) {
                        $assignmentResult = $this->assignPartnersToClient(
                            $client,
                            $row['nome_proprietario'] ?? '',
                            $row['email_proprietario'] ?? '',
                            $row['telefono_proprietario'] ?? ''
                        );
                        
                        $results['assignments']['successful'] += $assignmentResult['successful'];
                        $results['assignments']['failed'] += $assignmentResult['failed'];
                        $results['assignments']['details'] = array_merge(
                            $results['assignments']['details'],
                            $assignmentResult['details']
                        );
                    }

                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'row' => $index + 1,
                        'data' => $row,
                        'error' => $e->getMessage()
                    ];
                    Log::error("Errore importazione riga " . ($index + 1) . ": " . $e->getMessage());
                }
            }

            DB::commit();
            
            Log::info("Importazione completata", $results);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Errore durante importazione titolari: " . $e->getMessage());
            throw $e;
        }

        return $results;
    }

    /**
     * Assegna partner al client basandosi su nome, email, telefono
     */
    private function assignPartnersToClient(Client $client, string $ownerName, string $ownerEmail, string $ownerPhone): array
    {
        $result = [
            'successful' => 0,
            'failed' => 0,
            'details' => []
        ];

        // Normalizza nome (separa nome e cognome)
        $nameParts = $this->parseOwnerName($ownerName);
        
        // Cerca partner che matchano
        $partnersQuery = Partner::query();
        
        // Match per email (più affidabile)
        if (!empty($ownerEmail)) {
            $partnersQuery->orWhere('email', 'like', '%' . $ownerEmail . '%');
        }
        
        // Match per nome e cognome
        if (!empty($nameParts['nome'])) {
            $partnersQuery->orWhere(function($q) use ($nameParts) {
                $q->where('nome', 'like', '%' . $nameParts['nome'] . '%')
                  ->where('cognome', 'like', '%' . $nameParts['cognome'] . '%');
            });
        }
        
        // Match per telefono (normalizzato)
        if (!empty($ownerPhone)) {
            $normalizedPhone = $this->normalizePhone($ownerPhone);
            $partnersQuery->orWhere('telefono', 'like', '%' . $normalizedPhone . '%');
        }

        $partners = $partnersQuery->get();

        foreach ($partners as $partner) {
            try {
                // Se il partner ha già un ristorante, assegna il client al ristorante
                if ($partner->restaurant) {
                    $partner->restaurant->update(['client_id' => $client->id]);
                    $result['successful']++;
                    $result['details'][] = [
                        'partner' => $partner->nome . ' ' . $partner->cognome,
                        'restaurant' => $partner->restaurant->nome,
                        'client' => $client->ragione_sociale,
                        'status' => 'assigned'
                    ];
                    
                    Log::info("Partner {$partner->nome} {$partner->cognome} assegnato a {$client->ragione_sociale}");
                }
            } catch (\Exception $e) {
                $result['failed']++;
                $result['details'][] = [
                    'partner' => $partner->nome . ' ' . $partner->cognome,
                    'client' => $client->ragione_sociale,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                Log::error("Errore assegnazione partner: " . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Normalizza P.IVA (rimuove "IT", spazi, caratteri speciali)
     */
    private function normalizePiva(string $piva): string
    {
        $piva = strtoupper(trim($piva));
        $piva = str_replace(['IT', ' ', '.', '-'], '', $piva);
        return $piva;
    }

    /**
     * Normalizza numero di telefono (rimuove spazi, +39, parentesi)
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        // Rimuovi +39 o 0039 dall'inizio
        $phone = preg_replace('/^(39|0039)/', '', $phone);
        return $phone;
    }

    /**
     * Parse nome proprietario in nome e cognome
     */
    private function parseOwnerName(string $fullName): array
    {
        $fullName = trim($fullName);
        $parts = explode(' ', $fullName, 2);
        
        return [
            'nome' => $parts[0] ?? '',
            'cognome' => $parts[1] ?? ''
        ];
    }

    /**
     * Parse indirizzo legale in componenti
     * Formato: "Via Grande 89, Livorno, 57123"
     */
    private function parseAddress(string $address): array
    {
        $result = [
            'indirizzo' => null,
            'citta' => null,
            'provincia' => null,
            'cap' => null
        ];

        // Split per virgola
        $parts = array_map('trim', explode(',', $address));
        
        if (count($parts) >= 1) {
            // Primo pezzo è sempre l'indirizzo
            $result['indirizzo'] = $parts[0];
        }
        
        if (count($parts) >= 2) {
            // Secondo pezzo è città (e possibile provincia)
            $cityPart = $parts[1];
            
            // Se contiene parentesi o provincia (es. "Pisa, PI")
            if (preg_match('/^(.+?)\s*[\(,]?\s*([A-Z]{2})\s*[\)]?$/i', $cityPart, $matches)) {
                $result['citta'] = trim($matches[1]);
                $result['provincia'] = strtoupper(trim($matches[2]));
            } else {
                $result['citta'] = $cityPart;
            }
        }
        
        if (count($parts) >= 3) {
            // Terzo pezzo potrebbe essere CAP o provincia
            $lastPart = $parts[count($parts) - 1];
            
            // Se è numerico, è CAP
            if (preg_match('/\d{5}/', $lastPart, $matches)) {
                $result['cap'] = $matches[0];
            }
            // Se è 2 lettere, è provincia
            elseif (preg_match('/^[A-Z]{2}$/i', $lastPart)) {
                $result['provincia'] = strtoupper($lastPart);
            }
        }

        return $result;
    }

    /**
     * Importa da file CSV
     */
    public function importFromCsvFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File non trovato: {$filePath}");
        }

        $rows = [];
        $handle = fopen($filePath, 'r');
        
        // Leggi header
        $header = fgetcsv($handle, 0, ',');
        
        // Normalizza header (rimuovi spazi, converti in snake_case)
        $header = array_map(function($col) {
            $col = trim(strtolower($col));
            $col = str_replace([' ', '"', "\n", "\r", '.'], '_', $col);
            $col = preg_replace('/_{2,}/', '_', $col);
            return trim($col, '_');
        }, $header);

        // Leggi righe
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($row) === count($header)) {
                $rows[] = array_combine($header, $row);
            }
        }
        
        fclose($handle);

        return $this->importClientsFromCsv($rows);
    }
}
