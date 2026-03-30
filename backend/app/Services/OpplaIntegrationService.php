<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * OPPLA Integration Service - Filament Scraping Approach
 * 
 * Uses Filament admin panel web scraping to create partners and restaurants.
 * When a partner is created via Filament form submission, OPPLA automatically
 * sends the PartnerCreated notification with password reset link.
 * 
 * This approach is necessary because OPPLA doesn't expose a REST API for
 * partner/restaurant creation - only Filament admin panel.
 */
class OpplaIntegrationService
{
    private OpplaGraphQLService $graphql;
    private OpplaAdminSyncService $syncService;
    private OpplaAdminScraperService $scraper;

    private ?string $sessionCookie = null;
    private ?string $csrfToken = null;
    private ?string $livewireToken = null;

    public function __construct(
        OpplaGraphQLService $graphql,
        OpplaAdminSyncService $syncService,
        OpplaAdminScraperService $scraper
    ) {
        $this->graphql = $graphql;
        $this->syncService = $syncService;
        $this->scraper = $scraper;
    }

    /**
     * Get the base URL for OPPLA admin
     */
    private function getBaseUrl(): string
    {
        return rtrim(config('oppla_admin.base_url', 'https://api.oppla.delivery'), '/');
    }

    /**
     * Login to Filament admin panel and get session cookie
     */
    private function loginToFilament(): bool
    {
        try {
            $baseUrl = $this->getBaseUrl();
            $email = config('oppla_admin.credentials.email');
            $password = config('oppla_admin.credentials.password');

            if (empty($email) || empty($password)) {
                throw new Exception('OPPLA admin credentials not configured');
            }

            // Step 1: Get login page to extract CSRF token
            $loginPageResponse = Http::withOptions(['verify' => false])
                ->withCookies([], parse_url($baseUrl, PHP_URL_HOST))
                ->get($baseUrl . '/admin/login');

            if (!$loginPageResponse->successful()) {
                throw new Exception('Failed to load Filament login page');
            }

            // Extract CSRF token from meta tag or input
            $html = $loginPageResponse->body();
            if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
                $this->csrfToken = $matches[1];
            } elseif (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $html, $matches)) {
                $this->csrfToken = $matches[1];
            }

            if (!$this->csrfToken) {
                throw new Exception('CSRF token not found on login page');
            }

            // Get cookies from login page response
            $cookies = $loginPageResponse->cookies();
            $cookieJar = [];
            foreach ($cookies as $cookie) {
                $cookieJar[$cookie->getName()] = $cookie->getValue();
            }

            // Step 2: Submit login form
            $loginResponse = Http::withOptions(['verify' => false])
                ->withCookies($cookieJar, parse_url($baseUrl, PHP_URL_HOST))
                ->asForm()
                ->post($baseUrl . '/admin/login', [
                    '_token' => $this->csrfToken,
                    'email' => $email,
                    'password' => $password,
                    'remember' => 'on',
                ]);

            // Check if login was successful (should redirect to dashboard)
            if ($loginResponse->status() !== 302 && $loginResponse->status() !== 200) {
                throw new Exception('Login failed with status: ' . $loginResponse->status());
            }

            // Extract session cookie
            $responseCookies = $loginResponse->cookies();
            $sessionCookies = [];
            foreach ($responseCookies as $cookie) {
                $sessionCookies[] = $cookie->getName() . '=' . $cookie->getValue();
            }
            // Also include original cookies
            foreach ($cookieJar as $name => $value) {
                $sessionCookies[] = $name . '=' . $value;
            }
            
            $this->sessionCookie = implode('; ', array_unique($sessionCookies));

