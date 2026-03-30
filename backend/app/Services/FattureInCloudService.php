<?php

namespace App\Services;

use App\Models\FattureInCloudConnection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FattureInCloudService
{
    private Client $client;
    private array $config;

    public function __construct()
    {
        $this->config = config('fatture_in_cloud');
        $this->client = new Client([
            'base_uri' => $this->config['base_url'],
            'timeout' => $this->config['timeout'],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Valida formato Partita IVA italiana (11 cifre)
     */
    private function validatePartitaIVA(?string $piva): bool
    {
        if (empty($piva)) {
            return false;
        }
        // P.IVA italiana: 11 cifre numeriche
        return preg_match('/^\d{11}$/', $piva) === 1;
    }

    /**
     * Valida formato Codice Fiscale italiano (16 caratteri alfanumerici)
     */
    private function validateCodiceFiscale(?string $cf): bool
    {
        if (empty($cf)) {
            return false;
        }
        // Codice Fiscale: 16 caratteri alfanumerici (persone fisiche)
        // O 11 cifre per aziende (uguale a P.IVA)
        return preg_match('/^[A-Z0-9]{11,16}$/i', $cf) === 1;
    }

    /**
     * Valida dati cliente per creazione in Fatture in Cloud
     * 
     * @throws \Exception con messaggio dettagliato di errore
     */
    private function validateClientDataForFIC(\App\Models\Client $client): void
    {
        $errors = [];

        // 1. Ragione sociale obbligatoria
        if (empty($client->ragione_sociale)) {
            $errors[] = 'Ragione sociale mancante (campo obbligatorio)';
        }

        // 2. Almeno uno tra P.IVA o Codice Fiscale
        if (empty($client->piva) && empty($client->codice_fiscale)) {
            $errors[] = 'Mancano sia P.IVA che Codice Fiscale (almeno uno obbligatorio)';
        }

        // 3. Valida formato P.IVA se presente
        if (!empty($client->piva) && !$this->validatePartitaIVA($client->piva)) {
            $errors[] = "P.IVA non valida: '{$client->piva}' (deve essere 11 cifre numeriche)";
        }

        // 4. Valida formato Codice Fiscale se presente
        if (!empty($client->codice_fiscale) && !$this->validateCodiceFiscale($client->codice_fiscale)) {
            $errors[] = "Codice Fiscale non valido: '{$client->codice_fiscale}' (deve essere 11-16 caratteri alfanumerici)";
        }

        // 5. Per fatture elettroniche, valida SDI code o PEC
        // Nota: Questo controllo è fatto solo a livello informativo, non blocca la creazione
        if (empty($client->sdi_code) && empty($client->pec)) {
            Log::warning('[FattureInCloudService] Cliente senza SDI code né PEC', [
                'client_id' => $client->id,
                'ragione_sociale' => $client->ragione_sociale
            ]);
        }

        // 6. Valida formato SDI code se presente (7 caratteri alfanumerici)
        if (!empty($client->sdi_code) && !preg_match('/^[A-Z0-9]{7}$/i', $client->sdi_code)) {
            $errors[] = "Codice SDI non valido: '{$client->sdi_code}' (deve essere 7 caratteri alfanumerici, es: SUBM70N)";
        }

        // 7. Valida formato email se presente
        if (!empty($client->email) && !filter_var($client->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email non valida: '{$client->email}'";
        }

        // 8. Valida formato PEC se presente
        if (!empty($client->pec) && !filter_var($client->pec, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "PEC non valida: '{$client->pec}'";
        }

        // 9. Valida lunghezza provincia (2 caratteri)
        if (!empty($client->provincia) && strlen($client->provincia) !== 2) {
            $errors[] = "Provincia non valida: '{$client->provincia}' (deve essere 2 caratteri, es: MI, RM, NA)";
        }

        // 10. Valida formato CAP (5 cifre)
        if (!empty($client->cap) && !preg_match('/^\d{5}$/', $client->cap)) {
            $errors[] = "CAP non valido: '{$client->cap}' (deve essere 5 cifre)";
        }

        if (!empty($errors)) {
            $errorMessage = "Validazione cliente fallita per FIC:\n" . implode("\n", array_map(fn($e) => "- {$e}", $errors));
            Log::error('[FattureInCloudService] Validazione cliente fallita', [
                'client_id' => $client->id,
                'errors' => $errors
            ]);
            throw new \Exception($errorMessage);
        }

        Log::info('[FattureInCloudService] Validazione cliente OK', [
            'client_id' => $client->id,
            'ragione_sociale' => $client->ragione_sociale
        ]);
    }

    /**
     * Get default OAuth scopes.
     */
    public function getDefaultScopes(): array
    {
        return $this->config['default_scopes'];
    }

    /**
     * Generate the OAuth authorization URL.
     */
    public function getAuthorizationUrl(string $state, ?array $scopes = null): string
    {
        $scopes = $scopes ?? $this->config['default_scopes'];
        
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->config['oauth']['client_id'],
            'redirect_uri' => $this->config['oauth']['redirect_uri'],
            'scope' => implode(' ', $scopes),
            'state' => $state,
        ]);

        return $this->config['oauth']['authorize_url'] . '?' . $query;
    }

    /**
     * Exchange authorization code for access token.
     */
    public function exchangeCodeForToken(string $code): ?array
    {
        try {
            $response = $this->client->post($this->config['oauth']['token_url'], [
                'json' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => $this->config['oauth']['client_id'],
                    'client_secret' => $this->config['oauth']['client_secret'],
                    'redirect_uri' => $this->config['oauth']['redirect_uri'],
                    'code' => $code,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'token_type' => $data['token_type'],
                'expires_in' => $data['expires_in'],
                'token_expires_at' => Carbon::now()->addSeconds($data['expires_in']),
                'refresh_token_expires_at' => Carbon::now()->addYear(),
            ];
        } catch (GuzzleException $e) {
            Log::error('FIC OAuth token exchange failed', [
                'error' => $e->getMessage(),
                'code' => $code,
            ]);
            return null;
        }
    }

    /**
     * Refresh an expired access token.
     */
    public function refreshToken(FattureInCloudConnection $connection): ?array
    {
        if ($connection->isRefreshTokenExpired()) {
            Log::warning('FIC refresh token expired', ['connection_id' => $connection->id]);
            return null;
        }

        try {
            $response = $this->client->post($this->config['oauth']['token_url'], [
                'json' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->config['oauth']['client_id'],
                    'client_secret' => $this->config['oauth']['client_secret'],
                    'refresh_token' => $connection->refresh_token,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $tokenData = [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'token_expires_at' => Carbon::now()->addSeconds($data['expires_in']),
                'refresh_token_expires_at' => Carbon::now()->addYear(),
            ];

            // Update connection with new tokens
            $connection->update($tokenData);

            return $tokenData;
        } catch (GuzzleException $e) {
            Log::error('FIC token refresh failed', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id,
            ]);
            return null;
        }
    }

    /**
     * Get user companies from Fatture in Cloud.
     */
    public function getUserCompanies(FattureInCloudConnection $connection): ?array
    {
        $this->ensureValidToken($connection);

        try {
            $response = $this->client->get('/user/companies', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $connection->access_token,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            // La risposta dell'API è { "data": { "companies": [...] } }
            // Restituiamo l'array di companies
            return $data['data']['companies'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('FIC get companies failed', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id,
            ]);
            return null;
        }
    }

    /**
     * Get issued invoices from Fatture in Cloud.
     */
    public function getIssuedInvoices(
        FattureInCloudConnection $connection,
        ?array $filters = []
    ): ?array {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;
        $queryParams = $this->buildQueryParams($filters);

        try {
            $response = $this->client->get("/c/{$companyId}/issued_documents", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $connection->access_token,
                ],
                'query' => $queryParams,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            // Log per debug
            Log::info('[FattureInCloudService] getIssuedInvoices response', [
                'has_data_key' => isset($data['data']),
                'data_count' => isset($data['data']) ? count($data['data']) : 0,
                'response_keys' => array_keys($data ?? []),
                'query_params' => $queryParams,
            ]);
            
            // Ritorna l'oggetto completo con pagination info, non solo data
            return $data;
        } catch (GuzzleException $e) {
            Log::error('FIC get issued invoices failed', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id,
                'filters' => $filters,
            ]);
            return null;
        }
    }

    /**
     * Create an issued invoice in Fatture in Cloud.
     */
    public function createIssuedInvoice(
        FattureInCloudConnection $connection,
        array $invoiceData
    ): ?array {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;

        try {
            $response = $this->client->post("/c/{$companyId}/issued_documents", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $connection->access_token,
                ],
                'json' => ['data' => $invoiceData],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? null;
        } catch (GuzzleException $e) {
            // Cattura la risposta di errore dal server FIC
            $errorBody = null;
            $errorData = null;
            $statusCode = null;
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $errorBody = $response->getBody()->getContents();
                $errorData = json_decode($errorBody, true);
            }
            
            // Gestione speciale per 409 Conflict (documento già esistente)
            if ($statusCode === 409) {
                $errorMessage = $errorData['error']['message'] ?? 'Documento duplicato';
                Log::warning('FIC invoice already exists (409) - saltando creazione', [
                    'error' => $errorMessage,
                    'status_code' => $statusCode,
                    'invoice_number' => $invoiceData['number'] ?? null,
                    'invoice_data_preview' => [
                        'type' => $invoiceData['type'] ?? null,
                        'entity_id' => $invoiceData['entity']['id'] ?? null,
                        'entity_name' => $invoiceData['entity']['name'] ?? null,
                        'number' => $invoiceData['number'] ?? null,
                        'date' => $invoiceData['date'] ?? null,
                    ],
                ]);

                // Ritorna null invece di lanciare eccezione - il controller gestirà la situazione
                // Questo permette di saltare fatture duplicate senza bloccare l'intero processo
                return null;
            }
            
            Log::error('FIC create issued invoice failed', [
                'error' => $e->getMessage(),
                'status_code' => $statusCode,
                'error_body' => $errorBody,
                'error_data' => $errorData,
                'connection_id' => $connection->id,
                'invoice_data_preview' => [
                    'type' => $invoiceData['type'] ?? null,
                    'entity_id' => $invoiceData['entity']['id'] ?? null,
                    'entity_name' => $invoiceData['entity']['name'] ?? null,
                    'number' => $invoiceData['number'] ?? null,
                    'date' => $invoiceData['date'] ?? null,
                    'items_count' => count($invoiceData['items_list'] ?? []),
                    'e_invoice' => $invoiceData['e_invoice'] ?? false,
                ],
            ]);
            return null;
        }
    }

    /**
     * Get received invoices (passive) from Fatture in Cloud.
     */
    public function getReceivedDocuments(
        FattureInCloudConnection $connection,
        ?array $filters = []
    ): ?array {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;
        $queryParams = $this->buildQueryParams($filters);

        try {
            $response = $this->client->get("/c/{$companyId}/received_documents", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $connection->access_token,
                ],
                'query' => $queryParams,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('FIC get received documents failed', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id,
                'filters' => $filters,
            ]);
            return null;
        }
    }

    /**
     * Get clients from Fatture in Cloud.
     */
    public function getClients(
        FattureInCloudConnection $connection,
        ?array $filters = []
    ): ?array {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;
        $queryParams = $this->buildQueryParams($filters);

        try {
            $response = $this->client->get("/c/{$companyId}/entities/clients", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $connection->access_token,
                ],
                'query' => $queryParams,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('FIC get clients failed', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id,
                'filters' => $filters,
            ]);
            return null;
        }
    }

    /**
     * Cerca cliente esistente in FIC per Partita IVA
     */
    public function findClientByVatNumber(
        FattureInCloudConnection $connection,
        string $vatNumber
    ): ?array {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;

        try {
            // Cerca per vat_number usando il campo fieldset
            $response = $this->client->get("/c/{$companyId}/entities/clients", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $connection->access_token,
                ],
                'query' => [
                    'fieldset' => 'detailed',
                    'per_page' => 100, // Aumenta limite per trovare il cliente
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $clients = $data['data'] ?? [];

            // Cerca manualmente per P.IVA (FIC non supporta filtro per vat_number)
            foreach ($clients as $client) {
                if (isset($client['vat_number']) && $client['vat_number'] === $vatNumber) {
                    Log::info('[FattureInCloudService] Cliente trovato in FIC per P.IVA', [
                        'vat_number' => $vatNumber,
                        'fic_client_id' => $client['id'],
                        'name' => $client['name'] ?? null
                    ]);
                    return $client;
                }
            }

            Log::info('[FattureInCloudService] Nessun cliente trovato in FIC per P.IVA', [
                'vat_number' => $vatNumber
            ]);
            return null;
        } catch (GuzzleException $e) {
            Log::error('[FattureInCloudService] Errore ricerca cliente per P.IVA', [
                'error' => $e->getMessage(),
                'vat_number' => $vatNumber,
                'connection_id' => $connection->id,
            ]);
            return null;
        }
    }

    /**
     * Create a client in Fatture in Cloud.
     */
    public function createClient(
        FattureInCloudConnection $connection,
        array $clientData
    ): ?array {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;

        try {
            $response = $this->client->post("/c/{$companyId}/entities/clients", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $connection->access_token,
                ],
                'json' => ['data' => $clientData],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? null;
        } catch (GuzzleException $e) {
            // Cattura la risposta di errore dal server FIC
            $errorBody = null;
            $errorData = null;
            $statusCode = null;
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $errorBody = $response->getBody()->getContents();
                $errorData = json_decode($errorBody, true);
            }
            
            Log::error('FIC create client failed', [
                'error' => $e->getMessage(),
                'status_code' => $statusCode,
                'error_body' => $errorBody,
                'error_data' => $errorData,
                'connection_id' => $connection->id,
                'client_data' => $clientData,
            ]);
            return null;
        }
    }

    /**
     * Get suppliers from Fatture in Cloud.
     */
    public function getSuppliers(
        FattureInCloudConnection $connection,
        ?array $filters = []
    ): ?array {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;
        $queryParams = $this->buildQueryParams($filters);

        try {
            $response = $this->client->get("/c/{$companyId}/entities/suppliers", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $connection->access_token,
                ],
                'query' => $queryParams,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('FIC get suppliers failed', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id,
                'filters' => $filters,
            ]);
            return null;
        }
    }

    /**
     * Create webhook subscription
     */
    public function createWebhookSubscription(
        FattureInCloudConnection $connection,
        string $callbackUrl,
        array $eventTypes
    ): ?array {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;

        try {
            $response = $this->client->post(
                "/c/{$companyId}/subscriptions",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $connection->access_token,
                    ],
                    'json' => [
                        'data' => [
                            'sink' => $callbackUrl,
                            'types' => $eventTypes,
                        ],
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Webhook subscription created', [
                'subscription_id' => $data['data']['id'] ?? null,
                'callback_url' => $callbackUrl,
                'event_types' => $eventTypes,
            ]);

            return $data['data'] ?? null;
        } catch (GuzzleException $e) {
            Log::error('FIC create webhook subscription failed', [
                'error' => $e->getMessage(),
                'callback_url' => $callbackUrl,
            ]);
            return null;
        }
    }

    /**
     * List webhook subscriptions
     */
    public function listWebhookSubscriptions(
        FattureInCloudConnection $connection
    ): ?array {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;

        try {
            $response = $this->client->get(
                "/c/{$companyId}/subscriptions",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $connection->access_token,
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('FIC list webhook subscriptions failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Delete webhook subscription
     */
    public function deleteWebhookSubscription(
        FattureInCloudConnection $connection,
        string $subscriptionId
    ): bool {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;

        try {
            $this->client->delete(
                "/c/{$companyId}/subscriptions/{$subscriptionId}",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $connection->access_token,
                    ],
                ]
            );

            Log::info('Webhook subscription deleted', [
                'subscription_id' => $subscriptionId,
            ]);

            return true;
        } catch (GuzzleException $e) {
            Log::error('FIC delete webhook subscription failed', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
            ]);
            return false;
        }
    }

    /**
     * Ensure the connection has a valid token, refresh if needed.
     */
    private function ensureValidToken(FattureInCloudConnection $connection): void
    {
        if ($connection->needsRefresh()) {
            $this->refreshToken($connection);
        }
    }

    /**
     * Send invoice to SDI (Sistema di Interscambio) via Fatture in Cloud
     */
    public function sendInvoiceToSDI(
        FattureInCloudConnection $connection,
        int $ficDocumentId
    ): ?array {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;

        try {
            // Set e-invoice option to send to SDI
            $response = $this->client->post(
                "/c/{$companyId}/issued_documents/{$ficDocumentId}/e_invoice/send",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $connection->access_token,
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Invoice sent to SDI via FIC', [
                'fic_document_id' => $ficDocumentId,
                'response' => $data,
            ]);

            return $data['data'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('FIC send to SDI failed', [
                'error' => $e->getMessage(),
                'fic_document_id' => $ficDocumentId,
            ]);
            return null;
        }
    }

    /**
     * Get invoice status from Fatture in Cloud
     */
    public function getInvoiceStatus(
        FattureInCloudConnection $connection,
        int $ficDocumentId
    ): ?array {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;

        try {
            $response = $this->client->get(
                "/c/{$companyId}/issued_documents/{$ficDocumentId}",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $connection->access_token,
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data'] ?? null;
        } catch (GuzzleException $e) {
            Log::error('FIC get invoice status failed', [
                'error' => $e->getMessage(),
                'fic_document_id' => $ficDocumentId,
            ]);
            return null;
        }
    }

    /**
     * Change invoice status (e.g., from draft to issued)
     */
    public function changeInvoiceStatus(
        FattureInCloudConnection $connection,
        int $ficDocumentId,
        string $status
    ): ?array {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;

        try {
            $response = $this->client->put(
                "/c/{$companyId}/issued_documents/{$ficDocumentId}",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $connection->access_token,
                    ],
                    'json' => [
                        'data' => [
                            'status' => $status // 'draft', 'not_sent', 'sent', 'paid', etc.
                        ]
                    ]
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info('[FattureInCloudService] Invoice status changed', [
                'fic_document_id' => $ficDocumentId,
                'new_status' => $status
            ]);
            
            return $data['data'] ?? null;
        } catch (GuzzleException $e) {
            Log::error('FIC change invoice status failed', [
                'error' => $e->getMessage(),
                'fic_document_id' => $ficDocumentId,
                'status' => $status,
            ]);
            return null;
        }
    }

    /**
     * Download invoice PDF from Fatture in Cloud
     * Nota: Il PDF è disponibile solo dopo che la fattura è stata confermata/inviata in FIC
     */
    public function downloadInvoicePDF(
        FattureInCloudConnection $connection,
        int $ficDocumentId
    ): ?string {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;

        try {
            $response = $this->client->get(
                "/c/{$companyId}/issued_documents/{$ficDocumentId}/pdf",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $connection->access_token,
                    ],
                ]
            );

            // FIC returns base64 encoded PDF in JSON
            $data = json_decode($response->getBody()->getContents(), true);
            $pdfBase64 = $data['data'] ?? null;

            if ($pdfBase64) {
                return base64_decode($pdfBase64);
            }

            return null;
        } catch (GuzzleException $e) {
            $statusCode = $e->getCode();
            
            // 404 = fattura non ancora confermata/inviata in FIC (è ancora bozza)
            if ($statusCode == 404) {
                Log::warning('FIC PDF not available - invoice not confirmed yet', [
                    'fic_document_id' => $ficDocumentId,
                    'status_code' => $statusCode,
                ]);
                throw new \Exception('Il PDF non è ancora disponibile. Verifica la fattura in Fatture in Cloud prima di scaricare il PDF.');
            }
            
            Log::error('FIC download PDF failed', [
                'error' => $e->getMessage(),
                'fic_document_id' => $ficDocumentId,
                'status_code' => $statusCode,
            ]);
            return null;
        }
    }

    /**
     * Get e-invoice XML from Fatture in Cloud
     */
    public function getInvoiceXML(
        FattureInCloudConnection $connection,
        int $ficDocumentId
    ): ?string {
        $this->ensureValidToken($connection);

        $companyId = $connection->fic_company_id;

        try {
            $response = $this->client->get(
                "/c/{$companyId}/issued_documents/{$ficDocumentId}/xml",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $connection->access_token,
                    ],
                ]
            );

            return $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            Log::error('FIC get XML failed', [
                'error' => $e->getMessage(),
                'fic_document_id' => $ficDocumentId,
            ]);
            return null;
        }
    }

    /**
     * Create immediate invoice (fattura immediata)
     */
    public function createImmediateInvoice(
        FattureInCloudConnection $connection,
        array $invoiceData
    ): ?array {
        $formattedData = [
            'type' => 'invoice',
            'numeration' => $invoiceData['numeration'] ?? '/FE',
            'date' => $invoiceData['date'],
            'number' => $invoiceData['number'] ?? null,
            'entity' => [
                'id' => $invoiceData['client_fic_id'] ?? null,
                'name' => $invoiceData['client_name'],
                'vat_number' => $invoiceData['client_vat'] ?? null,
                'tax_code' => $invoiceData['client_tax_code'] ?? null,
                'address_street' => $invoiceData['client_address'] ?? null,
                'address_city' => $invoiceData['client_city'] ?? null,
                'address_province' => $invoiceData['client_province'] ?? null,
                'address_postal_code' => $invoiceData['client_zip'] ?? null,
                'certified_email' => $invoiceData['client_pec'] ?? null,
                'ei_code' => $invoiceData['client_sdi_code'] ?? null,
            ],
            'items_list' => $invoiceData['items'],
            'payments_list' => [
                [
                    'payment_terms' => [
                        'type' => 'standard',
                        'days' => 0,
                    ],
                    'amount' => $invoiceData['total'],
                    'status' => 'paid',
                ],
            ],
            'payment_method' => [
                'name' => $invoiceData['payment_method'] ?? 'Stripe',
            ],
            'e_invoice' => true,
        ];

        return $this->createIssuedInvoice($connection, $formattedData);
    }

    /**
     * Create deferred invoice (fattura differita) with DDT references
     */
    public function createDeferredInvoice(
        FattureInCloudConnection $connection,
        array $invoiceData,
        array $ddtReferences = []
    ): ?array {
        $formattedData = [
            'type' => 'invoice',
            'numeration' => $invoiceData['numeration'] ?? '/FE',
            'date' => $invoiceData['date'],
            'number' => $invoiceData['number'] ?? null,
            'entity' => [
                'id' => $invoiceData['client_fic_id'] ?? null,
                'name' => $invoiceData['client_name'],
                'vat_number' => $invoiceData['client_vat'] ?? null,
                'tax_code' => $invoiceData['client_tax_code'] ?? null,
                'address_street' => $invoiceData['client_address'] ?? null,
                'address_city' => $invoiceData['client_city'] ?? null,
                'address_province' => $invoiceData['client_province'] ?? null,
                'address_postal_code' => $invoiceData['client_zip'] ?? null,
                'certified_email' => $invoiceData['client_pec'] ?? null,
                'ei_code' => $invoiceData['client_sdi_code'] ?? null,
            ],
            'items_list' => $invoiceData['items'],
            'payments_list' => [
                [
                    'payment_terms' => [
                        'type' => 'standard',
                        'days' => 30,
                    ],
                    'amount' => $invoiceData['total'],
                    'status' => 'not_paid',
                ],
            ],
            'payment_method' => [
                'name' => $invoiceData['payment_method'] ?? 'Bonifico',
            ],
            'e_invoice' => true,
        ];

        // Add DDT references if present
        if (!empty($ddtReferences)) {
            $formattedData['ddt_reference'] = $ddtReferences;
        }

        return $this->createIssuedInvoice($connection, $formattedData);
    }

    /**
     * Create credit note (nota di credito)
     */
    public function createCreditNote(
        FattureInCloudConnection $connection,
        array $invoiceData,
        ?int $originalInvoiceFicId = null
    ): ?array {
        $formattedData = [
            'type' => 'credit_note',
            'numeration' => $invoiceData['numeration'] ?? '/FE',
            'date' => $invoiceData['date'],
            'number' => $invoiceData['number'] ?? null,
            'entity' => [
                'id' => $invoiceData['client_fic_id'] ?? null,
                'name' => $invoiceData['client_name'],
                'vat_number' => $invoiceData['client_vat'] ?? null,
                'tax_code' => $invoiceData['client_tax_code'] ?? null,
            ],
            'items_list' => $invoiceData['items'],
            'e_invoice' => true,
        ];

        // Reference to original invoice if provided
        if ($originalInvoiceFicId) {
            $formattedData['related_document'] = [
                'id' => $originalInvoiceFicId,
            ];
        }

        return $this->createIssuedInvoice($connection, $formattedData);
    }

    /**
     * Build query parameters for API requests.
     */
    private function buildQueryParams(array $filters): array
    {
        $params = [];

        // Date filters
        if (isset($filters['date_from'])) {
            $params['date_from'] = $filters['date_from'];
        }
        if (isset($filters['date_to'])) {
            $params['date_to'] = $filters['date_to'];
        }

        // Type filter (for issued documents)
        if (isset($filters['type'])) {
            $params['type'] = $filters['type'];
        }

        // Pagination
        if (isset($filters['page'])) {
            $params['page'] = $filters['page'];
        }
        if (isset($filters['per_page'])) {
            $params['per_page'] = $filters['per_page'];
        }

        // Search query
        if (isset($filters['q'])) {
            $params['q'] = $filters['q'];
        }

        return $params;
    }

    /**
     * Ottieni aliquote IVA disponibili da FIC
     * @return array Array con key = percentuale IVA, value = ID FIC
     */
    private function getVatTypesMap(FattureInCloudConnection $connection): array
    {
        static $cache = null;
        
        // Cache in memoria per ridurre chiamate API
        if ($cache !== null) {
            return $cache;
        }
        
        $this->ensureValidToken($connection);
        
        $companyId = $connection->fic_company_id;
        
        try {
            $response = $this->client->get("/c/{$companyId}/info/vat_types", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $connection->access_token,
                ],
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $vatTypes = $data['data'] ?? [];
            
            // Crea mappa percentuale => id
            $map = [];
            foreach ($vatTypes as $vat) {
                if (isset($vat['value']) && isset($vat['id'])) {
                    $map[(float)$vat['value']] = (int)$vat['id'];
                }
            }
            
            Log::info('[FattureInCloudService] Aliquote IVA caricate da FIC', [
                'count' => count($map),
                'rates' => array_keys($map)
            ]);
            
            $cache = $map;
            return $map;
            
        } catch (GuzzleException $e) {
            Log::error('[FattureInCloudService] Errore caricamento aliquote IVA', [
                'error' => $e->getMessage()
            ]);
            
            // Fallback con aliquote standard italiane (usate se API fallisce)
            // Questi ID sono stabili nel sistema FIC
            return [
                0.0 => 0,    // IVA esente
                4.0 => 3,    // 4% (libri, giornali)
                5.0 => 6,    // 5% (ridotta speciale)
                10.0 => 2,   // 10% (ridotta)
                22.0 => 1,   // 22% (ordinaria)
            ];
        }
    }
    
    /**
     * Trova ID aliquota IVA per una percentuale
     */
    private function getVatIdForRate(FattureInCloudConnection $connection, float $rate): ?int
    {
        $map = $this->getVatTypesMap($connection);
        
        if (isset($map[$rate])) {
            return $map[$rate];
        }
        
        // Se non trovata, usa 22% (ordinaria) come default
        Log::warning('[FattureInCloudService] Aliquota IVA non trovata, uso 22% default', [
            'requested_rate' => $rate,
            'available_rates' => array_keys($map)
        ]);
        
        return $map[22.0] ?? 1; // ID 1 = 22% ordinaria
    }

    /**
     * Crea fattura in FIC da una fattura Laravel esistente
     */
    public function createInvoiceFromLocal(
        FattureInCloudConnection $connection,
        \App\Models\Invoice $invoice,
        array $additionalData = []
    ): ?array {
        $this->ensureValidToken($connection);

        try {
            $client = $invoice->client;
            
            // 1. Crea o trova cliente in FIC
            if (!$client->fic_client_id) {
                Log::info('[FattureInCloudService] Creazione cliente in FIC', [
                    'client_id' => $client->id,
                    'ragione_sociale' => $client->ragione_sociale
                ]);
                
                // Validazione completa dati cliente prima di inviare a FIC
                try {
                    $this->validateClientDataForFIC($client);
                } catch (\Exception $e) {
                    throw new \Exception("Impossibile creare cliente in Fatture in Cloud:\n{$e->getMessage()}\n\nCorreggi i dati del cliente e riprova.");
                }
                
                $clientData = [
                    'name' => $client->ragione_sociale,
                    'country' => 'Italia',
                ];
                
                // Aggiungi P.IVA e Codice Fiscale solo se presenti
                if (!empty($client->piva)) {
                    $clientData['vat_number'] = $client->piva;
                }
                
                // Per ditte individuali usa il codice fiscale del titolare
                if ($client->tipo_societa === 'ditta_individuale' && !empty($client->codice_fiscale_titolare)) {
                    $clientData['tax_code'] = $client->codice_fiscale_titolare;
                } elseif (!empty($client->codice_fiscale)) {
                    $clientData['tax_code'] = $client->codice_fiscale;
                }
                
                // Aggiungi solo campi non vuoti (FIC è molto rigido con la validazione)
                if (!empty($client->indirizzo)) {
                    $clientData['address_street'] = $client->indirizzo;
                }
                if (!empty($client->citta)) {
                    $clientData['address_city'] = $client->citta;
                }
                if (!empty($client->provincia)) {
                    $clientData['address_province'] = $client->provincia;
                }
                if (!empty($client->cap)) {
                    $clientData['address_postal_code'] = $client->cap;
                }
                if (!empty($client->email)) {
                    $clientData['email'] = $client->email;
                }
                if (!empty($client->pec)) {
                    $clientData['certified_email'] = $client->pec;
                }
                
                Log::info('[FattureInCloudService] Dati cliente per FIC', [
                    'client_id' => $client->id,
                    'ragione_sociale' => $client->ragione_sociale,
                    'indirizzo' => $client->indirizzo,
                    'citta' => $client->citta,
                    'provincia' => $client->provincia,
                    'cap' => $client->cap,
                    'client_data_to_fic' => $clientData
                ]);
                
                $ficClient = $this->createClient($connection, $clientData);
                
                if (!$ficClient || !isset($ficClient['id'])) {
                    // Potrebbe essere un 409 Conflict - cliente già esistente
                    // Prova a cercare il cliente esistente per P.IVA
                    if (!empty($client->piva)) {
                        Log::info('[FattureInCloudService] Creazione fallita, ricerca cliente esistente per P.IVA', [
                            'client_id' => $client->id,
                            'piva' => $client->piva
                        ]);
                        
                        $existingClient = $this->findClientByVatNumber($connection, $client->piva);
                        
                        if ($existingClient && isset($existingClient['id'])) {
                            // Trovato! Collega il cliente esistente
                            $client->fic_client_id = $existingClient['id'];
                            $client->save();
                            
                            Log::info('[FattureInCloudService] Cliente esistente collegato', [
                                'client_id' => $client->id,
                                'fic_client_id' => $existingClient['id'],
                                'fic_client_name' => $existingClient['name'] ?? null
                            ]);
                            
                            // Continua con questo cliente
                        } else {
                            // Non trovato nemmeno cercando - errore reale
                            Log::error('[FattureInCloudService] Impossibile creare o trovare cliente in FIC', [
                                'client_id' => $client->id,
                                'client_data' => $clientData,
                                'fic_response' => $ficClient
                            ]);
                            throw new \Exception('Impossibile creare il cliente in Fatture in Cloud. Verifica i dati del cliente (P.IVA, Codice Fiscale, Indirizzo).');
                        }
                    } else {
                        // Nessuna P.IVA per cercare - errore
                        Log::error('[FattureInCloudService] Impossibile creare cliente in FIC e nessuna P.IVA per ricerca', [
                            'client_id' => $client->id,
                            'client_data' => $clientData,
                            'fic_response' => $ficClient
                        ]);
                        throw new \Exception('Impossibile creare il cliente in Fatture in Cloud. Verifica i dati del cliente (P.IVA, Codice Fiscale, Indirizzo).');
                    }
                } else {
                    // Creazione riuscita
                    $client->fic_client_id = $ficClient['id'];
                    $client->save();
                    
                    Log::info('[FattureInCloudService] Cliente creato in FIC', [
                        'client_id' => $client->id,
                        'fic_client_id' => $ficClient['id']
                    ]);
                }
            }
            
            // 2. Prepara items fattura per FIC
            $items = [];
            foreach ($invoice->items as $item) {
                $ivaRate = (float) ($item->iva_percentuale ?? 22);
                $vatId = $this->getVatIdForRate($connection, $ivaRate);
                
                Log::info('[FattureInCloudService] Item fattura', [
                    'descrizione' => $item->descrizione,
                    'iva_percentuale' => $ivaRate,
                    'fic_vat_id' => $vatId,
                    'prezzo_unitario' => $item->prezzo_unitario,
                    'quantita' => $item->quantita,
                ]);
                
                $items[] = [
                    'product_id' => null,
                    'code' => '',
                    'name' => $item->descrizione ?? 'Servizio',
                    'measure' => '',
                    'net_price' => (float) $item->prezzo_unitario,
                    'category' => '',
                    'qty' => (float) ($item->quantita ?? 1),
                    'vat' => [
                        'id' => $vatId, // ID corretto da FIC API
                        'value' => $ivaRate,
                        'description' => ''
                    ],
                    'not_taxable' => false,
                    'apply_withholding_taxes' => false,
                    'discount' => 0,
                    'discount_highlight' => false,
                    'in_dn' => false,
                    'stock' => false,
                ];
            }
            
            // 3. Crea dati fattura per FIC
            $entityData = [
                'id' => $client->fic_client_id,
                'name' => $client->ragione_sociale,
                'vat_number' => $client->piva,
                // Per ditte individuali usa il codice fiscale del titolare, per società usa il CF dell'azienda
                'tax_code' => ($client->tipo_societa === 'ditta_individuale' && !empty($client->codice_fiscale_titolare)) 
                    ? $client->codice_fiscale_titolare 
                    : ($client->codice_fiscale ?: ''), // SEMPRE stringa, mai null
                'country' => 'Italia',
            ];
            
            // Aggiungi solo campi indirizzo non vuoti
            if (!empty($client->indirizzo)) {
                $entityData['address_street'] = $client->indirizzo;
            }
            if (!empty($client->citta)) {
                $entityData['address_city'] = $client->citta;
            }
            if (!empty($client->provincia)) {
                $entityData['address_province'] = $client->provincia;
            }
            if (!empty($client->cap)) {
                $entityData['address_postal_code'] = $client->cap;
            }
            if (!empty($client->pec)) {
                $entityData['certified_email'] = $client->pec;
            }
            
            Log::info('[FattureInCloudService] Entity data per fattura', [
                'client_id' => $client->id,
                'fic_client_id' => $client->fic_client_id,
                'indirizzo_db' => $client->indirizzo,
                'citta_db' => $client->citta,
                'provincia_db' => $client->provincia,
                'cap_db' => $client->cap,
                'entity_address_street' => $entityData['address_street'] ?? 'VUOTO',
                'entity_address_city' => $entityData['address_city'] ?? 'VUOTO',
                'entity_address_province' => $entityData['address_province'] ?? 'VUOTO',
                'entity_address_postal_code' => $entityData['address_postal_code'] ?? 'VUOTO',
            ]);
            
            // Per fatture elettroniche, aggiungi campi SDI richiesti
            $isEInvoice = $additionalData['e_invoice'] ?? false;
            if ($isEInvoice) {
                $entityData['e_invoice'] = true;
                
                // Codice Destinatario (obbligatorio per e-invoice B2B) o PEC
                if (!empty($client->sdi_code)) {
                    $entityData['ei_code'] = $client->sdi_code;
                } elseif (!empty($client->pec)) {
                    // Se non c'è codice SDI, usa 0000000 e la PEC (modalità PEC)
                    $entityData['ei_code'] = '0000000';
                } else {
                    // Fallback per consumatori finali (B2C)
                    $entityData['ei_code'] = '0000000';
                }
            } elseif (!empty($client->sdi_code)) {
                $entityData['ei_code'] = $client->sdi_code;
            }
            
            // Calcola totali dagli items (FIC ricalcolerà comunque, ma deve matchare)
            $calculatedNet = 0;
            $calculatedVat = 0;
            foreach ($items as $item) {
                $itemNet = $item['net_price'] * $item['qty'];
                $itemVat = $itemNet * ($item['vat']['value'] / 100);
                $calculatedNet += $itemNet;
                $calculatedVat += $itemVat;
            }
            $calculatedGross = $calculatedNet + $calculatedVat;
            
            Log::info('[FattureInCloudService] Totali calcolati dagli items', [
                'net' => round($calculatedNet, 2),
                'vat' => round($calculatedVat, 2),
                'gross' => round($calculatedGross, 2),
                'items_count' => count($items)
            ]);
            
            // Determina numerazione corretta per FIC
            // IMPORTANTE: Il numeration deve corrispondere ad un sezionale esistente in FIC!
            // Se in FIC non esiste il sezionale /A, la fattura apparirà solo col numero.
            // Verifica in FIC: Impostazioni → Sezionali → crea sezionale "A" o "Oppla"
            // Sia ordinarie che differite usano /A
            $numeration = '/A'; // Default - VERIFICA che /A esista in FIC!
            if ($invoice->type !== 'attiva') {
                $numeration = '/P'; // Passiva - VERIFICA che /P esista in FIC!
            }
            
            Log::info('[FattureInCloudService] Numero fattura per FIC', [
                'invoice_id' => $invoice->id,
                'numero_fattura_locale' => $invoice->numero_fattura,
                'numero_progressivo' => $invoice->numero_progressivo,
                'numeration' => $numeration,
                'fic_number' => (int) $invoice->numero_progressivo,
                'fic_numeration' => $numeration,
                'NOTA' => 'Se in FIC appare solo il numero senza suffisso, il sezionale ' . $numeration . ' non esiste in FIC!'
            ]);
            
            $invoiceData = array_merge([
                'type' => 'invoice',
                'entity' => $entityData,
                'date' => $invoice->data_emissione->format('Y-m-d'),
                'number' => (int) $invoice->numero_progressivo,
                'numeration' => $numeration,
                'currency' => [
                    'id' => 'EUR',
                    'exchange_rate' => '1.00000',
                    'symbol' => '€'
                ],
                'language' => [
                    'code' => 'it',
                    'name' => 'Italiano'
                ],
                'items_list' => $items,
                'payments_list' => [
                    [
                        'amount' => round($calculatedGross, 2),
                        'due_date' => $invoice->data_scadenza ? $invoice->data_scadenza->format('Y-m-d') : $invoice->data_emissione->addDays(30)->format('Y-m-d'),
                        'payment_terms' => [
                            'days' => 30,
                            'type' => 'standard'
                        ],
                    ]
                ],
                'amount_net' => round($calculatedNet, 2),
                'amount_vat' => round($calculatedVat, 2),
                'amount_gross' => round($calculatedGross, 2),
            ], $additionalData);
            
            // 4b. Se è una e-invoice, aggiungi ei_data con payment_method obbligatorio
            if ($isEInvoice) {
                // FIC richiede ei_data quando e_invoice = true
                $invoiceData['ei_data'] = [
                    'payment_method' => $additionalData['ei_payment_method'] ?? 'MP05', // MP05 = Bonifico bancario
                ];
                
                // Aggiungi altri campi ei_data se specificati in additionalData
                if (isset($additionalData['ei_vat_kind'])) {
                    $invoiceData['ei_data']['vat_kind'] = $additionalData['ei_vat_kind']; // I=immediata, D=differita, S=split payment
                }
                if (isset($additionalData['ei_bank_iban'])) {
                    $invoiceData['ei_data']['bank_iban'] = $additionalData['ei_bank_iban'];
                }
                if (isset($additionalData['ei_bank_name'])) {
                    $invoiceData['ei_data']['bank_name'] = $additionalData['ei_bank_name'];
                }
                if (isset($additionalData['ei_bank_beneficiary'])) {
                    $invoiceData['ei_data']['bank_beneficiary'] = $additionalData['ei_bank_beneficiary'];
                }
            }
            
            // 4c. Verifica che il cliente FIC esista
            if (empty($invoiceData['entity']['id'])) {
                throw new \Exception('Cliente non collegato a Fatture in Cloud. Impossibile creare la fattura.');
            }
            
            // 4d. Crea fattura in FIC
            Log::info('[FattureInCloudService] Invio fattura a FIC API', [
                'invoice_id' => $invoice->id,
                'fic_client_id' => $invoiceData['entity']['id'],
                'numero_fattura' => $invoiceData['number'],
                'items_count' => count($invoiceData['items_list']),
                'amount' => $invoiceData['amount_gross'],
            ]);
            
            $result = $this->createIssuedInvoice($connection, $invoiceData);
            
            if (!$result) {
                throw new \Exception('Errore durante la creazione della fattura in Fatture in Cloud. Verifica i log per dettagli.');
            }
            
            Log::info('[FattureInCloudService] Fattura creata in FIC', [
                'local_invoice_id' => $invoice->id,
                'fic_document_id' => $result['id'] ?? null
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('[FattureInCloudService] Errore creazione fattura da locale', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Ottiene l'ultimo numero di fattura da Fatture in Cloud per un anno specifico
     * 
     * @param int $year Anno da controllare (es. 2026)
     * @return int Ultimo progressivo trovato (es. se ultima è 15/A ritorna 15, se nessuna ritorna 0)
     */
    public function getLastInvoiceNumber(int $year): int
    {
        try {
            $connection = FattureInCloudConnection::where('is_active', true)->first();
            
            if (!$connection) {
                Log::warning('[FattureInCloudService] Nessuna connessione FIC attiva per sincronizzazione progressivo');
                return 0;
            }

            // Query FIC API per fatture dell'anno specifico
            $response = $this->client->get("c/{$connection->fic_company_id}/issued_documents", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $connection->access_token,
                ],
                'query' => [
                    'type' => 'invoice',
                    'year' => $year,
                    'page' => 1,
                    'per_page' => 1,
                    'sort' => '-number', // Ordina per numero decrescente
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (empty($data['data']) || count($data['data']) === 0) {
                Log::info('[FattureInCloudService] Nessuna fattura trovata su FIC per anno', ['year' => $year]);
                return 0;
            }

            // Estrai numero dalla prima fattura (es. "15/A" → 15)
            $lastNumber = $data['data'][0]['number'] ?? null;
            
            if (!$lastNumber) {
                return 0;
            }

            // Estrai solo la parte numerica (es. "15/A" → 15, "123" → 123)
            if (preg_match('/^(\d+)/', $lastNumber, $matches)) {
                $progressivo = (int)$matches[1];
                
                Log::info('[FattureInCloudService] Ultimo numero fattura su FIC', [
                    'year' => $year,
                    'last_number' => $lastNumber,
                    'progressivo' => $progressivo
                ]);
                
                return $progressivo;
            }

            return 0;

        } catch (\Exception $e) {
            Log::error('[FattureInCloudService] Errore recupero ultimo numero fattura da FIC', [
                'year' => $year,
                'error' => $e->getMessage()
            ]);
            return 0; // In caso di errore, ritorna 0 per usare il sistema locale
        }
    }

    /**
     * Sync passive invoices (received documents) from Fatture in Cloud to local database.
     * Creates or updates SupplierInvoice records and auto-creates Suppliers if needed.
     */
    public function syncPassiveInvoices(int $year): array
    {
        $result = [
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'suppliers_created' => 0,
            'errors' => [],
        ];

        try {
            $connection = FattureInCloudConnection::where('is_active', true)->first();
            
            if (!$connection) {
                $result['errors'][] = 'Nessuna connessione FIC attiva';
                return $result;
            }

            $this->ensureValidToken($connection);
            
            $page = 1;
            $perPage = 50;
            $hasMore = true;

            while ($hasMore) {
                $response = $this->client->get("c/{$connection->fic_company_id}/received_documents", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $connection->access_token,
                    ],
                    'query' => [
                        'type' => 'expense',
                        'year' => $year,
                        'page' => $page,
                        'per_page' => $perPage,
                        'fieldset' => 'detailed',
                    ]
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                $documents = $data['data'] ?? [];

                if (empty($documents)) {
                    $hasMore = false;
                    break;
                }

                foreach ($documents as $doc) {
                    try {
                        $syncResult = $this->syncSinglePassiveInvoice($doc);
                        $result['synced']++;
                        
                        if ($syncResult['action'] === 'created') {
                            $result['created']++;
                        } elseif ($syncResult['action'] === 'updated') {
                            $result['updated']++;
                        }
                        
                        if ($syncResult['supplier_created']) {
                            $result['suppliers_created']++;
                        }
                    } catch (\Exception $e) {
                        $result['errors'][] = "Documento {$doc['id']}: " . $e->getMessage();
                        Log::error('[FattureInCloudService] Errore sync fattura passiva', [
                            'doc_id' => $doc['id'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Check if there are more pages
                $totalPages = $data['last_page'] ?? 1;
                $hasMore = $page < $totalPages;
                $page++;
            }

            Log::info('[FattureInCloudService] Sync fatture passive completato', $result);
            return $result;

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
            Log::error('[FattureInCloudService] Errore sync fatture passive', ['error' => $e->getMessage()]);
            return $result;
        }
    }

    /**
     * Sync a single passive invoice from FIC data
     */
    private function syncSinglePassiveInvoice(array $doc): array
    {
        $supplierCreated = false;
        
        // Extract supplier data from document
        $entityData = $doc['entity'] ?? [];
        
        // Find or create supplier
        $supplier = null;
        if (!empty($entityData['vat_number'])) {
            $supplier = \App\Models\Supplier::where('piva', $entityData['vat_number'])->first();
        }
        
        if (!$supplier && !empty($entityData['tax_code'])) {
            $supplier = \App\Models\Supplier::where('codice_fiscale', $entityData['tax_code'])->first();
        }
        
        if (!$supplier && !empty($entityData['name'])) {
            // Create new supplier
            $supplier = \App\Models\Supplier::create([
                'ragione_sociale' => $entityData['name'] ?? 'Fornitore FIC ' . ($doc['id'] ?? ''),
                'piva' => $entityData['vat_number'] ?? null,
                'codice_fiscale' => $entityData['tax_code'] ?? null,
                'email' => $entityData['email'] ?? null,
                'phone' => $entityData['phone'] ?? null,
                'pec' => $entityData['certified_email'] ?? null,
                'indirizzo' => $entityData['address_street'] ?? null,
                'citta' => $entityData['address_city'] ?? null,
                'provincia' => $entityData['address_province'] ?? null,
                'cap' => $entityData['address_postal_code'] ?? null,
                'nazione' => $entityData['country'] ?? 'IT',
                'is_active' => true,
            ]);
            $supplierCreated = true;
        }

        if (!$supplier) {
            throw new \Exception('Impossibile determinare/creare fornitore');
        }

        // Find existing invoice by FIC ID or invoice number + supplier
        $existingInvoice = \App\Models\SupplierInvoice::where('fic_id', $doc['id'])->first();
        
        if (!$existingInvoice) {
            $existingInvoice = \App\Models\SupplierInvoice::where([
                'supplier_id' => $supplier->id,
                'invoice_number' => $doc['number'] ?? null,
            ])->whereYear('invoice_date', Carbon::parse($doc['date'])->year)->first();
        }

        // Prepare invoice data
        $invoiceData = [
            'supplier_id' => $supplier->id,
            'invoice_number' => $doc['number'] ?? 'FIC-' . $doc['id'],
            'invoice_date' => $doc['date'] ?? now(),
            'due_date' => $doc['next_due_date'] ?? null,
            'amount' => $doc['amount_net'] ?? 0,
            'vat_amount' => $doc['amount_vat'] ?? 0,
            'total_amount' => $doc['amount_gross'] ?? ($doc['amount_net'] + $doc['amount_vat']),
            'fic_id' => $doc['id'],
            'payment_status' => $this->mapFicPaymentStatus($doc['payment_status'] ?? null),
        ];

        if (!empty($doc['payments_list']) && is_array($doc['payments_list'])) {
            $lastPayment = collect($doc['payments_list'])->sortByDesc('date')->first();
            if ($lastPayment && ($invoiceData['payment_status'] === 'paid')) {
                $invoiceData['paid_at'] = $lastPayment['date'] ?? null;
            }
        }

        if ($existingInvoice) {
            $existingInvoice->update($invoiceData);
            return ['action' => 'updated', 'supplier_created' => $supplierCreated];
        } else {
            \App\Models\SupplierInvoice::create($invoiceData);
            return ['action' => 'created', 'supplier_created' => $supplierCreated];
        }
    }

    /**
     * Map FIC payment status to local status
     */
    private function mapFicPaymentStatus(?string $ficStatus): string
    {
        return match ($ficStatus) {
            'paid' => 'paid',
            'not_paid' => 'pending',
            'partial' => 'partial',
            default => 'pending',
        };
    }
}
