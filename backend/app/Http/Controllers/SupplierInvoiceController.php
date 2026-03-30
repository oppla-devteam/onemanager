<?php

namespace App\Http\Controllers;

use App\Http\Traits\CsvExportTrait;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Services\FattureInCloudService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SupplierInvoiceController extends Controller
{
    use CsvExportTrait;

    public function __construct(
        private FattureInCloudService $ficService
    ) {}

    /**
     * Esporta fatture passive in formato CSV
     */
    public function export(Request $request)
    {
        $query = SupplierInvoice::with('supplier:id,ragione_sociale,piva');

        if ($request->filled('supplier_id')) $query->where('supplier_id', $request->input('supplier_id'));
        if ($request->filled('payment_status')) $query->where('payment_status', $request->input('payment_status'));
        if ($request->filled('date_from')) $query->where('data_emissione', '>=', $request->input('date_from'));
        if ($request->filled('date_to')) $query->where('data_emissione', '<=', $request->input('date_to'));

        $invoices = $query->orderBy('data_emissione', 'desc')->get();

        $data = [];
        foreach ($invoices as $inv) {
            $data[] = [
                'ID' => $inv->id,
                'Numero Fattura' => $inv->numero_fattura ?? '',
                'Fornitore' => $inv->supplier?->ragione_sociale ?? '',
                'P.IVA Fornitore' => $inv->supplier?->piva ?? '',
                'Data Emissione' => $inv->data_emissione ? $inv->data_emissione->format('d/m/Y') : '',
                'Data Scadenza' => $inv->data_scadenza ? $inv->data_scadenza->format('d/m/Y') : '',
                'Data Pagamento' => $inv->data_pagamento ? $inv->data_pagamento->format('d/m/Y') : '',
                'Imponibile (€)' => number_format($inv->imponibile ?? 0, 2, ',', '.'),
                'IVA (€)' => number_format($inv->iva ?? 0, 2, ',', '.'),
                'Totale (€)' => number_format($inv->totale ?? 0, 2, ',', '.'),
                'Stato Pagamento' => $inv->payment_status ?? '',
                'Note' => $inv->note ?? '',
            ];
        }

        return $this->streamCsv($data, 'fatture_passive_' . date('Y-m-d_His') . '.csv');
    }

    /**
     * Get all supplier invoices with filters
     */
    public function index(Request $request)
    {
        $query = SupplierInvoice::with('supplier:id,ragione_sociale,piva');

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('data_emissione', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('data_emissione', '<=', $request->input('date_to'));
        }

        // Filter overdue only
        if ($request->boolean('overdue')) {
            $query->where('payment_status', '!=', 'pagata')
                ->where('data_scadenza', '<', Carbon::today());
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('numero_fattura', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('ragione_sociale', 'like', "%{$search}%");
                    });
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'data_emissione');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = $request->input('per_page', 25);
        $invoices = $query->paginate($perPage);

        return response()->json($invoices);
    }

    /**
     * Get a single invoice
     */
    public function show(SupplierInvoice $supplierInvoice)
    {
        $supplierInvoice->load('supplier');
        return response()->json($supplierInvoice);
    }

    /**
     * Create a new supplier invoice
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'numero_fattura' => 'required|string|max:50',
            'data_emissione' => 'required|date',
            'data_scadenza' => 'nullable|date|after_or_equal:data_emissione',
            'imponibile' => 'required|numeric|min:0',
            'iva' => 'nullable|numeric|min:0',
            'totale' => 'required|numeric|min:0',
            'payment_status' => 'in:non_pagata,pagata',
            'note' => 'nullable|string',
            'fic_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['payment_status'] = $data['payment_status'] ?? 'non_pagata';

        // Auto-calculate due date if not provided (use supplier's giorni_pagamento)
        if (empty($data['data_scadenza'])) {
            $supplier = Supplier::find($data['supplier_id']);
            $terms = $supplier->giorni_pagamento ?? 30;
            $data['data_scadenza'] = Carbon::parse($data['data_emissione'])->addDays($terms);
        }

        $invoice = SupplierInvoice::create($data);

        return response()->json([
            'message' => 'Fattura passiva creata',
            'invoice' => $invoice->load('supplier'),
        ], 201);
    }

    /**
     * Update a supplier invoice
     */
    public function update(Request $request, SupplierInvoice $supplierInvoice)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'numero_fattura' => 'sometimes|string|max:50',
            'data_emissione' => 'sometimes|date',
            'data_scadenza' => 'nullable|date',
            'imponibile' => 'sometimes|numeric|min:0',
            'iva' => 'nullable|numeric|min:0',
            'totale' => 'sometimes|numeric|min:0',
            'payment_status' => 'in:non_pagata,pagata',
            'data_pagamento' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Auto-set data_pagamento when marking as paid
        if (isset($data['payment_status']) && $data['payment_status'] === 'pagata' && !$supplierInvoice->data_pagamento) {
            $data['data_pagamento'] = now();
        }

        $supplierInvoice->update($data);

        return response()->json([
            'message' => 'Fattura aggiornata',
            'invoice' => $supplierInvoice->fresh('supplier'),
        ]);
    }

    /**
     * Delete a supplier invoice
     */
    public function destroy(SupplierInvoice $supplierInvoice)
    {
        // Delete associated file if exists
        if ($supplierInvoice->file_path && Storage::exists($supplierInvoice->file_path)) {
            Storage::delete($supplierInvoice->file_path);
        }

        $supplierInvoice->delete();

        return response()->json(['message' => 'Fattura eliminata']);
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Request $request, SupplierInvoice $supplierInvoice)
    {
        $supplierInvoice->update([
            'payment_status' => 'pagata',
            'data_pagamento' => $request->input('data_pagamento', now()),
        ]);

        return response()->json([
            'message' => 'Fattura marcata come pagata',
            'invoice' => $supplierInvoice->fresh('supplier'),
        ]);
    }

    /**
     * Get statistics for supplier invoices
     */
    public function stats(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month');

        $query = SupplierInvoice::query();

        if ($month) {
            $query->whereYear('data_emissione', $year)
                ->whereMonth('data_emissione', $month);
        } else {
            $query->whereYear('data_emissione', $year);
        }

        $total = $query->sum('totale');
        $paid = (clone $query)->where('payment_status', 'pagata')->sum('totale');
        $pending = (clone $query)->where('payment_status', '!=', 'pagata')->sum('totale');
        $overdue = SupplierInvoice::where('payment_status', '!=', 'pagata')
            ->where('data_scadenza', '<', Carbon::today())
            ->sum('totale');

        $countByStatus = SupplierInvoice::select('payment_status', DB::raw('count(*) as count'))
            ->groupBy('payment_status')
            ->pluck('count', 'payment_status');

        // Monthly breakdown
        $monthlyData = SupplierInvoice::whereYear('data_emissione', $year)
            ->select(
                DB::raw('MONTH(data_emissione) as month'),
                DB::raw('SUM(totale) as total'),
                DB::raw('SUM(CASE WHEN payment_status = "pagata" THEN totale ELSE 0 END) as paid')
            )
            ->groupBy(DB::raw('MONTH(data_emissione)'))
            ->orderBy('month')
            ->get();

        return response()->json([
            'year' => $year,
            'month' => $month,
            'total_amount' => round($total, 2),
            'paid_amount' => round($paid, 2),
            'pending_amount' => round($pending, 2),
            'overdue_amount' => round($overdue, 2),
            'count_by_status' => $countByStatus,
            'monthly_breakdown' => $monthlyData,
        ]);
    }

    /**
     * Sync passive invoices from Fatture in Cloud
     */
    public function syncFromFIC(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        
        try {
            $result = $this->ficService->syncPassiveInvoices($year);
            
            return response()->json([
                'message' => 'Sincronizzazione completata',
                'synced' => $result['synced'] ?? 0,
                'created' => $result['created'] ?? 0,
                'updated' => $result['updated'] ?? 0,
                'errors' => $result['errors'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore durante la sincronizzazione: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload invoice file
     */
    public function uploadFile(Request $request, SupplierInvoice $supplierInvoice)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,xml|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $path = $file->store('supplier-invoices/' . $supplierInvoice->supplier_id, 'local');

        // Delete old file if exists
        if ($supplierInvoice->file_path && Storage::exists($supplierInvoice->file_path)) {
            Storage::delete($supplierInvoice->file_path);
        }

        $supplierInvoice->update(['file_path' => $path]);

        return response()->json([
            'message' => 'File caricato',
            'file_path' => $path,
        ]);
    }

    /**
     * Download invoice file
     */
    public function downloadFile(SupplierInvoice $supplierInvoice)
    {
        if (!$supplierInvoice->file_path || !Storage::exists($supplierInvoice->file_path)) {
            return response()->json(['error' => 'File non trovato'], 404);
        }

        return Storage::download(
            $supplierInvoice->file_path,
            "fattura_{$supplierInvoice->numero_fattura}.pdf"
        );
    }

    /**
     * Get upcoming payments (due within X days)
     */
    public function upcomingPayments(Request $request)
    {
        $days = $request->input('days', 30);
        
        $invoices = SupplierInvoice::with('supplier:id,ragione_sociale,iban')
            ->where('payment_status', '!=', 'pagata')
            ->whereBetween('data_scadenza', [Carbon::today(), Carbon::today()->addDays($days)])
            ->orderBy('data_scadenza')
            ->get();

        $totalDue = $invoices->sum('totale');

        return response()->json([
            'invoices' => $invoices,
            'total_due' => round($totalDue, 2),
            'count' => $invoices->count(),
        ]);
    }

    /**
     * Get overdue payments
     */
    public function overduePayments()
    {
        $invoices = SupplierInvoice::with('supplier:id,ragione_sociale')
            ->where('payment_status', '!=', 'pagata')
            ->where('data_scadenza', '<', Carbon::today())
            ->orderBy('data_scadenza')
            ->get();

        $totalOverdue = $invoices->sum('totale');

        // Group by days overdue
        $byAge = [
            '1-30' => $invoices->filter(fn($i) => Carbon::parse($i->data_scadenza)->diffInDays(now()) <= 30)->sum('totale'),
            '31-60' => $invoices->filter(fn($i) => Carbon::parse($i->data_scadenza)->diffInDays(now()) > 30 && Carbon::parse($i->data_scadenza)->diffInDays(now()) <= 60)->sum('totale'),
            '60+' => $invoices->filter(fn($i) => Carbon::parse($i->data_scadenza)->diffInDays(now()) > 60)->sum('totale'),
        ];

        return response()->json([
            'invoices' => $invoices,
            'total_overdue' => round($totalOverdue, 2),
            'count' => $invoices->count(),
            'by_age' => $byAge,
        ]);
    }
}