            Log::info('Successfully logged into OPPLA Filament panel');
            return true;

        } catch (Exception $e) {
            Log::error('Filament login failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Ensure we have a valid Filament session
     */
    private function ensureAuthenticated(): void
    {
        if (!$this->sessionCookie) {
            if (!$this->loginToFilament()) {
                throw new Exception('Failed to authenticate with Filament admin panel');
            }
        }
    }

    /**
     * Make authenticated request to Filament
     */
    private function filamentRequest(string $method, string $url, array $data = []): \Illuminate\Http\Client\Response
    {
        $this->ensureAuthenticated();
        
        $baseUrl = $this->getBaseUrl();
        $fullUrl = $baseUrl . $url;

        $request = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Cookie' => $this->sessionCookie,
                'X-CSRF-TOKEN' => $this->csrfToken,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]);

        if ($method === 'get') {
            return $request->get($fullUrl);
        }
        
        return $request->asForm()->post($fullUrl, array_merge($data, ['_token' => $this->csrfToken]));
    }

    /**
     * Create partner via Filament admin panel.
     *
     * Submits the partner creation form in Filament, which triggers
     * the afterCreate hook that sends the PartnerCreated notification
     * (invite email with password reset link).
     *
     * Note: Only email, nome, cognome, telefono are submitted to the form.
     * The partner does NOT need a restaurant_id to be created on Oppla.
     *
     * @param array $data Partner data: email, nome, cognome, telefono
     * @return array|null Created partner data or null on failure
     */
    public function createPartner(array $data): ?array
    {
        try {
            $this->ensureAuthenticated();
            $baseUrl = $this->getBaseUrl();

            // Step 1: Load partner create page to get Livewire component data
            $createPageResponse = $this->filamentRequest('get', '/admin/partners/create');
            
            if (!$createPageResponse->successful()) {
                throw new Exception('Failed to load partner create page');
            }

            $html = $createPageResponse->body();

            // Extract Livewire snapshot and component info
            $livewireSnapshot = null;
            if (preg_match('/wire:snapshot="([^"]+)"/', $html, $matches)) {
                $livewireSnapshot = html_entity_decode($matches[1]);
            }

            // Extract new CSRF token if present
            if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
                $this->csrfToken = $matches[1];
            }

            // Step 2: Submit via Livewire (Filament v3 uses Livewire)
            // Filament forms are Livewire components, we need to call the create action
            
            $formData = [
                'email' => $data['email'],
                'phone' => $data['telefono'] ?? '',
                'first_name' => $data['nome'],
                'last_name' => $data['cognome'],
            ];

            // Try direct form submission (some Filament setups support this)
            $submitResponse = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Cookie' => $this->sessionCookie,
                    'X-CSRF-TOKEN' => $this->csrfToken,
                    'Accept' => 'application/json, text/html',
                    'X-Livewire' => 'true',
                ])
                ->asForm()
                ->post($baseUrl . '/admin/partners/create', array_merge($formData, [
                    '_token' => $this->csrfToken,
                ]));

            // Check response
            if ($submitResponse->successful() || $submitResponse->status() === 302) {
                Log::info('Partner created via Filament - Welcome email sent by OPPLA', [
                    'email' => $data['email'],
                    'response_status' => $submitResponse->status(),
                ]);

                // Try to extract partner ID from response/redirect
                $partnerId = null;
                $location = $submitResponse->header('Location');
                if ($location && preg_match('/partners\/(\d+)/', $location, $matches)) {
                    $partnerId = (int) $matches[1];
                }

                return [
                    'id' => $partnerId,
                    'email' => $data['email'],
                    'first_name' => $data['nome'],
                    'last_name' => $data['cognome'],
                    'created_via' => 'filament_scraping',
                ];
            }

            // If direct submission fails, log and try alternative approach
            Log::warning('Direct form submission failed, response: ' . $submitResponse->status());
            
            throw new Exception('Partner creation form submission failed');

        } catch (Exception $e) {
            Log::error('Partner creation via Filament failed', [
                'error' => $e->getMessage(),
                'email' => $data['email'] ?? 'unknown',
            ]);
            return null;
        }
    }

    /**
     * Create restaurant via Filament admin panel
     * 
     * @param array $data Restaurant data
     * @return array|null Created restaurant data or null on failure
     */
    public function createRestaurant(array $data): ?array
    {
        try {
            $this->ensureAuthenticated();
            $baseUrl = $this->getBaseUrl();

            // Load restaurant create page
            $createPageResponse = $this->filamentRequest('get', '/admin/restaurants/create');
            
            if (!$createPageResponse->successful()) {
                throw new Exception('Failed to load restaurant create page');
            }

            $html = $createPageResponse->body();

            // Extract new CSRF token if present
            if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
                $this->csrfToken = $matches[1];
            }

            $formData = [
                'name' => $data['nome'],
                'slug' => $data['slug'] ?? Str::slug($data['nome']),
                'phone' => $data['telefono'] ?? '',
                'address' => $data['indirizzo'] ?? '',
                'description' => $data['description'] ?? '',
                'preparation_time_minutes' => $data['preparation_time_minutes'] ?? 30,
                'accepts_deliveries' => $data['accepts_deliveries'] ?? true ? '1' : '0',
                'accepts_pickups' => $data['accepts_pickups'] ?? false ? '1' : '0',
                'accepts_cash' => $data['accepts_cash'] ?? true ? '1' : '0',
                'has_deliveries_managed' => ($data['delivery_management'] ?? 'oppla') === 'oppla' ? '1' : '0',
            ];

            // Submit form
            $submitResponse = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Cookie' => $this->sessionCookie,
                    'X-CSRF-TOKEN' => $this->csrfToken,
                    'Accept' => 'application/json, text/html',
                ])
                ->asForm()
                ->post($baseUrl . '/admin/restaurants/create', array_merge($formData, [
                    '_token' => $this->csrfToken,
                ]));

            if ($submitResponse->successful() || $submitResponse->status() === 302) {
                Log::info('Restaurant created via Filament', [
                    'name' => $data['nome'],
                    'response_status' => $submitResponse->status(),
                ]);

                // Try to extract restaurant ID from redirect
                $restaurantId = null;
                $location = $submitResponse->header('Location');
                if ($location && preg_match('/restaurants\/(\d+)/', $location, $matches)) {
                    $restaurantId = (int) $matches[1];
                }

                return [
                    'id' => $restaurantId,
                    'name' => $data['nome'],
                    'slug' => $formData['slug'],
                    'created_via' => 'filament_scraping',
                ];
            }

            throw new Exception('Restaurant creation form submission failed');

        } catch (Exception $e) {
            Log::error('Restaurant creation via Filament failed', [
                'error' => $e->getMessage(),
                'name' => $data['nome'] ?? 'unknown',
            ]);
            return null;
        }
    }

    /**
     * Get restaurant by ID (via GraphQL API)
     */
    public function getRestaurant(int $id): ?array
    {
        return $this->graphql->getRestaurant($id);
    }

    /**
     * Get active orders (via GraphQL API)
     */
    public function getActiveOrders(int $restaurantId): array
    {
        return $this->graphql->getActiveOrders($restaurantId);
    }

    /**
     * Get delivery zones (via GraphQL API)
     */
    public function getDeliveryZones(): array
    {
        return $this->graphql->getDeliveryZones();
    }

    /**
     * Sync data from OPPLA to local database
     */
    public function syncFromOppla(): array
    {
        return $this->syncService->syncAllData();
    }

    /**
     * Test connection to OPPLA Filament
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            return $this->loginToFilament();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get city areas (delivery zones) from OPPLA
     * Tries GraphQL API first, falls back to Filament scraping
     */
    public function getCityAreas(): array
    {
        try {
            // Try GraphQL API first
            $cityAreas = $this->graphql->getCityAreas();

            if (!empty($cityAreas)) {
                Log::info('[OpplaIntegration] City areas fetched via GraphQL API', [
                    'count' => count($cityAreas)
                ]);
                return $cityAreas;
            }
        } catch (\Exception $e) {
            Log::warning('[OpplaIntegration] GraphQL getCityAreas failed, trying scraper', [
                'error' => $e->getMessage()
            ]);
        }

        // Fallback to Filament scraping
        try {
            $cityAreas = $this->scraper->fetchCityAreas();

            Log::info('[OpplaIntegration] City areas fetched via Filament scraping', [
                'count' => count($cityAreas)
            ]);

            return $cityAreas;
        } catch (\Exception $e) {
            Log::error('[OpplaIntegration] Failed to fetch city areas', [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Unable to fetch city areas from OPPLA: ' . $e->getMessage());
        }
    }

    /**
     * Create a new city area (delivery zone) in OPPLA
     * Tries GraphQL API first, falls back to Filament scraping
     */
    public function createCityArea(array $data): ?array
    {
        // First, ensure we have a city_id
        if (empty($data['city_id'])) {
            // Need to find or create the city
            $data['city_id'] = $this->findOrCreateCity($data['city_name']);
        }

        // Try GraphQL API first
        try {
            $result = $this->graphql->createCityArea($data);

            if ($result) {
                Log::info('[OpplaIntegration] City area created via GraphQL API', [
                    'id' => $result['id'] ?? null,
                    'name' => $data['name']
                ]);
                return $result;
            }
        } catch (\Exception $e) {
            Log::warning('[OpplaIntegration] GraphQL createCityArea failed, trying Filament', [
                'error' => $e->getMessage()
            ]);
        }

        // Fallback to Filament scraping
        try {
            $result = $this->scraper->createCityArea($data);

            Log::info('[OpplaIntegration] City area created via Filament scraping', [
                'id' => $result['id'] ?? null,
                'name' => $data['name']
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('[OpplaIntegration] Failed to create city area', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new \Exception('Unable to create city area in OPPLA: ' . $e->getMessage());
        }
    }

    /**
     * Find or create a city in OPPLA (needed for city_area creation)
     * Uses minimal read-only DB access for city lookup
     */
    private function findOrCreateCity(string $cityName): int
    {
        try {
            // Minimal read-only DB access for city lookup
            // This is acceptable since cities are infrastructure data
            $city = \DB::connection('oppla')
                ->table('cities')
                ->where('name', $cityName)
                ->first();

            if ($city) {
                return $city->id;
            }

            // For creation, must use API/scraping or manual creation
            throw new \Exception("City '{$cityName}' not found in OPPLA. Please create it via Filament admin panel first.");

        } catch (\Exception $e) {
            Log::error('[OpplaIntegration] City lookup failed', [
                'city_name' => $cityName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
