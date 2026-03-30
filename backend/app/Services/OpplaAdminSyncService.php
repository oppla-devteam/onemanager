<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OpplaAdminSyncService
{
    private OpplaAdminScraperService $scraper;

    public function __construct(OpplaAdminScraperService $scraper)
    {
        $this->scraper = $scraper;
    }

    /**
     * Sincronizza tutti i dati da Opplà Admin
     */
    public function syncAll(): array
    {
        $results = [
            'partners_synced' => 0,
            'partners_created' => 0,
            'partners_updated' => 0,
            'restaurants_synced' => 0,
            'errors' => [],
            'started_at' => now(),
            'completed_at' => null,
        ];

        try {
            Log::info('[OpplaSync] ===== Inizio sincronizzazione completa =====');

            // Step 1: Sincronizza partners
            $partnersResult = $this->syncPartners();
            $results['partners_synced'] = $partnersResult['synced'];
            $results['partners_created'] = $partnersResult['created'];
            $results['partners_updated'] = $partnersResult['updated'];

            // Step 2: Sincronizza ristoranti per ogni partner
            $restaurantsResult = $this->syncAllRestaurants();
            $results['restaurants_synced'] = $restaurantsResult['synced'];

            $results['completed_at'] = now();
            $duration = $results['started_at']->diffInSeconds($results['completed_at']);

            Log::info('[OpplaSync] ===== Sincronizzazione completata =====', [
                'duration_seconds' => $duration,
                'partners_synced' => $results['partners_synced'],
                'restaurants_synced' => $results['restaurants_synced'],
            ]);

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('[OpplaSync] Errore sincronizzazione', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            $this->scraper->logout();
        }

        return $results;
    }

    /**
     * Sincronizza solo partners
     */
    public function syncPartners(): array
    {
        $results = [
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        try {
            $partnersData = $this->scraper->fetchPartners();

            Log::info('[OpplaSync] Partners da sincronizzare', ['count' => count($partnersData)]);

            DB::beginTransaction();

            foreach ($partnersData as $partnerData) {
                try {
                    $result = $this->syncSinglePartner($partnerData);
                    
                    if ($result['created']) {
                        $results['created']++;
                    } elseif ($result['updated']) {
                        $results['updated']++;
                    }
                    
                    $results['synced']++;

                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'partner' => $partnerData['ragione_sociale'] ?? 'Unknown',
                        'error' => $e->getMessage(),
                    ];
                    Log::warning('[OpplaSync] Errore sync singolo partner', [
                        'partner' => $partnerData['ragione_sociale'] ?? 'Unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            Log::info('[OpplaSync] Partners sincronizzati', [
                'total' => $results['synced'],
                'created' => $results['created'],
                'updated' => $results['updated'],
                'errors' => count($results['errors']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();
            Log::error('[OpplaSync] Errore sync partners', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Sincronizza un singolo partner
     */
    private function syncSinglePartner(array $data): array
    {
        $result = ['created' => false, 'updated' => false];

        // Cerca client esistente per external_id o email
        $client = Client::where('oppla_external_id', $data['external_id'])
            ->orWhere('email', $data['email'])
            ->first();

        $clientData = [
            'oppla_external_id' => $data['external_id'],
            'ragione_sociale' => $data['ragione_sociale'],
            'email' => $data['email'],
            'piva' => $data['piva'],
            'telefono' => $data['telefono'],
            'indirizzo' => $data['indirizzo'],
            'citta' => $data['citta'],
            'client_type' => 'partner_oppla',
            'status' => 'active',
            'oppla_sync_at' => now(),
        ];

        if ($client) {
            // Update esistente
            $client->update($clientData);
            $result['updated'] = true;

            Log::debug('[OpplaSync] Partner aggiornato', [
                'id' => $client->id,
                'ragione_sociale' => $client->ragione_sociale,
            ]);
        } else {
            // Crea nuovo
            $client = Client::create(array_merge($clientData, [
                'guid' => \Illuminate\Support\Str::uuid(),
                'type' => 'partner_oppla',
            ]));
            $result['created'] = true;

            Log::debug('[OpplaSync] Partner creato', [
                'id' => $client->id,
                'ragione_sociale' => $client->ragione_sociale,
            ]);

            // Crea automaticamente un Lead se è nuovo
            $this->createLeadFromPartner($client, $data);
        }

        return $result;
    }

    /**
     * Crea Lead automatico da nuovo partner
     */
    private function createLeadFromPartner(Client $client, array $data): void
    {
        try {
            Lead::create([
                'company_name' => $data['ragione_sociale'],
                'contact_name' => $data['ragione_sociale'],
                'email' => $data['email'],
                'phone' => $data['telefono'],
                'source' => 'partner',
                'status' => 'converted',
                'converted_to_client_at' => now(),
                'client_id' => $client->id,
                'city' => $data['citta'],
                'address' => $data['indirizzo'],
            ]);

            Log::debug('[OpplaSync] Lead automatico creato per partner', [
                'client_id' => $client->id,
            ]);

        } catch (\Exception $e) {
            Log::warning('[OpplaSync] Errore creazione lead automatico', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sincronizza ristoranti per tutti i partner
     */
    public function syncAllRestaurants(): array
    {
        $results = [
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        try {
            // Prendi tutti i partner con external_id
            $partners = Client::whereNotNull('oppla_external_id')
                ->where('client_type', 'partner_oppla')
                ->get();

            Log::info('[OpplaSync] Partners per sync ristoranti', ['count' => $partners->count()]);

            foreach ($partners as $partner) {
                try {
                    $restaurantsData = $this->scraper->fetchRestaurantsForPartner($partner->oppla_external_id);
                    
                    Log::info('[OpplaSync] Ristoranti trovati per partner', [
                        'partner' => $partner->ragione_sociale,
                        'count' => count($restaurantsData),
                    ]);

                    foreach ($restaurantsData as $restaurantData) {
                        // Salva in JSON o tabella dedicata ristoranti
                        // Per ora salviamo in campo JSON sul client
                        $this->syncRestaurantToPartner($partner, $restaurantData);
                        $results['synced']++;
                    }

                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'partner' => $partner->ragione_sociale,
                        'error' => $e->getMessage(),
                    ];
                    Log::warning('[OpplaSync] Errore sync ristoranti per partner', [
                        'partner' => $partner->ragione_sociale,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('[OpplaSync] Errore sync ristoranti', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Sincronizza singolo ristorante su partner
     */
    private function syncRestaurantToPartner(Client $partner, array $restaurantData): void
    {
        // Aggiungi ristorante alla lista JSON del partner
        $restaurants = $partner->oppla_restaurants ?? [];
        
        // Cerca se esiste già per external_id
        $existingIndex = null;
        foreach ($restaurants as $index => $restaurant) {
            if (isset($restaurant['external_id']) && $restaurant['external_id'] === $restaurantData['external_id']) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            // Update esistente
            $restaurants[$existingIndex] = array_merge($restaurants[$existingIndex], $restaurantData);
        } else {
            // Aggiungi nuovo
            $restaurants[] = $restaurantData;
        }

        $partner->update([
            'oppla_restaurants' => $restaurants,
            'oppla_restaurants_count' => count($restaurants),
        ]);

        Log::debug('[OpplaSync] Ristorante sincronizzato', [
            'partner' => $partner->ragione_sociale,
            'restaurant' => $restaurantData['nome'],
        ]);
    }

    /**
     * Get sync statistics
     */
    public function getSyncStats(): array
    {
        return [
            'total_partners' => Client::where('client_type', 'partner_oppla')->count(),
            'synced_partners' => Client::whereNotNull('oppla_external_id')->count(),
            'total_restaurants' => Client::where('client_type', 'partner_oppla')
                ->sum('oppla_restaurants_count'),
            'last_sync' => Client::where('client_type', 'partner_oppla')
                ->max('oppla_sync_at'),
        ];
    }
}
