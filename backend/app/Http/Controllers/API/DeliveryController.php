<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\CsvExportTrait;
use Illuminate\Http\Request;
use App\Models\Delivery;
use App\Services\ManagedDeliveryInvoicingService;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{
    use CsvExportTrait;

    public function index(Request $request)
    {
        $query = Delivery::with('client');

        // Filtro per data personalizzata (priorità alta)
        if ($request->has('start_date') || $request->has('end_date')) {
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            } elseif ($request->has('start_date')) {
                $query->where('created_at', '>=', $request->start_date . ' 00:00:00');
            } elseif ($request->has('end_date')) {
                $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
            }
        }
        // Filtro per periodo (solo se non ci sono date personalizzate)
        elseif ($request->has('period')) {
            switch ($request->period) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('delivery_address', 'like', "%{$search}%")
                  ->orWhereHas('client', function($q) use ($search) {
                      $q->where('ragione_sociale', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'pickup_address' => 'required|string',
            'delivery_address' => 'required|string',
            'scheduled_at' => 'required|date',
            'distance_km' => 'required|numeric|min:0',
            'fee_base' => 'required|numeric|min:0',
            'fee_km' => 'required|numeric|min:0',
            'rider_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['delivery_code'] = 'DEL-' . strtoupper(uniqid());
        $validated['status'] = 'in_attesa';
        $validated['fee_total'] = $validated['fee_base'] + ($validated['distance_km'] * $validated['fee_km']);

        $delivery = Delivery::create($validated);
        return response()->json($delivery->load('client'), 201);
    }

    public function show(string $id)
    {
        $delivery = Delivery::with('client')->findOrFail($id);
        return response()->json($delivery);
    }

    public function update(Request $request, string $id)
    {
        $delivery = Delivery::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:in_attesa,assegnata,in_consegna,completata,annullata',
            'rider_name' => 'nullable|string',
            'pickup_address' => 'sometimes|string',
            'delivery_address' => 'sometimes|string',
            'scheduled_at' => 'sometimes|date',
            'picked_up_at' => 'nullable|date',
            'delivered_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $delivery->update($validated);
        return response()->json($delivery->load('client'));
    }

    /**
     * Delete delivery and propagate changes (remove from invoices, App Partner, Tookan)
     */
    public function destroy(string $id)
    {
        try {
            \DB::beginTransaction();
            
            $delivery = Delivery::findOrFail($id);
            
            // Log operazione
            \Log::info('Deleting delivery with propagation', [
                'delivery_id' => $id,
                'order_id' => $delivery->order_id,
                'client_id' => $delivery->client_id
            ]);
            
            // 1. Rimuovi da fatture se fatturato
            if ($delivery->is_invoiced && $delivery->invoice_id) {
                $invoice = \App\Models\Invoice::find($delivery->invoice_id);
                if ($invoice) {
                    // Rimuovi delivery_id dalle invoice_items
                    \App\Models\InvoiceItem::where('invoice_id', $invoice->id)
                        ->where('delivery_id', $delivery->id)
                        ->delete();
                    
                    // Ricalcola totale fattura
                    $invoice->recalculateTotals();
                    
                    \Log::info('Removed delivery from invoice', ['invoice_id' => $invoice->id]);
                }
            }
            
            // 2. Notifica App Partner / Tookan (se integrato)
            // TODO: Implementare cancellazione su App Partner/Tookan quando integrato
            
            // 3. Elimina la consegna
            $delivery->delete();
            
            \DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Consegna eliminata con successo'
            ]);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error deleting delivery', [
                'delivery_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Esporta consegne gestite in formato CSV
     */
    public function export(Request $request)
    {
        $query = Delivery::with('client');

        // Stessi filtri dell'index
        if ($request->has('start_date') || $request->has('end_date')) {
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            } elseif ($request->has('start_date')) {
                $query->where('created_at', '>=', $request->start_date . ' 00:00:00');
            } elseif ($request->has('end_date')) {
                $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
            }
        } elseif ($request->has('period') && $request->period !== 'all') {
            switch ($request->period) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $deliveries = $query->orderBy('created_at', 'desc')->get();

        $data = [];
        foreach ($deliveries as $d) {
            $data[] = [
                'ID' => $d->id,
                'Codice Consegna' => $d->delivery_code ?? $d->order_id ?? '',
                'Data Ordine' => $d->order_date ? $d->order_date->format('d/m/Y H:i') : '',
                'Cliente' => $d->client?->ragione_sociale ?? 'N/A',
                'Indirizzo Ritiro' => $d->pickup_address ?? '',
                'Indirizzo Consegna' => $d->delivery_address ?? '',
                'Distanza Km' => $d->distance_km ?? 0,
                'Importo Ordine (€)' => number_format(($d->order_amount ?? 0) / 100, 2, ',', '.'),
                'Fee Base (€)' => number_format(($d->delivery_fee_base ?? 0) / 100, 2, ',', '.'),
                'Fee Distanza (€)' => number_format(($d->delivery_fee_distance ?? 0) / 100, 2, ',', '.'),
                'Fee Totale (€)' => number_format(($d->delivery_fee_total ?? 0) / 100, 2, ',', '.'),
                'Stato' => $d->status ?? '',
                'Nome Cliente Finale' => $d->customer_name ?? '',
                'Telefono Cliente' => $d->customer_phone ?? '',
                'Metodo Pagamento' => $d->payment_method ?? '',
                'Note' => $d->note ?? $d->delivery_notes ?? '',
                'Fatturata' => $d->is_invoiced ? 'Sì' : 'No',
                'Data Creazione' => $d->created_at ? $d->created_at->format('d/m/Y H:i') : '',
            ];
        }

        return $this->streamCsv($data, 'consegne_gestite_' . date('Y-m-d_His') . '.csv');
    }

    public function stats()
    {
        $stats = [
            'total' => Delivery::count(),
            'in_attesa' => Delivery::where('status', 'in_attesa')->count(),
            'assegnata' => Delivery::where('status', 'assegnata')->count(),
            'in_consegna' => Delivery::where('status', 'in_consegna')->count(),
            'completata' => Delivery::where('status', 'completata')->count(),
            'completata_oggi' => Delivery::where('status', 'completata')
                ->whereDate('delivered_at', today())->count(),
            'totale_mese' => Delivery::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
        ];
        return response()->json($stats);
    }

    /**
     * Pre-genera fatture per consegne gestite non fatturate
     */
    public function pregenerateInvoices(Request $request)
    {
        try {
            $service = new ManagedDeliveryInvoicingService();
            $result = $service->pregenerate($request->all());

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('[ManagedDeliveryInvoicing] Errore pre-generazione: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la pre-generazione delle fatture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera fatture per consegne gestite non fatturate
     */
    public function generateInvoices(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'period' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        try {
            $service = new ManagedDeliveryInvoicingService();
            $result = $service->generate($validated);

            return response()->json([
                'success' => true,
                'message' => "Generate {$result['count']} fatture per consegne gestite",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('[ManagedDeliveryInvoicing] Errore generazione: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la generazione delle fatture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
