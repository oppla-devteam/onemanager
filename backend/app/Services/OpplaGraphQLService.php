<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for interacting with OPPLA Delivery Platform GraphQL API
 * Documentation: https://docs.api.oppla.delivery/
 */
class OpplaGraphQLService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('oppla_admin.graphql_url', 'https://api.oppla.delivery/graphql');
        $this->apiKey = config('oppla_admin.api_key');
    }

    /**
     * Execute a GraphQL query
     */
    private function query(string $query, array $variables = []): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->apiUrl, [
                'query' => $query,
                'variables' => $variables,
            ]);

            if (!$response->successful()) {
                Log::error('OPPLA GraphQL API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('API request failed: ' . $response->body());
            }

            $data = $response->json();

            if (isset($data['errors'])) {
                Log::error('OPPLA GraphQL Errors', ['errors' => $data['errors']]);
                throw new \Exception('GraphQL errors: ' . json_encode($data['errors']));
            }

            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('OPPLA GraphQL Exception', [
                'message' => $e->getMessage(),
                'query' => $query,
                'variables' => $variables,
            ]);
            throw $e;
        }
    }

    /**
     * Create a new restaurant in OPPLA platform
     */
    public function createRestaurant(array $data): array
    {
        $mutation = <<<'GRAPHQL'
        mutation CreateRestaurant($input: CreateRestaurantInput!) {
            createRestaurant(input: $input) {
                id
                nome
                indirizzo
                citta
                provincia
                cap
                telefono
                email
                piva
                codice_fiscale
                is_active
            }
        }
        GRAPHQL;

        $variables = [
            'input' => [
                'nome' => $data['nome'],
                'indirizzo' => $data['indirizzo'] ?? null,
                'citta' => $data['citta'] ?? null,
                'provincia' => $data['provincia'] ?? null,
                'cap' => $data['cap'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'email' => $data['email'] ?? null,
                'piva' => $data['piva'] ?? null,
                'codice_fiscale' => $data['codice_fiscale'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'delivery_management' => $data['delivery_management'] ?? 'oppla',
                'delivery_zones' => $data['delivery_zones'] ?? [],
            ]
        ];

        $result = $this->query($mutation, $variables);
        return $result['createRestaurant'] ?? [];
    }

    /**
     * Update an existing restaurant
     */
    public function updateRestaurant(int $restaurantId, array $data): array
    {
        $mutation = <<<'GRAPHQL'
        mutation UpdateRestaurant($id: ID!, $input: UpdateRestaurantInput!) {
            updateRestaurant(id: $id, input: $input) {
                id
                nome
                indirizzo
                citta
                provincia
                cap
                telefono
                email
                piva
                codice_fiscale
                is_active
            }
        }
        GRAPHQL;

        $variables = [
            'id' => $restaurantId,
            'input' => $data
        ];

        $result = $this->query($mutation, $variables);
        return $result['updateRestaurant'] ?? [];
    }

    /**
     * Get restaurant by ID
     */
    public function getRestaurant(int $restaurantId): ?array
    {
        $query = <<<'GRAPHQL'
        query GetRestaurant($id: ID!) {
            restaurant(id: $id) {
                id
                nome
                indirizzo
                citta
                provincia
                cap
                telefono
                email
                piva
                codice_fiscale
                logo_path
                foto_path
                cover_path
                delivery_management
                delivery_zones
                is_active
                client_id
                created_at
                updated_at
            }
        }
        GRAPHQL;

        $result = $this->query($query, ['id' => $restaurantId]);
        return $result['restaurant'] ?? null;
    }

    /**
     * Get all restaurants for a client
     */
    public function getRestaurantsByClient(int $clientId): array
    {
        $query = <<<'GRAPHQL'
        query GetRestaurants($clientId: ID!) {
            restaurants(clientId: $clientId) {
                id
                nome
                indirizzo
                citta
                provincia
                cap
                telefono
                email
                is_active
            }
        }
        GRAPHQL;

        $result = $this->query($query, ['clientId' => $clientId]);
        return $result['restaurants'] ?? [];
    }

    /**
     * Create a new partner in OPPLA platform
     */
    public function createPartner(array $data): array
    {
        $mutation = <<<'GRAPHQL'
        mutation CreatePartner($input: CreatePartnerInput!) {
            createPartner(input: $input) {
                id
                nome
                cognome
                email
                telefono
                restaurant_id
                is_active
            }
        }
        GRAPHQL;

        $variables = [
            'input' => [
                'nome' => $data['nome'],
                'cognome' => $data['cognome'],
                'email' => $data['email'],
                'telefono' => $data['telefono'],
                'restaurant_id' => $data['restaurant_id'],
                'password' => $data['password'] ?? null,
            ]
        ];

        $result = $this->query($mutation, $variables);
        return $result['createPartner'] ?? [];
    }

    /**
     * Update an existing partner
     */
    public function updatePartner(int $partnerId, array $data): array
    {
        $mutation = <<<'GRAPHQL'
        mutation UpdatePartner($id: ID!, $input: UpdatePartnerInput!) {
            updatePartner(id: $id, input: $input) {
                id
                nome
                cognome
                email
                telefono
                restaurant_id
                is_active
            }
        }
        GRAPHQL;

        $variables = [
            'id' => $partnerId,
            'input' => $data
        ];

        $result = $this->query($mutation, $variables);
        return $result['updatePartner'] ?? [];
    }

    /**
     * Get partner by ID
     */
    public function getPartner(int $partnerId): ?array
    {
        $query = <<<'GRAPHQL'
        query GetPartner($id: ID!) {
            partner(id: $id) {
                id
                nome
                cognome
                email
                telefono
                restaurant_id
                restaurant {
                    id
                    nome
                }
                is_active
                created_at
                updated_at
            }
        }
        GRAPHQL;

        $result = $this->query($query, ['id' => $partnerId]);
        return $result['partner'] ?? null;
    }

    /**
     * Get all partners for a restaurant
     */
    public function getPartnersByRestaurant(int $restaurantId): array
    {
        $query = <<<'GRAPHQL'
        query GetPartners($restaurantId: ID!) {
            partners(restaurantId: $restaurantId) {
                id
                nome
                cognome
                email
                telefono
                is_active
            }
        }
        GRAPHQL;

        $result = $this->query($query, ['restaurantId' => $restaurantId]);
        return $result['partners'] ?? [];
    }

    /**
     * Get delivery zones
     */
    public function getDeliveryZones(): array
    {
        $query = <<<'GRAPHQL'
        query GetDeliveryZones {
            deliveryZones {
                id
                name
                label
                cities
                is_active
            }
        }
        GRAPHQL;

        $result = $this->query($query);
        return $result['deliveryZones'] ?? [];
    }

    /**
     * Get active orders for a restaurant
     */
    public function getActiveOrders(int $restaurantId): array
    {
        $query = <<<'GRAPHQL'
        query GetActiveOrders($restaurantId: ID!) {
            activeOrders(restaurantId: $restaurantId) {
                id
                order_number
                status
                total
                customer_name
                customer_phone
                delivery_address
                created_at
            }
        }
        GRAPHQL;

        $result = $this->query($query, ['restaurantId' => $restaurantId]);
        return $result['activeOrders'] ?? [];
    }

    /**
     * Upload restaurant logo to OPPLA
     */
    public function uploadRestaurantLogo(int $restaurantId, string $filePath): ?string
    {
        // Note: File upload with GraphQL typically uses multipart/form-data
        // This is a simplified version - might need adjustment based on actual API
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->attach(
                'file', file_get_contents($filePath), basename($filePath)
            )->post($this->apiUrl . '/upload/restaurant/' . $restaurantId . '/logo');

            if ($response->successful()) {
                $data = $response->json();
                return $data['url'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('OPPLA Logo Upload Error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get city areas with detailed info
     */
    public function getCityAreas(?int $cityId = null): array
    {
        $query = <<<'GRAPHQL'
        query GetCityAreas($cityId: ID) {
            cityAreas(cityId: $cityId) {
                id
                name
                slug
                city {
                    id
                    name
                }
                logistic_partner {
                    id
                    name
                }
                is_active
            }
        }
        GRAPHQL;

        $variables = $cityId ? ['cityId' => $cityId] : [];

        try {
            $result = $this->query($query, $variables);
            return $result['cityAreas'] ?? [];
        } catch (\Exception $e) {
            // Fallback to existing getDeliveryZones if this doesn't work
            Log::warning('getCityAreas not available, using getDeliveryZones', [
                'error' => $e->getMessage()
            ]);
            return $this->getDeliveryZones();
        }
    }

    /**
     * Create a new city area (delivery zone)
     */
    public function createCityArea(array $data): ?array
    {
        $mutation = <<<'GRAPHQL'
        mutation CreateCityArea($input: CreateCityAreaInput!) {
            createCityArea(input: $input) {
                id
                name
                slug
                city_id
                city {
                    id
                    name
                }
                logistic_partner_id
                is_active
            }
        }
        GRAPHQL;

        $variables = [
            'input' => [
                'name' => $data['name'],
                'city_id' => $data['city_id'],
                'slug' => $data['slug'] ?? \Illuminate\Support\Str::slug($data['name']),
                'logistic_partner_id' => $data['logistic_partner_id'] ?? null,
            ]
        ];

        try {
            $result = $this->query($mutation, $variables);
            return $result['createCityArea'] ?? null;
        } catch (\Exception $e) {
            // If API doesn't support this operation, return null
            // Will fallback to Filament scraping
            Log::warning('GraphQL city_area creation not supported, will use Filament scraping', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update an existing city area
     */
    public function updateCityArea(int $cityAreaId, array $data): ?array
    {
        $mutation = <<<'GRAPHQL'
        mutation UpdateCityArea($id: ID!, $input: UpdateCityAreaInput!) {
            updateCityArea(id: $id, input: $input) {
                id
                name
                slug
                city_id
                logistic_partner_id
                is_active
            }
        }
        GRAPHQL;

        $variables = [
            'id' => $cityAreaId,
            'input' => $data
        ];

        try {
            $result = $this->query($mutation, $variables);
            return $result['updateCityArea'] ?? null;
        } catch (\Exception $e) {
            Log::warning('GraphQL city_area update not supported, will use Filament scraping', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
