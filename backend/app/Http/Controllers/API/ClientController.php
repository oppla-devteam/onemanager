<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\CsvExportTrait;
use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    use CsvExportTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get all clients with their restaurants and partner
        $query = Client::with(['restaurants.partner']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ragione_sociale', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('piva', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Get all matching clients
        $allClients = $query->get();

        // Convert clients to array with restaurants and partners
        $clients = $allClients->map(function($client) {
            return [
                'id' => $client->id,
                'guid' => $client->guid,
                'oppla_external_id' => $client->oppla_external_id,
                'type' => $client->type,
                'tipo_societa' => $client->tipo_societa,
                'ragione_sociale' => $client->ragione_sociale,
                'email' => $client->email,
                'phone' => $client->phone,
                'telefono' => $client->phone, // Alias for frontend compatibility
                'piva' => $client->piva,
                'codice_fiscale' => $client->codice_fiscale,
                'codice_fiscale_titolare' => $client->codice_fiscale_titolare,
                'pec' => $client->pec,
                'sdi_code' => $client->sdi_code,
                'indirizzo' => $client->indirizzo,
                'citta' => $client->citta,
                'provincia' => $client->provincia,
                'cap' => $client->cap,
                'nazione' => $client->nazione,
                'stripe_customer_id' => $client->stripe_customer_id,
                'has_delivery' => $client->has_delivery,
                'is_active' => $client->is_active,
                'status' => $client->status,
                'source' => 'local',
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at,
                'restaurants' => $client->restaurants->map(function($restaurant) {
                    return [
                        'id' => $restaurant->id,
                        'nome' => $restaurant->nome,
                        'indirizzo' => $restaurant->indirizzo,
                        'citta' => $restaurant->citta,
                        'provincia' => $restaurant->provincia,
                        'cap' => $restaurant->cap,
                        'telefono' => $restaurant->telefono,
                        'email' => $restaurant->email,
                        'is_active' => $restaurant->is_active,
                        'partner' => $restaurant->partner ? [
                            'id' => $restaurant->partner->id,
                            'nome' => $restaurant->partner->nome,
                            'cognome' => $restaurant->partner->cognome,
                            'email' => $restaurant->partner->email,
                            'telefono' => $restaurant->partner->telefono,
                            'is_active' => $restaurant->partner->is_active,
                        ] : null
                    ];
                })
            ];
        })->toArray();
        
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        usort($clients, function($a, $b) use ($sortBy, $sortOrder) {
            $valA = $a[$sortBy] ?? '';
            $valB = $b[$sortBy] ?? '';
            
            $comparison = $valA <=> $valB;
            return $sortOrder === 'desc' ? -$comparison : $comparison;
        });

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $total = count($clients);
        
        $clients = array_slice($clients, ($page - 1) * $perPage, $perPage);

        return response()->json([
            'data' => $clients,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:partner_oppla,cliente_extra,consumatore',
            'tipo_societa' => 'nullable|in:societa,ditta_individuale',
            'ragione_sociale' => 'required|string|max:255',
            'piva' => 'nullable|string|max:20',
            'codice_fiscale' => 'nullable|string|max:20',
            'codice_fiscale_titolare' => 'nullable|string|max:20',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'pec' => 'nullable|email',
            'sdi_code' => 'nullable|string|max:7',
            'indirizzo' => 'nullable|string',
            'citta' => 'nullable|string',
            'provincia' => 'nullable|string|max:2',
            'cap' => 'nullable|string|max:5',
            'nazione' => 'nullable|string|max:2',
            'has_domain' => 'boolean',
            'has_pos' => 'boolean',
            'has_delivery' => 'boolean',
            'is_partner_logistico' => 'boolean',
            'fee_mensile' => 'nullable|numeric|min:0',
            'fee_ordine' => 'nullable|numeric|min:0',
            'fee_consegna_base' => 'nullable|numeric|min:0',
            'fee_consegna_km' => 'nullable|numeric|min:0',
            'abbonamento_mensile' => 'nullable|numeric|min:0',
        ]);

        $validated['guid'] = (string) Str::uuid();
        
        // Handle telefono/phone field aliasing
        if (isset($validated['telefono']) && !isset($validated['phone'])) {
            $validated['phone'] = $validated['telefono'];
        }
        unset($validated['telefono']);
        
        $client = Client::create($validated);

        return response()->json($client, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $client = Client::with(['invoices', 'deliveries', 'contracts'])->findOrFail($id);
        return response()->json($client);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $client = Client::findOrFail($id);

        $validated = $request->validate([
            'type' => 'sometimes|in:partner_oppla,cliente_extra,consumatore',
            'tipo_societa' => 'nullable|in:societa,ditta_individuale',
            'ragione_sociale' => 'sometimes|string|max:255',
            'piva' => 'nullable|string|max:20',
            'codice_fiscale' => 'nullable|string|max:20',
            'codice_fiscale_titolare' => 'nullable|string|max:20',
            'email' => 'sometimes|email|unique:clients,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'pec' => 'nullable|email',
            'sdi_code' => 'nullable|string|max:7',
            'indirizzo' => 'nullable|string',
            'citta' => 'nullable|string',
            'provincia' => 'nullable|string|max:2',
            'cap' => 'nullable|string|max:5',
            'nazione' => 'nullable|string|max:2',
            'has_domain' => 'boolean',
            'has_pos' => 'boolean',
            'has_delivery' => 'boolean',
            'is_partner_logistico' => 'boolean',
            'fee_mensile' => 'nullable|numeric|min:0',
            'fee_ordine' => 'nullable|numeric|min:0',
            'fee_consegna_base' => 'nullable|numeric|min:0',
            'fee_consegna_km' => 'nullable|numeric|min:0',
            'abbonamento_mensile' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Handle telefono/phone field aliasing
        if (isset($validated['telefono']) && !isset($validated['phone'])) {
            $validated['phone'] = $validated['telefono'];
        }
        unset($validated['telefono']);

        $client->update($validated);

        return response()->json($client);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json([
            'message' => 'Cliente eliminato con successo'
        ]);
    }

    /**
     * Esporta clienti in formato CSV (include ristoranti)
     */
    public function export(Request $request)
    {
        $query = Client::with('restaurants');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ragione_sociale', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('piva', 'like', "%{$search}%");
            });
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $clients = $query->orderBy('ragione_sociale')->get();

        $data = [];
        foreach ($clients as $c) {
            $restaurantNames = $c->restaurants->pluck('nome')->implode(', ');
            $data[] = [
                'ID' => $c->id,
                'Ragione Sociale' => $c->ragione_sociale ?? '',
                'P.IVA' => $c->piva ?? '',
                'Codice Fiscale' => $c->codice_fiscale ?? '',
                'Email' => $c->email ?? '',
                'Telefono' => $c->phone ?? '',
                'PEC' => $c->pec ?? '',
                'SDI' => $c->sdi_code ?? '',
                'Indirizzo' => $c->indirizzo ?? '',
                'Città' => $c->citta ?? '',
                'Provincia' => $c->provincia ?? '',
                'CAP' => $c->cap ?? '',
                'Tipo' => $c->type ?? '',
                'Status' => $c->status ?? '',
                'Fee Mensile (€)' => number_format($c->fee_mensile ?? 0, 2, ',', '.'),
                'Abbonamento (€)' => number_format($c->abbonamento_mensile ?? 0, 2, ',', '.'),
                'Data Onboarding' => $c->onboarding_date ? $c->onboarding_date->format('d/m/Y') : '',
                'Data Attivazione' => $c->activation_date ? $c->activation_date->format('d/m/Y') : '',
                'N. Ristoranti' => $c->restaurants->count(),
                'Ristoranti' => $restaurantNames,
            ];
        }

        return $this->streamCsv($data, 'clienti_' . date('Y-m-d_His') . '.csv');
    }

    /**
     * Get client statistics
     */
    public function stats()
    {
        $stats = [
            'total' => Client::count(),
            'partner_oppla' => Client::where('type', 'partner_oppla')->count(),
            'cliente_extra' => Client::where('type', 'cliente_extra')->count(),
            'consumatore' => Client::where('type', 'consumatore')->count(),
            'active' => Client::where('is_active', true)->count(),
            'inactive' => Client::where('is_active', false)->count(),
        ];

        return response()->json($stats);
    }
}
