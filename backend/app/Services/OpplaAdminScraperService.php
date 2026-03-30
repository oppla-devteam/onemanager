<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class OpplaAdminScraperService
{
    private Client $client;
    private CookieJar $cookieJar;
    private bool $authenticated = false;
    private string $baseUrl;
    private array $credentials;

    public function __construct()
    {
        $this->baseUrl = config('oppla_admin.base_url');
        $this->credentials = config('oppla_admin.credentials');
        $this->cookieJar = new CookieJar();
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => config('oppla_admin.timeout', 30),
            'cookies' => $this->cookieJar,
            'verify' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'it-IT,it;q=0.9,en;q=0.8',
            ],
        ]);
    }

    /**
     * Autentica sul pannello admin Filament
     */
    public function authenticate(): bool
    {
        if ($this->authenticated) {
            return true;
        }

        try {
            Log::info('[OpplaSync] Tentativo autenticazione...', [
                'email' => $this->credentials['email'],
                'url' => $this->baseUrl . config('oppla_admin.routes.login')
            ]);

            // Step 1: GET login page to extract CSRF token and Livewire data
            $loginPageResponse = $this->client->get(config('oppla_admin.routes.login'));
            $loginPageHtml = (string) $loginPageResponse->getBody();

            $crawler = new Crawler($loginPageHtml);

            // Extract CSRF token
            $token = null;
            $metaTag = $crawler->filter('meta[name="csrf-token"]')->first();
            if ($metaTag->count() > 0) {
                $token = $metaTag->attr('content');
            }

            if (!$token) {
                $csrfToken = $crawler->filter('input[name="_token"]')->first();
                $token = $csrfToken->count() > 0 ? $csrfToken->attr('value') : null;
            }

            if (!$token) {
                Log::error('[OpplaSync] CSRF token non trovato');
                return false;
            }

            Log::info('[OpplaSync] CSRF token ottenuto', ['token_length' => strlen($token)]);

            // Step 2: Try Livewire format first (Filament 3.x)
            if ($this->authenticateWithLivewire($loginPageHtml, $token)) {
                return true;
            }

            Log::info('[OpplaSync] Livewire auth failed, trying standard form POST');

            // Step 3: Fallback to standard form POST
            $loginResponse = $this->client->post(config('oppla_admin.routes.login'), [
                'form_params' => [
                    '_token' => $token,
                    'email' => $this->credentials['email'],
                    'password' => $this->credentials['password'],
                ],
                'allow_redirects' => true,
            ]);

            $statusCode = $loginResponse->getStatusCode();
            $responseBody = (string) $loginResponse->getBody();

            if ($statusCode === 200 && !str_contains($responseBody, 'login')) {
                $this->authenticated = true;
                Log::info('[OpplaSync] Autenticazione riuscita (form POST)');
                return true;
            }

            if (str_contains($responseBody, 'Invalid credentials') ||
                str_contains($responseBody, 'credenziali') ||
                str_contains($responseBody, 'email') && str_contains($responseBody, 'password')) {
                Log::error('[OpplaSync] Credenziali invalide');
                return false;
            }

            $this->authenticated = true;
            Log::info('[OpplaSync] Autenticazione completata (redirect detected)');
            return true;

        } catch (\Exception $e) {
            Log::error('[OpplaSync] Errore autenticazione', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Authenticate using Livewire format (Filament 3.x)
     */
    private function authenticateWithLivewire(string $html, string $csrfToken): bool
    {
        try {
            // Extract Livewire component data from wire:snapshot
            if (!preg_match('/wire:snapshot="([^"]+)"/', $html, $snapshotMatches)) {
                Log::info('[OpplaSync] No Livewire wire:snapshot found');
                return false;
            }

            $snapshotJson = html_entity_decode($snapshotMatches[1]);
            $snapshot = json_decode($snapshotJson, true);

            if (!$snapshot || !isset($snapshot['memo']['id'])) {
                Log::warning('[OpplaSync] Invalid Livewire snapshot structure');
                return false;
            }

            $componentId = $snapshot['memo']['id'];
            $componentName = $snapshot['memo']['name'] ?? 'filament.pages.auth.login';

            Log::info('[OpplaSync] Found Livewire component', [
                'id' => $componentId,
                'name' => $componentName
            ]);

            // Livewire v3 payload structure
            $payload = [
                [
                    'snapshot' => json_encode($snapshot),
                    'updates' => [
                        ['type' => 'syncInput', 'payload' => ['name' => 'data.email', 'value' => $this->credentials['email']]],
                        ['type' => 'syncInput', 'payload' => ['name' => 'data.password', 'value' => $this->credentials['password']]],
                        ['type' => 'callMethod', 'payload' => ['method' => 'authenticate', 'params' => []]],
                    ],
                    'calls' => [
                        ['method' => 'authenticate', 'params' => []],
                    ],
                ]
            ];

            // POST to Livewire v3 update endpoint
            $livewireResponse = $this->client->post('/livewire/update', [
                'json' => $payload,
                'headers' => [
                    'X-Livewire' => 'true',
                    'X-CSRF-TOKEN' => $csrfToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'text/html, application/xhtml+xml',
                    'Referer' => $this->baseUrl . config('oppla_admin.routes.login'),
                ],
                'allow_redirects' => false,
            ]);

            $statusCode = $livewireResponse->getStatusCode();
            $responseBody = (string) $livewireResponse->getBody();

            Log::info('[OpplaSync] Livewire response', [
                'status' => $statusCode,
                'body_preview' => substr($responseBody, 0, 300),
            ]);

            // Check for successful authentication
            if ($statusCode === 200) {
                $responseData = json_decode($responseBody, true);

                // Livewire v3 responses - check for redirects or lack of errors
                if (is_array($responseData) && count($responseData) > 0) {
                    $firstComponent = $responseData[0] ?? [];

                    // Check for redirect effect
                    $redirectUrl = null;
                    if (isset($firstComponent['effects']['redirect'])) {
                        $redirectUrl = $firstComponent['effects']['redirect'];
                    } elseif (isset($firstComponent['redirectTo'])) {
                        $redirectUrl = $firstComponent['redirectTo'];
                    }

                    if ($redirectUrl) {
                        // Follow the redirect to establish session
                        Log::info('[OpplaSync] Following redirect to establish session', ['url' => $redirectUrl]);
                        try {
                            $this->client->get($redirectUrl, ['allow_redirects' => true]);
                        } catch (\Exception $e) {
                            Log::warning('[OpplaSync] Redirect follow failed', ['error' => $e->getMessage()]);
                        }
                        $this->authenticated = true;
                        Log::info('[OpplaSync] Autenticazione Livewire riuscita (redirect followed)');
                        return true;
                    }

                    // Check for errors
                    if (!isset($firstComponent['effects']['errors']) ||
                        empty($firstComponent['effects']['errors'])) {
                        // No explicit redirect, try to access dashboard to establish session
                        Log::info('[OpplaSync] No redirect, accessing dashboard to establish session');
                        try {
                            $this->client->get('/admin', ['allow_redirects' => true]);
                        } catch (\Exception $e) {
                            Log::warning('[OpplaSync] Dashboard access failed', ['error' => $e->getMessage()]);
                        }
                        $this->authenticated = true;
                        Log::info('[OpplaSync] Autenticazione Livewire riuscita (session established)');
                        return true;
                    }
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::warning('[OpplaSync] Livewire authentication failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Scarica lista partners dal pannello admin
     */
    public function fetchPartners(): array
    {
        if (!$this->authenticate()) {
            throw new \Exception('Autenticazione fallita');
        }

        try {
            Log::info('[OpplaSync] Fetching partners list...');

            $response = $this->client->get(config('oppla_admin.routes.partners'));
            $html = (string) $response->getBody();
            
            $crawler = new Crawler($html);
            $partners = [];

            // Filament usa tabelle HTML per le liste
            // Parsing della tabella partners
            $crawler->filter('table tbody tr')->each(function (Crawler $row) use (&$partners) {
                try {
                    $cells = $row->filter('td');
                    
                    if ($cells->count() < 3) {
                        return;
                    }

                    // Estrai dati partner (adatta i selettori alla struttura reale)
                    $partner = [
                        'external_id' => $this->extractId($row),
                        'ragione_sociale' => trim($cells->eq(0)->text()),
                        'email' => $this->extractEmail($cells),
                        'piva' => $this->extractPIVA($cells),
                        'telefono' => $this->extractPhone($cells),
                        'indirizzo' => $this->extractAddress($cells),
                        'citta' => $this->extractCity($cells),
                        'raw_html' => $row->html(), // Per debug
                    ];

                    // Filtra partner vuoti
                    if (!empty($partner['ragione_sociale'])) {
                        $partners[] = $partner;
                    }

                } catch (\Exception $e) {
                    Log::warning('[OpplaSync] Errore parsing partner row', [
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            Log::info('[OpplaSync] Partners recuperati', ['count' => count($partners)]);
            return $partners;

        } catch (\Exception $e) {
            Log::error('[OpplaSync] Errore fetch partners', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Scarica lista ristoranti per un partner specifico
     */
    public function fetchRestaurantsForPartner(string $partnerId): array
    {
        if (!$this->authenticate()) {
            throw new \Exception('Autenticazione fallita');
        }

        try {
            Log::info('[OpplaSync] Fetching restaurants for partner', ['partner_id' => $partnerId]);

            // Costruisci URL per i ristoranti del partner
            // Potrebbe essere /admin/partners/{id}/restaurants o simile
            $url = config('oppla_admin.routes.restaurants') . '?partner_id=' . $partnerId;
            
            $response = $this->client->get($url);
            $html = (string) $response->getBody();
            
            $crawler = new Crawler($html);
            $restaurants = [];

            $crawler->filter('table tbody tr')->each(function (Crawler $row) use (&$restaurants, $partnerId) {
                try {
                    $cells = $row->filter('td');
                    
                    if ($cells->count() < 2) {
                        return;
                    }

                    $restaurant = [
                        'external_id' => $this->extractId($row),
                        'partner_id' => $partnerId,
                        'nome' => trim($cells->eq(0)->text()),
                        'indirizzo' => $this->extractAddress($cells),
                        'citta' => $this->extractCity($cells),
                        'telefono' => $this->extractPhone($cells),
                        'email' => $this->extractEmail($cells),
                        'cucina' => $this->extractCuisineType($cells),
                        'raw_html' => $row->html(),
                    ];

                    if (!empty($restaurant['nome'])) {
                        $restaurants[] = $restaurant;
                    }

                } catch (\Exception $e) {
                    Log::warning('[OpplaSync] Errore parsing restaurant row', [
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            Log::info('[OpplaSync] Restaurants recuperati', [
                'partner_id' => $partnerId,
                'count' => count($restaurants)
            ]);

            return $restaurants;

        } catch (\Exception $e) {
            Log::error('[OpplaSync] Errore fetch restaurants', [
                'partner_id' => $partnerId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Estrai ID dalla riga (cerca in link o attributi data)
     */
    private function extractId(Crawler $row): ?string
    {
        // Cerca link con pattern /admin/partners/123 o data-id
        try {
            $link = $row->filter('a')->first();
            if ($link->count() > 0) {
                $href = $link->attr('href');
                if (preg_match('/\/(\d+)(?:\/|$)/', $href, $matches)) {
                    return $matches[1];
                }
            }

            // Cerca attributo data-id
            if ($row->attr('data-id')) {
                return $row->attr('data-id');
            }

        } catch (\Exception $e) {
            // Ignora
        }

        return null;
    }

    /**
     * Estrai email dalla riga
     */
    private function extractEmail(Crawler $cells): ?string
    {
        try {
            // Cerca mailto: link
            $emailLink = $cells->filter('a[href^="mailto:"]')->first();
            if ($emailLink->count() > 0) {
                return str_replace('mailto:', '', $emailLink->attr('href'));
            }

            // Cerca pattern email nel testo
            $text = $cells->text();
            if (preg_match('/[\w\.-]+@[\w\.-]+\.\w+/', $text, $matches)) {
                return $matches[0];
            }

        } catch (\Exception $e) {
            // Ignora
        }

        return null;
    }

    /**
     * Estrai P.IVA
     */
    private function extractPIVA(Crawler $cells): ?string
    {
        try {
            $text = $cells->text();
            // Pattern P.IVA italiana: 11 cifre
            if (preg_match('/\b(\d{11})\b/', $text, $matches)) {
                return $matches[1];
            }
        } catch (\Exception $e) {
            // Ignora
        }

        return null;
    }

    /**
     * Estrai telefono
     */
    private function extractPhone(Crawler $cells): ?string
    {
        try {
            // Cerca tel: link
            $telLink = $cells->filter('a[href^="tel:"]')->first();
            if ($telLink->count() > 0) {
                return str_replace('tel:', '', $telLink->attr('href'));
            }

            $text = $cells->text();
            // Pattern telefono italiano
            if (preg_match('/\+?39[\s\-]?(\d{2,4})[\s\-]?(\d{6,8})/', $text, $matches)) {
                return '+39' . $matches[1] . $matches[2];
            }

        } catch (\Exception $e) {
            // Ignora
        }

        return null;
    }

    /**
     * Estrai indirizzo
     */
    private function extractAddress(Crawler $cells): ?string
    {
        try {
            // Cerca cella con pattern indirizzo (via, piazza, etc)
            foreach ($cells as $cell) {
                $text = trim($cell->textContent);
                if (preg_match('/^(via|piazza|corso|viale|largo)\s+/i', $text)) {
                    return $text;
                }
            }
        } catch (\Exception $e) {
            // Ignora
        }

        return null;
    }

    /**
     * Estrai città
     */
    private function extractCity(Crawler $cells): ?string
    {
        try {
            $text = $cells->text();
            // Pattern CAP + Città
            if (preg_match('/\b(\d{5})\s+([A-Z][a-zA-Z\s]+)/', $text, $matches)) {
                return trim($matches[2]);
            }
        } catch (\Exception $e) {
            // Ignora
        }

        return null;
    }

    /**
     * Estrai tipo cucina
     */
    private function extractCuisineType(Crawler $cells): ?string
    {
        try {
            // Cerca badge o span con classe cuisine/category
            $badge = $cells->filter('.badge, .category, .cuisine')->first();
            if ($badge->count() > 0) {
                return trim($badge->text());
            }
        } catch (\Exception $e) {
            // Ignora
        }

        return null;
    }

    /**
     * Logout (pulizia sessione)
     */
    public function logout(): void
    {
        try {
            $this->client->post('/admin/logout');
        } catch (\Exception $e) {
            // Ignora errori logout
        }

        $this->authenticated = false;
        $this->cookieJar = new CookieJar();
    }

    /**
     * Fetch city areas from Filament admin panel
     */
    public function fetchCityAreas(): array
    {
        if (!$this->authenticate()) {
            throw new \Exception('Autenticazione fallita');
        }

        try {
            Log::info('[OpplaSync] Fetching city areas list...');

            // URL potrebbe essere /admin/city-areas o /admin/delivery-zones
            $response = $this->client->get('/admin/city-areas');
            $html = (string) $response->getBody();

            $crawler = new Crawler($html);
            $cityAreas = [];

            // Parse tabella city_areas
            $crawler->filter('table tbody tr')->each(function (Crawler $row) use (&$cityAreas) {
                try {
                    $cells = $row->filter('td');

                    if ($cells->count() < 2) {
                        return;
                    }

                    $name = trim($cells->eq(0)->text());

                    // IMPORTANTE: Filtra nomi di ristoranti invece di zone geografiche
                    if ($this->isRestaurantName($name)) {
                        Log::debug('[OpplaSync] Skipping restaurant name as zone', [
                            'name' => $name
                        ]);
                        return;
                    }

                    $cityArea = [
                        'external_id' => $this->extractId($row),
                        'name' => $name,
                        'slug' => $this->extractSlug($row),
                        'city_name' => $this->extractCityFromRow($cells),
                        'logistic_partner' => $this->extractLogisticPartner($cells),
                        'raw_html' => $row->html(),
                    ];

                    if (!empty($cityArea['name'])) {
                        $cityAreas[] = $cityArea;
                    }

                } catch (\Exception $e) {
                    Log::warning('[OpplaSync] Errore parsing city_area row', [
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            Log::info('[OpplaSync] City areas recuperate', ['count' => count($cityAreas)]);
            return $cityAreas;

        } catch (\Exception $e) {
            Log::error('[OpplaSync] Errore fetch city areas', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create city area via Filament form submission
     */
    public function createCityArea(array $data): ?array
    {
        if (!$this->authenticate()) {
            throw new \Exception('Autenticazione fallita');
        }

        try {
            Log::info('[OpplaSync] Creating city area via Filament', ['name' => $data['name']]);

            // Step 1: Load create page
            $createPageResponse = $this->client->get('/admin/city-areas/create');
            $createPageHtml = (string) $createPageResponse->getBody();

            $crawler = new Crawler($createPageHtml);

            // Extract new CSRF token
            $csrfToken = $crawler->filter('input[name="_token"]')->first();
            $token = $csrfToken->count() > 0 ? $csrfToken->attr('value') : null;

            if (!$token) {
                throw new \Exception('CSRF token not found');
            }

            // Step 2: Submit form
            $formData = [
                '_token' => $token,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? \Illuminate\Support\Str::slug($data['name']),
                'city_id' => $data['city_id'],
            ];

            if (isset($data['logistic_partner_id'])) {
                $formData['logistic_partner_id'] = $data['logistic_partner_id'];
            }

            $submitResponse = $this->client->post('/admin/city-areas', [
                'form_params' => $formData,
                'allow_redirects' => true,
            ]);

            $statusCode = $submitResponse->getStatusCode();

            if ($statusCode === 200 || $statusCode === 302) {
                // Extract ID from redirect location
                $cityAreaId = null;
                $location = $submitResponse->getHeaderLine('Location');
                if ($location && preg_match('/city-areas\/(\d+)/', $location, $matches)) {
                    $cityAreaId = (int) $matches[1];
                }

                Log::info('[OpplaSync] City area created via Filament', [
                    'id' => $cityAreaId,
                    'name' => $data['name']
                ]);

                return [
                    'id' => $cityAreaId,
                    'name' => $data['name'],
                    'slug' => $formData['slug'],
                    'created_via' => 'filament_scraping',
                ];
            }

            throw new \Exception('Form submission failed with status: ' . $statusCode);

        } catch (\Exception $e) {
            Log::error('[OpplaSync] City area creation failed', [
                'error' => $e->getMessage(),
                'name' => $data['name'] ?? 'unknown',
            ]);
            throw $e;
        }
    }

    /**
     * Helper: Extract city name from table row
     */
    private function extractCityFromRow(Crawler $cells): ?string
    {
        try {
            // Cerca cella che contiene il nome della città
            foreach ($cells as $cell) {
                $text = trim($cell->textContent);
                // Le città sono tipicamente in una cella separata
                if (preg_match('/^[A-Z][a-zA-Z\s]+$/', $text) && strlen($text) < 50) {
                    return $text;
                }
            }
        } catch (\Exception $e) {
            // Ignora
        }
        return null;
    }

    /**
     * Helper: Extract logistic partner
     */
    private function extractLogisticPartner(Crawler $cells): ?string
    {
        try {
            $text = $cells->text();
            // Cerca pattern come "Gestita da: Nome Partner"
            if (preg_match('/Gestita da[:\s]+([A-Za-z\s]+)/', $text, $matches)) {
                return trim($matches[1]);
            }
        } catch (\Exception $e) {
            // Ignora
        }
        return null;
    }

    /**
     * Helper: Extract slug from row
     */
    private function extractSlug(Crawler $row): ?string
    {
        try {
            $link = $row->filter('a')->first();
            if ($link->count() > 0) {
                $href = $link->attr('href');
                if (preg_match('/\/([a-z0-9\-]+)(?:\/|$)/', $href, $matches)) {
                    return $matches[1];
                }
            }
        } catch (\Exception $e) {
            // Ignora
        }
        return null;
    }

    /**
     * Check if a name looks like a restaurant name instead of a geographic zone
     *
     * Zone geografiche valide: "Livorno Centro", "Pisa Nord", "Roma EUR"
     * Nomi ristoranti da escludere: "Pizzeria Da Mario", "Ristorante Bella Vista", "Bar Centrale"
     *
     * @param string $name
     * @return bool True if it looks like a restaurant name
     */
    private function isRestaurantName(string $name): bool
    {
        $nameLower = mb_strtolower($name);

        // Pattern tipici di ristoranti
        $restaurantKeywords = [
            'pizzeria',
            'ristorante',
            'trattoria',
            'osteria',
            'bar',
            'cafè',
            'cafe',
            'bistrot',
            'bistro',
            'pub',
            'paninoteca',
            'hamburgeria',
            'gelateria',
            'pasticceria',
            'braceria',
            'steakhouse',
            'sushi',
            'poke',
            'kebab',
            'street food',
            'food truck',
            'da ',      // es: "Da Mario"
            'al ',      // es: "Al Grottino"
            'la ',      // es: "La Pergola" (solo all'inizio)
            'il ',      // es: "Il Gambero" (solo all'inizio)
            'lo ',      // es: "Lo Scoglio"
            "l'",       // es: "L'Ostrica"
        ];

        // Controlla se contiene keyword di ristoranti
        foreach ($restaurantKeywords as $keyword) {
            if (str_contains($nameLower, $keyword)) {
                // Eccezioni: alcune zone potrebbero contenere queste parole
                // es: "Zona Bar" (zona industriale), "Via del Ristoro"
                // Ma generalmente se contiene keyword + altri identificatori, è un ristorante
                return true;
            }
        }

        // Pattern di nomi di ristoranti specifici
        // Contiene apostrofo seguito da nome proprio (es: "L'Approdo", "D'Amore")
        if (preg_match("/[ld]'[a-z]/i", $name)) {
            return true;
        }

        // Contiene "& " o " & " (es: "Fish & Chips", "Meat & Wine")
        if (str_contains($name, ' & ') || str_contains($name, '&')) {
            return true;
        }

        // Se contiene numeri seguiti da parole (es: "1000 Miglia", "2 Fratelli")
        // Probabile nome ristorante
        if (preg_match('/^\d+\s+[a-z]/i', $name)) {
            return true;
        }

        // Pattern positivi: zone geografiche valide
        // Contiene indicatori geografici + punti cardinali/zone
        $validZonePatterns = [
            '/^(livorno|pisa|lucca|firenze|roma|milano|napoli|torino)\s+(centro|nord|sud|est|ovest)/i',
            '/^(zona|area)\s+/i',
            '/^(centro\s+storico|centro|periferia)/i',
            '/\s+(centro|nord|sud|est|ovest|centrale|settentrionale|meridionale)$/i',
        ];

        foreach ($validZonePatterns as $pattern) {
            if (preg_match($pattern, $name)) {
                // È una zona geografica valida
                return false;
            }
        }

        // Se il nome è molto corto (1-2 parole) e non contiene keyword
        // potrebbe essere una zona valida (es: "Centro", "Periferia")
        $wordCount = str_word_count($name);
        if ($wordCount <= 2 && strlen($name) < 30) {
            // Probabile zona geografica
            return false;
        }

        // Se il nome è lungo (> 3 parole) e non ha pattern geografici
        // probabilmente è un ristorante
        if ($wordCount > 3) {
            return true;
        }

        // Default: assume sia una zona valida se non ha indicatori chiari di ristorante
        return false;
    }
}
