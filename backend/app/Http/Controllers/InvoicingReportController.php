<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Delivery;
use App\Models\PosOrder;
use App\Models\UpsellingSale;
use App\Models\ClientService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoicingReportController extends Controller
{
    /**
     * Genera il "Report Figo" - Report dettagliato fatturazione
     */
    public function generateDetailedReport(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $clientType = $request->input('client_type'); // 'partner', 'extra', null (tutti)

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Query base fatture
        $invoicesQuery = Invoice::with(['client', 'items'])
            ->whereBetween('invoice_date', [$startDate, $endDate]);

        if ($clientType) {
            $invoicesQuery->whereHas('client', function($q) use ($clientType) {
                $q->where('type', $clientType);
            });
        }

        $invoices = $invoicesQuery->get();

        // Aggregazioni per tipologia
        $report = [
            'period' => [
                'month' => $month,
                'year' => $year,
                'start_date' => $startDate->format('d/m/Y'),
                'end_date' => $endDate->format('d/m/Y'),
            ],
            'summary' => [
                'total_invoices' => $invoices->count(),
                'total_amount' => $invoices->sum('total'),
                'total_tax' => $invoices->sum('tax'),
                'total_net' => $invoices->sum('subtotal'),
                'average_invoice' => $invoices->avg('total'),
            ],
            'by_client_type' => $this->groupByClientType($invoices),
            'by_invoice_type' => $this->groupByInvoiceType($invoices),
            'by_payment_method' => $this->groupByPaymentMethod($invoices),
            'top_clients' => $this->getTopClients($invoices),
            'fee_breakdown' => $this->getFeeBreakdown($startDate, $endDate, $clientType),
            'services_breakdown' => $this->getServicesBreakdown($startDate, $endDate, $clientType),
            'upselling_breakdown' => $this->getUpsellingBreakdown($startDate, $endDate, $clientType),
            'deliveries_stats' => $this->getDeliveriesStats($startDate, $endDate, $clientType),
            'pos_orders_stats' => $this->getPosOrdersStats($startDate, $endDate, $clientType),
            'daily_trend' => $this->getDailyTrend($invoices, $startDate, $endDate),
            'outstanding_invoices' => $this->getOutstandingInvoices($endDate),
        ];

        return response()->json($report);
    }

    /**
     * Raggruppa per tipo cliente
     */
    private function groupByClientType($invoices)
    {
        return $invoices->groupBy('client.type')->map(function($group, $type) {
            return [
                'type' => $type,
                'count' => $group->count(),
                'total' => $group->sum('total'),
                'percentage' => 0, // Calcolato dopo
            ];
        })->values()->map(function($item) use ($invoices) {
            $item['percentage'] = $invoices->sum('total') > 0 
                ? round(($item['total'] / $invoices->sum('total')) * 100, 2) 
                : 0;
            return $item;
        });
    }

    /**
     * Raggruppa per tipo fattura
     */
    private function groupByInvoiceType($invoices)
    {
        return $invoices->groupBy('invoice_type')->map(function($group, $type) {
            return [
                'type' => $type,
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ];
        })->values();
    }

    /**
     * Raggruppa per metodo pagamento
     */
    private function groupByPaymentMethod($invoices)
    {
        return $invoices->groupBy('payment_method')->map(function($group, $method) {
            return [
                'method' => $method,
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ];
        })->values();
    }

    /**
     * Top 10 clienti per fatturato
     */
    private function getTopClients($invoices)
    {
        return $invoices->groupBy('client_id')
            ->map(function($group) {
                $client = $group->first()->client;
                return [
                    'client_id' => $client->id,
                    'client_name' => $client->ragione_sociale,
                    'client_type' => $client->type,
                    'invoice_count' => $group->count(),
                    'total_amount' => $group->sum('total'),
                ];
            })
            ->sortByDesc('total_amount')
            ->take(10)
            ->values();
    }

    /**
     * Dettaglio fee per tipo
     */
    private function getFeeBreakdown(Carbon $startDate, Carbon $endDate, $clientType = null)
    {
        $query = Invoice::with('items')
            ->whereBetween('invoice_date', [$startDate, $endDate]);

        if ($clientType) {
            $query->whereHas('client', fn($q) => $q->where('type', $clientType));
        }

        $invoices = $query->get();

        $feeTypes = [
            'fee_consegna_base' => 'Fee consegne base',
            'fee_consegna_km' => 'Fee consegne km',
            'fee_ordini_cash' => 'Fee ordini cash',
            'fee_ordini_pos' => 'Fee ordini POS',
            'abbonamento_mensile' => 'Abbonamento mensile',
            'canone_pos' => 'Canone POS',
        ];

        $breakdown = [];
        foreach ($feeTypes as $key => $label) {
            $total = $invoices->flatMap->items
                ->where('description', 'LIKE', "%{$label}%")
                ->sum('total');
            
            if ($total > 0) {
                $breakdown[] = [
                    'type' => $label,
                    'amount' => $total,
                ];
            }
        }

        return collect($breakdown);
    }

    /**
     * Dettaglio servizi fatturati
     */
    private function getServicesBreakdown(Carbon $startDate, Carbon $endDate, $clientType = null)
    {
        $query = ClientService::with(['service', 'client'])
            ->where('is_active', true);

        if ($clientType) {
            $query->whereHas('client', fn($q) => $q->where('type', $clientType));
        }

        return $query->get()
            ->groupBy('service.name')
            ->map(function($group, $serviceName) use ($startDate, $endDate) {
                $monthlyRevenue = $group->sum(function($clientService) use ($startDate, $endDate) {
                    // Calcola revenue proporzionale al periodo
                    return $clientService->monthly_fee ?? 0;
                });

                return [
                    'service_name' => $serviceName,
                    'active_clients' => $group->count(),
                    'monthly_revenue' => $monthlyRevenue,
                ];
            })
            ->values();
    }

    /**
     * Dettaglio upselling
     */
    private function getUpsellingBreakdown(Carbon $startDate, Carbon $endDate, $clientType = null)
    {
        $query = UpsellingSale::with('client')
            ->whereBetween('sale_date', [$startDate, $endDate]);

        if ($clientType) {
            $query->whereHas('client', fn($q) => $q->where('type', $clientType));
        }

        return $query->get()
            ->groupBy('product_type')
            ->map(function($group, $productType) {
                return [
                    'product' => $productType,
                    'quantity' => $group->sum('quantity'),
                    'total_amount' => $group->sum('amount'),
                    'clients_count' => $group->unique('client_id')->count(),
                ];
            })
            ->values();
    }

    /**
     * Statistiche consegne
     */
    private function getDeliveriesStats(Carbon $startDate, Carbon $endDate, $clientType = null)
    {
        $query = Delivery::with('client')
            ->whereBetween('delivery_date', [$startDate, $endDate]);

        if ($clientType) {
            $query->whereHas('client', fn($q) => $q->where('type', $clientType));
        }

        $deliveries = $query->get();

        return [
            'total_deliveries' => $deliveries->count(),
            'total_km' => $deliveries->sum('distance_km'),
            'total_fee_base' => $deliveries->sum('fee_base'),
            'total_fee_km' => $deliveries->sum('fee_km'),
            'total_fee' => $deliveries->sum('fee_base') + $deliveries->sum('fee_km'),
            'avg_distance' => $deliveries->avg('distance_km'),
            'by_status' => $deliveries->groupBy('status')->map->count(),
        ];
    }

    /**
     * Statistiche ordini POS
     */
    private function getPosOrdersStats(Carbon $startDate, Carbon $endDate, $clientType = null)
    {
        $query = PosOrder::with('client')
            ->whereBetween('order_date', [$startDate, $endDate]);

        if ($clientType) {
            $query->whereHas('client', fn($q) => $q->where('type', $clientType));
        }

        $orders = $query->get();

        return [
            'total_orders' => $orders->count(),
            'total_amount' => $orders->sum('amount'),
            'total_fee' => $orders->sum('fee'),
            'avg_order_value' => $orders->avg('amount'),
            'by_payment_method' => $orders->groupBy('payment_method')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            }),
        ];
    }

    /**
     * Trend giornaliero fatturato
     */
    private function getDailyTrend($invoices, Carbon $startDate, Carbon $endDate)
    {
        $dailyData = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dayInvoices = $invoices->filter(function($invoice) use ($current) {
                return Carbon::parse($invoice->invoice_date)->isSameDay($current);
            });

            $dailyData[] = [
                'date' => $current->format('Y-m-d'),
                'day' => $current->format('d/m'),
                'count' => $dayInvoices->count(),
                'total' => $dayInvoices->sum('total'),
            ];

            $current->addDay();
        }

        return collect($dailyData);
    }

    /**
     * Fatture in sospeso (non pagate)
     */
    private function getOutstandingInvoices(Carbon $asOfDate)
    {
        $outstanding = Invoice::with('client')
            ->where('status', '!=', 'paid')
            ->where('invoice_date', '<=', $asOfDate)
            ->get();

        $overdue = $outstanding->filter(function($invoice) use ($asOfDate) {
            $dueDate = Carbon::parse($invoice->invoice_date)->addDays($invoice->payment_terms ?? 30);
            return $dueDate < $asOfDate;
        });

        return [
            'total_count' => $outstanding->count(),
            'total_amount' => $outstanding->sum('total'),
            'overdue_count' => $overdue->count(),
            'overdue_amount' => $overdue->sum('total'),
            'by_client' => $outstanding->groupBy('client_id')->map(function($group) {
                $client = $group->first()->client;
                return [
                    'client_name' => $client->ragione_sociale,
                    'count' => $group->count(),
                    'total' => $group->sum('total'),
                ];
            })->values(),
        ];
    }

    /**
     * Esporta report in Excel
     */
    public function exportToExcel(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Genera report
        $reportData = $this->generateDetailedReport($request)->getData();

        $filename = "report_fatturazione_{$year}_{$month}.csv";
        $path = storage_path('app/exports/' . $filename);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $fp = fopen($path, 'w');
        
        // Header
        fputcsv($fp, ['REPORT FATTURAZIONE DETTAGLIATO']);
        fputcsv($fp, ['Periodo:', $reportData->period->start_date . ' - ' . $reportData->period->end_date]);
        fputcsv($fp, []);

        // Riepilogo
        fputcsv($fp, ['RIEPILOGO GENERALE']);
        fputcsv($fp, ['Totale fatture', $reportData->summary->total_invoices]);
        fputcsv($fp, ['Imponibile totale', '€' . number_format($reportData->summary->total_net, 2)]);
        fputcsv($fp, ['IVA totale', '€' . number_format($reportData->summary->total_tax, 2)]);
        fputcsv($fp, ['Totale fatturato', '€' . number_format($reportData->summary->total_amount, 2)]);
        fputcsv($fp, ['Media fattura', '€' . number_format($reportData->summary->average_invoice, 2)]);
        fputcsv($fp, []);

        // Top clienti
        fputcsv($fp, ['TOP 10 CLIENTI']);
        fputcsv($fp, ['Cliente', 'Tipo', 'N. Fatture', 'Totale']);
        foreach ($reportData->top_clients as $client) {
            fputcsv($fp, [
                $client->client_name,
                $client->client_type,
                $client->invoice_count,
                '€' . number_format($client->total_amount, 2),
            ]);
        }

        fclose($fp);

        return response()->download($path)->deleteFileAfterSend(true);
    }
}
