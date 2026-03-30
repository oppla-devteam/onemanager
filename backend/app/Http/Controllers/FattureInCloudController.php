<?php

namespace App\Http\Controllers;

use App\Models\FattureInCloudConnection;
use App\Services\FattureInCloudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FattureInCloudController extends Controller
{
    private FattureInCloudService $ficService;

    public function __construct(FattureInCloudService $ficService)
    {
        $this->ficService = $ficService;
    }

    /**
     * Initiate OAuth authorization flow.
     */
    public function authorize(Request $request)
    {
        try {
            // Autenticazione: token può venire da query param o header
            if ($request->has('token') && !Auth::check()) {
                $token = $request->get('token');
                $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
                if ($user) {
                    Auth::login($user);
                    \Log::info('FIC OAuth: User authenticated via token', ['user_id' => $user->id]);
                }
            }
            
            if (!Auth::check()) {
                \Log::warning('FIC OAuth: User not authenticated');
                $frontendUrl = config('app.frontend_url', 'https://pedro.oppla.club');
                return redirect($frontendUrl . '/invoices?fic_error=not_authenticated');
            }
            
            $userId = Auth::id();
            \Log::info('FIC OAuth: User authenticated', ['user_id' => $userId]);
            
            // Create or update pending connection with state
            $state = Str::random(40);
            
            // Delete any existing pending connections for this user
            FattureInCloudConnection::where('user_id', $userId)
                ->whereNotNull('pending_oauth_state')
                ->delete();
            
            // Create new pending connection
            $pendingConnection = FattureInCloudConnection::create([
                'user_id' => $userId,
                'pending_oauth_state' => $state,
                'oauth_state_expires_at' => now()->addMinutes(10),
                'is_active' => false,
            ]);

            $authUrl = $this->ficService->getAuthorizationUrl($state);
            
            \Log::info('FIC OAuth: Created pending connection and redirecting', [
                'url' => $authUrl,
                'state' => $state,
                'connection_id' => $pendingConnection->id
            ]);

            // Redirect via JavaScript per massima compatibilità
            return response("
                <script>
                    console.log('Redirecting to Fatture in Cloud...');
                    window.location.replace('{$authUrl}');
                </script>
                <noscript>
                    <meta http-equiv='refresh' content='0;url={$authUrl}'>
                    <p>Redirecting to Fatture in Cloud... <a href='{$authUrl}'>Click here</a> if not redirected.</p>
                </noscript>
            ", 200)->header('Content-Type', 'text/html');
        } catch (\Exception $e) {
            \Log::error('FIC OAuth authorize error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $frontendUrl = config('app.frontend_url', 'https://pedro.oppla.club');
            return redirect($frontendUrl . '/invoices?fic_error=authorization_failed');
        }
    }

    /**
     * Handle OAuth callback.
     */
    public function callback(Request $request)
    {
        try {
        // CRITICAL DEBUG: Log everything that arrives
        \Log::channel('single')->info('FIC OAuth callback received', [
            'all_params' => $request->all(),
            'state' => $request->state,
            'has_code' => $request->has('code'),
            'code_preview' => $request->has('code') ? substr($request->code, 0, 10) . '...' : null,
            'error' => $request->error,
            'error_description' => $request->error_description,
        ]);

        // DEBUG FALLBACK: If no code, show what we got
        if (!$request->has('code')) {
            \Log::error('FIC OAuth callback: NO CODE RECEIVED', $request->all());
            // Uncomment next line for development debugging only:
            // dd('OAuth Callback Debug', $request->all());
        }

        $frontendUrl = config('app.frontend_url', 'https://pedro.oppla.club');

        if (!$request->has('state')) {
            \Log::error('FIC OAuth callback: missing state parameter', $request->all());
            return redirect($frontendUrl . '/invoices?fic_error=missing_state');
        }

        // Find pending connection by state
        \Log::info('FIC OAuth: Searching for pending connection', ['state' => $request->state]);
        
        $pendingConnection = FattureInCloudConnection::where('pending_oauth_state', $request->state)
            ->where('oauth_state_expires_at', '>', now())
            ->first();

        if (!$pendingConnection) {
            // DEBUG: Check if ANY pending connections exist
            $allPending = FattureInCloudConnection::whereNotNull('pending_oauth_state')->get();
            \Log::error('FIC OAuth callback: invalid or expired state', [
                'state' => $request->state,
                'all_pending_count' => $allPending->count(),
                'all_pending_states' => $allPending->pluck('pending_oauth_state')->toArray(),
                'table_columns' => \Schema::getColumnListing('fatture_in_cloud_connections'),
            ]);
            return redirect($frontendUrl . '/invoices?fic_error=invalid_state');
        }

        $userId = $pendingConnection->user_id;

        if (!$request->has('code')) {
            \Log::error('FIC OAuth callback: missing code parameter');
            return redirect($frontendUrl . '/invoices?fic_error=no_code');
        }

        // Exchange code for token
        \Log::info('FIC OAuth: Exchanging code for token', ['code_length' => strlen($request->code)]);
        $tokenData = $this->ficService->exchangeCodeForToken($request->code);

        if (!$tokenData) {
            \Log::error('FIC OAuth: Token exchange returned null');
            $frontendUrl = config('app.frontend_url', 'https://pedro.oppla.club');
            return redirect($frontendUrl . '/invoices?fic_error=token_exchange_failed');
        }
        
        \Log::info('FIC OAuth: Token received successfully', [
            'expires_at' => $tokenData['token_expires_at'] ?? 'N/A',
            'has_refresh' => isset($tokenData['refresh_token']),
        ]);

        // Get user companies to retrieve company ID
        $tempConnection = new FattureInCloudConnection([
            'access_token' => $tokenData['access_token'],
            'token_expires_at' => $tokenData['token_expires_at'],
        ]);

        \Log::info('FIC OAuth: Fetching user companies');
        $companies = $this->ficService->getUserCompanies($tempConnection);

        // DEBUG: Log raw companies response
        \Log::info('FIC OAuth: Raw companies response', [
            'companies_type' => gettype($companies),
            'companies_dump' => json_encode($companies),
            'is_array' => is_array($companies),
            'count' => is_countable($companies) ? count($companies) : 'not countable',
        ]);

        if (empty($companies) || !is_array($companies)) {
            \Log::error('FIC OAuth: No companies found for user or invalid response', [
                'companies' => $companies,
            ]);
            $frontendUrl = config('app.frontend_url', 'https://pedro.oppla.club');
            return redirect($frontendUrl . '/invoices?fic_error=no_companies');
        }
        
        // Convert to array if needed and ensure it's an indexed array
        $companiesArray = array_values($companies);
        
        if (empty($companiesArray)) {
            \Log::error('FIC OAuth: Companies array is empty', [
                'companies_array' => $companiesArray,
            ]);
            $frontendUrl = config('app.frontend_url', 'https://pedro.oppla.club');
            return redirect($frontendUrl . '/invoices?fic_error=invalid_companies_format');
        }
        
        // Use first company (or let user select if multiple)
        $company = $companiesArray[0];
        
        // Fix: Se $company è ancora un array wrapper, estrai il primo elemento
        if (is_array($company) && isset($company[0]) && is_array($company[0])) {
            \Log::info('FIC OAuth: Company was wrapped in array, extracting first element');
            $company = $company[0];
        }
        
        // Ensure company is an array (not object or other type)
        if (!is_array($company)) {
            \Log::error('FIC OAuth: Company is not an array after extraction', [
                'company_type' => gettype($company),
                'company_value' => $company,
            ]);
            $frontendUrl = config('app.frontend_url', 'https://pedro.oppla.club');
            return redirect($frontendUrl . '/invoices?fic_error=invalid_company_structure');
        }
        
        // DEBUG: Log entire company structure to understand API response
        \Log::info('FIC OAuth: Full company structure', [
            'company_type' => gettype($company),
            'company_keys' => is_array($company) ? array_keys($company) : 'not an array',
            'company_data' => $company,
            'has_id_field' => is_array($company) && array_key_exists('id', $company),
            'has_name_field' => is_array($company) && array_key_exists('name', $company),
        ]);
        
        // Use array_key_exists to avoid PHP warnings
        // Try all possible key variations from FIC API
        $companyId = null;
        foreach (['id', 'company_id', 'companyId', 'ID'] as $key) {
            if (array_key_exists($key, $company) && !empty($company[$key])) {
                $companyId = $company[$key];
                break;
            }
        }
        
        $companyName = 'Unknown';
        foreach (['name', 'company_name', 'companyName', 'ragione_sociale', 'business_name'] as $key) {
            if (array_key_exists($key, $company) && !empty($company[$key])) {
                $companyName = $company[$key];
                break;
            }
        }

        if (!$companyId) {
            // Log dettagliato della struttura ricevuta per debugging
            \Log::error('FIC OAuth: Company ID not found in response', [
                'company_structure' => $company,
                'available_keys' => array_keys($company),
                'all_values' => array_values($company),
                'user_id' => $userId,
                'raw_companies_response' => $companies,
            ]);
            
            // Se la risposta contiene un oggetto nested, prova a estrarre da lì
            if (isset($company['data']) && is_array($company['data'])) {
                \Log::info('FIC OAuth: Trying nested data structure');
                $nestedCompany = $company['data'];
                foreach (['id', 'company_id', 'companyId', 'ID'] as $key) {
                    if (array_key_exists($key, $nestedCompany) && !empty($nestedCompany[$key])) {
                        $companyId = $nestedCompany[$key];
                        break;
                    }
                }
                foreach (['name', 'company_name', 'companyName', 'ragione_sociale', 'business_name'] as $key) {
                    if (array_key_exists($key, $nestedCompany) && !empty($nestedCompany[$key])) {
                        $companyName = $nestedCompany[$key];
                        break;
                    }
                }
            }
            
            // Se ancora non troviamo l'ID, mostra errore dettagliato
            if (!$companyId) {
                $frontendUrl = config('app.frontend_url', 'https://pedro.oppla.club');
                $debugInfo = [
                    'keys' => array_keys($company),
                    'sample' => array_slice($company, 0, 3, true), // Prime 3 chiavi per debug
                ];
                return redirect($frontendUrl . '/invoices?fic_error=invalid_company_data&debug=' . urlencode(json_encode($debugInfo)));
            }
        }

        \Log::info('FIC OAuth: Storing connection', [
            'user_id' => $userId,
            'company_id' => $companyId,
            'company_name' => $companyName,
        ]);

        // Update the pending connection with actual tokens
        $pendingConnection->update([
            'fic_company_id' => $companyId,
            'company_name' => $companyName,
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'token_expires_at' => $tokenData['token_expires_at'],
            'refresh_token_expires_at' => $tokenData['refresh_token_expires_at'],
            'scopes' => config('fatture_in_cloud.default_scopes'),
            'is_active' => true,
            'last_sync_at' => now(),
            'pending_oauth_state' => null,
            'oauth_state_expires_at' => null,
        ]);

        \Log::info('FIC OAuth: Connection stored successfully', ['connection_id' => $pendingConnection->id]);

        return redirect($frontendUrl . '/invoices?fic_connected=true');
        
        } catch (\Exception $e) {
            \Log::error('FIC OAuth callback FATAL ERROR', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            
            $frontendUrl = config('app.frontend_url', 'https://pedro.oppla.club');
            return redirect($frontendUrl . '/invoices?fic_error=callback_failed&message=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Get connection status.
     */
    public function status()
    {
        $connection = FattureInCloudConnection::where('user_id', Auth::id())
            ->where('is_active', true)
            ->first();

        if (!$connection) {
            return response()->json([
                'connected' => false,
            ]);
        }

        return response()->json([
            'connected' => true,
            'company_name' => $connection->company_name,
            'last_sync' => $connection->last_sync_at,
            'token_expires_at' => $connection->token_expires_at,
            'needs_refresh' => $connection->needsRefresh(),
        ]);
    }

    /**
     * Disconnect Fatture in Cloud integration.
     */
    public function disconnect()
    {
        FattureInCloudConnection::where('user_id', Auth::id())
            ->update(['is_active' => false]);

        return response()->json([
            'message' => 'Fatture in Cloud disconnected successfully',
        ]);
    }

    /**
     * Get issued invoices from Fatture in Cloud.
     */
    public function getInvoices(Request $request)
    {
        $connection = $this->getActiveConnection();

        if (!$connection) {
            return response()->json(['error' => 'Not connected to Fatture in Cloud'], 400);
        }

        $filters = $request->only(['date_from', 'date_to', 'type', 'page', 'per_page']);
        $invoices = $this->ficService->getIssuedInvoices($connection, $filters);

        return response()->json($invoices);
    }

    /**
     * Create an invoice in Fatture in Cloud.
     */
    public function createInvoice(Request $request)
    {
        $connection = $this->getActiveConnection();

        if (!$connection) {
            return response()->json(['error' => 'Not connected to Fatture in Cloud'], 400);
        }

        $validated = $request->validate([
            'type' => 'required|string',
            'entity' => 'required|array',
            'date' => 'required|date',
            'items_list' => 'required|array',
            'payments_list' => 'required|array',
        ]);

        $invoice = $this->ficService->createIssuedInvoice($connection, $validated);

        if (!$invoice) {
            return response()->json(['error' => 'Failed to create invoice'], 500);
        }

        return response()->json($invoice);
    }

    /**
     * Get clients from Fatture in Cloud.
     */
    public function getClients(Request $request)
    {
        $connection = $this->getActiveConnection();

        if (!$connection) {
            return response()->json(['error' => 'Not connected to Fatture in Cloud'], 400);
        }

        $filters = $request->only(['q', 'page', 'per_page']);
        $clients = $this->ficService->getClients($connection, $filters);

        return response()->json($clients);
    }

    /**
     * Create a client in Fatture in Cloud.
     */
    public function createClient(Request $request)
    {
        $connection = $this->getActiveConnection();

        if (!$connection) {
            return response()->json(['error' => 'Not connected to Fatture in Cloud'], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'vat_number' => 'nullable|string',
            'tax_code' => 'nullable|string',
            'address_street' => 'nullable|string',
            'address_city' => 'nullable|string',
            'address_province' => 'nullable|string',
            'address_postal_code' => 'nullable|string',
            'email' => 'nullable|email',
            'certified_email' => 'nullable|email',
            'phone' => 'nullable|string',
        ]);

        $client = $this->ficService->createClient($connection, $validated);

        if (!$client) {
            return response()->json(['error' => 'Failed to create client'], 500);
        }

        return response()->json($client);
    }

    /**
     * Get suppliers from Fatture in Cloud.
     */
    public function getSuppliers(Request $request)
    {
        $connection = $this->getActiveConnection();

        if (!$connection) {
            return response()->json(['error' => 'Not connected to Fatture in Cloud'], 400);
        }

        $filters = $request->only(['q', 'page', 'per_page']);
        $suppliers = $this->ficService->getSuppliers($connection, $filters);

        return response()->json($suppliers);
    }

    /**
     * Get received documents from Fatture in Cloud.
     */
    public function getReceivedDocuments(Request $request)
    {
        $connection = $this->getActiveConnection();

        if (!$connection) {
            return response()->json(['error' => 'Not connected to Fatture in Cloud'], 400);
        }

        $filters = $request->only(['date_from', 'date_to', 'page', 'per_page']);
        $documents = $this->ficService->getReceivedDocuments($connection, $filters);

        return response()->json($documents);
    }

    /**
     * Sync passive invoices (received documents) from Fatture in Cloud to local database.
     */
    public function syncPassiveInvoices(Request $request)
    {
        $year = $request->input('year', now()->year);

        try {
            $result = $this->ficService->syncPassiveInvoices($year);

            return response()->json([
                'success' => true,
                'message' => "Sincronizzazione fatture passive completata",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active connection for current user.
     */
    private function getActiveConnection(): ?FattureInCloudConnection
    {
        return FattureInCloudConnection::where('user_id', Auth::id())
            ->where('is_active', true)
            ->first();
    }
}
