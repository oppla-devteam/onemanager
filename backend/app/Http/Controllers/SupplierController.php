<?php

namespace App\Http\Controllers;

use App\Http\Traits\CsvExportTrait;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    use CsvExportTrait;

    /**
     * Esporta fornitori in formato CSV
     */
    public function export()
    {
        $suppliers = Supplier::orderBy('ragione_sociale')->get();

        $data = [];
        foreach ($suppliers as $s) {
            $data[] = [
                'ID' => $s->id,
                'Ragione Sociale' => $s->ragione_sociale ?? '',
                'P.IVA' => $s->piva ?? '',
                'Codice Fiscale' => $s->codice_fiscale ?? '',
                'Email' => $s->email ?? '',
                'Telefono' => $s->phone ?? '',
                'PEC' => $s->pec ?? '',
                'SDI' => $s->sdi_code ?? '',
                'Indirizzo' => $s->indirizzo ?? '',
                'Città' => $s->citta ?? '',
                'Provincia' => $s->provincia ?? '',
                'CAP' => $s->cap ?? '',
                'Tipo' => $s->type ?? '',
                'IBAN' => $s->iban ?? '',
                'Giorni Pagamento' => $s->giorni_pagamento ?? 30,
                'Attivo' => $s->is_active ? 'Sì' : 'No',
                'Note' => $s->note ?? '',
            ];
        }

        return $this->streamCsv($data, 'fornitori_' . date('Y-m-d_His') . '.csv');
    }

    /**
     * Get all suppliers with optional filters
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('ragione_sociale', 'like', "%{$search}%")
                    ->orWhere('piva', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'ragione_sociale');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Include invoice stats
        $query->withCount('invoices')
            ->withSum('invoices as total_invoiced', 'totale')
            ->withSum(['invoices as pending_amount' => function ($q) {
                $q->where('payment_status', 'non_pagata');
            }], 'totale');

        // Pagination
        $perPage = $request->input('per_page', 25);
        $suppliers = $query->paginate($perPage);

        return response()->json($suppliers);
    }

    /**
     * Get a single supplier with invoices
     */
    public function show(Supplier $supplier)
    {
        $supplier->load(['invoices' => function ($q) {
            $q->orderBy('data_emissione', 'desc')->limit(50);
        }]);

        // Calculate stats
        $stats = [
            'total_invoices' => $supplier->invoices->count(),
            'total_amount' => $supplier->invoices->sum('totale'),
            'pending_amount' => $supplier->invoices->where('payment_status', 'non_pagata')->sum('totale'),
            'paid_amount' => $supplier->invoices->where('payment_status', 'pagata')->sum('totale'),
        ];

        return response()->json([
            'supplier' => $supplier,
            'stats' => $stats,
        ]);
    }

    /**
     * Create a new supplier
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ragione_sociale' => 'required|string|max:255',
            'piva' => 'nullable|string|max:20',
            'codice_fiscale' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'pec' => 'nullable|email|max:255',
            'sdi_code' => 'nullable|string|max:10',
            'indirizzo' => 'nullable|string|max:255',
            'citta' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:5',
            'cap' => 'nullable|string|max:10',
            'nazione' => 'nullable|string|max:2|uppercase',
            'type' => 'in:italiano_sdi,estero,altro',
            'iban' => 'nullable|string|max:50',
            'giorni_pagamento' => 'nullable|integer|min:0|max:365',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['nazione'] = $data['nazione'] ?? 'IT';
        $data['type'] = $data['type'] ?? 'italiano_sdi';
        $data['giorni_pagamento'] = $data['giorni_pagamento'] ?? 30;

        $supplier = Supplier::create($data);

        return response()->json([
            'message' => 'Fornitore creato con successo',
            'supplier' => $supplier,
        ], 201);
    }

    /**
     * Update a supplier
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validator = Validator::make($request->all(), [
            'ragione_sociale' => 'sometimes|required|string|max:255',
            'piva' => 'nullable|string|max:20',
            'codice_fiscale' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'pec' => 'nullable|email|max:255',
            'sdi_code' => 'nullable|string|max:10',
            'indirizzo' => 'nullable|string|max:255',
            'citta' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:5',
            'cap' => 'nullable|string|max:10',
            'nazione' => 'nullable|string|max:2|uppercase',
            'type' => 'in:italiano_sdi,estero,altro',
            'iban' => 'nullable|string|max:50',
            'giorni_pagamento' => 'nullable|integer|min:0|max:365',
            'is_active' => 'boolean',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $supplier->update($validator->validated());

        return response()->json([
            'message' => 'Fornitore aggiornato',
            'supplier' => $supplier->fresh(),
        ]);
    }

    /**
     * Delete a supplier
     */
    public function destroy(Supplier $supplier)
    {
        // Check if supplier has invoices
        if ($supplier->invoices()->count() > 0) {
            return response()->json([
                'error' => 'Impossibile eliminare: il fornitore ha fatture associate',
            ], 409);
        }

        $supplier->delete();

        return response()->json([
            'message' => 'Fornitore eliminato',
        ]);
    }

    /**
     * Get supplier statistics
     */
    public function stats()
    {
        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::where('is_active', true)->count();
        
        $totalInvoiced = Supplier::withSum('invoices', 'totale')
            ->get()
            ->sum('invoices_sum_totale') ?? 0;
            
        $pendingPayments = Supplier::query()
            ->withSum(['invoices as pending' => function ($q) {
                $q->where('payment_status', 'non_pagata');
            }], 'totale')
            ->get()
            ->sum('pending') ?? 0;

        return response()->json([
            'total_suppliers' => $totalSuppliers,
            'active_suppliers' => $activeSuppliers,
            'total_invoiced' => round($totalInvoiced, 2),
            'pending_payments' => round($pendingPayments, 2),
        ]);
    }

    /**
     * Search suppliers for autocomplete
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        
        $suppliers = Supplier::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('ragione_sociale', 'like', "%{$query}%")
                    ->orWhere('piva', 'like', "%{$query}%");
            })
            ->select('id', 'ragione_sociale', 'piva', 'email')
            ->limit(10)
            ->get();

        return response()->json($suppliers);
    }
}
