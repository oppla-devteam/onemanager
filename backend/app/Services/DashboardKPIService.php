<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\Delivery;
use App\Models\PosOrder;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardKPIService
{
    /**
     * Ottieni tutti i KPI per la dashboard economica
     */
    public function getEconomicKPIs(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'revenue' => $this->getRevenueKPIs($startDate, $endDate),
            'margins' => $this->getMarginsKPIs($startDate, $endDate),
            'cash_flow' => $this->getCashFlowKPIs($startDate, $endDate),
            'clients' => $this->getClientsKPIs($startDate, $endDate),
            'operations' => $this->getOperationsKPIs($startDate, $endDate),
            'receivables' => $this->getReceivablesKPIs(),
            'payables' => $this->getPayablesKPIs(),
        ];
    }

    /**
     * KPI Fatturato
     */
    private function getRevenueKPIs(Carbon $startDate, Carbon $endDate): array
    {
        $invoices = Invoice::whereBetween('data_emissione', [$startDate, $endDate])->get();
        
        $previousPeriod = $this->getPreviousPeriod($startDate, $endDate);
        $previousInvoices = Invoice::whereBetween('data_emissione', $previousPeriod)->get();

        $currentRevenue = $invoices->sum('totale');
        $previousRevenue = $previousInvoices->sum('totale');
        $growth = $previousRevenue > 0 
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;

        return [
            'total_revenue' => $currentRevenue,
            'total_invoices' => $invoices->count(),
            'average_invoice' => $invoices->avg('totale') ?? 0,
            'growth_percentage' => round($growth, 2),
            'by_type' => [
                'partner' => $invoices->whereIn('client.type', ['partner'])->sum('totale'),
                'extra' => $invoices->whereIn('client.type', ['extra'])->sum('totale'),
                'consumer' => $invoices->whereIn('client.type', ['consumer'])->sum('totale'),
            ],
            'by_payment_method' => $invoices->groupBy('payment_method')->map->sum('totale'),
        ];
    }

    /**
     * KPI Margini
     */
    private function getMarginsKPIs(Carbon $startDate, Carbon $endDate): array
    {
        // Revenue (fatture attive)
        $revenue = Invoice::whereBetween('data_emissione', [$startDate, $endDate])
            ->sum('totale');

        // Costs estimation (simplified - you can enhance this)
        // Cost delle consegne (assumendo 70% è costo)
        $deliveriesCost = Delivery::whereBetween('delivery_date', [$startDate, $endDate])
            ->sum('fee_base') * 0.7;

        // Cost ordini POS (assumendo commissioni 2%)
        $posOrdersCost = PosOrder::whereBetween('order_date', [$startDate, $endDate])
            ->sum('amount') * 0.02;

        $totalCosts = $deliveriesCost + $posOrdersCost;
        $grossMargin = $revenue - $totalCosts;
        $marginPercentage = $revenue > 0 ? ($grossMargin / $revenue) * 100 : 0;

        return [
            'gross_revenue' => $revenue,
            'total_costs' => $totalCosts,
            'gross_margin' => $grossMargin,
            'margin_percentage' => round($marginPercentage, 2),
            'costs_breakdown' => [
                'deliveries' => $deliveriesCost,
                'pos_fees' => $posOrdersCost,
            ],
        ];
    }

    /**
     * KPI Flusso di Cassa
     */
    private function getCashFlowKPIs(Carbon $startDate, Carbon $endDate): array
    {
        // Entrate effettive (fatture pagate)
        $cashIn = Invoice::where('payment_status', 'pagata')
            ->whereBetween('data_pagamento', [$startDate, $endDate])
            ->sum('totale');

        // Uscite (bank transactions negative)
        $cashOut = BankTransaction::where('type', 'uscita')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $netCashFlow = $cashIn - abs($cashOut);

        // Saldo bancario attuale
        $currentBalance = BankAccount::where('is_active', true)->sum('saldo_attuale');

        // Previsione prossimi 30 giorni
        $forecastRevenue = Invoice::where('status', '!=', 'pagata')
            ->whereBetween('data_scadenza', [now(), now()->addDays(30)])
            ->sum('totale');

        return [
            'cash_in' => $cashIn,
            'cash_out' => abs($cashOut),
            'net_cash_flow' => $netCashFlow,
            'current_balance' => $currentBalance,
            'forecast_30_days' => $forecastRevenue,
            'projected_balance' => $currentBalance + $forecastRevenue,
        ];
    }

    /**
     * KPI Clienti
     */
    private function getClientsKPIs(Carbon $startDate, Carbon $endDate): array
    {
        $totalClients = Client::where('is_active', true)->count();
        
        $newClients = Client::whereBetween('created_at', [$startDate, $endDate])->count();
        
        $clientsWithRevenue = Invoice::whereBetween('data_emissione', [$startDate, $endDate])
            ->select('client_id')
            ->distinct()
            ->count();

        $avgRevenuePerClient = $clientsWithRevenue > 0 
            ? Invoice::whereBetween('data_emissione', [$startDate, $endDate])->sum('totale') / $clientsWithRevenue
            : 0;

        return [
            'total_active' => $totalClients,
            'new_clients' => $newClients,
            'clients_with_revenue' => $clientsWithRevenue,
            'avg_revenue_per_client' => round($avgRevenuePerClient, 2),
            'by_type' => [
                'partner' => Client::where('type', 'partner')->where('is_active', true)->count(),
                'extra' => Client::where('type', 'extra')->where('is_active', true)->count(),
                'consumer' => Client::where('type', 'consumer')->where('is_active', true)->count(),
            ],
        ];
    }

    /**
     * KPI Operazioni
     */
    private function getOperationsKPIs(Carbon $startDate, Carbon $endDate): array
    {
        $deliveries = Delivery::whereBetween('delivery_date', [$startDate, $endDate])->get();
        $posOrders = PosOrder::whereBetween('order_date', [$startDate, $endDate])->get();

        return [
            'deliveries' => [
                'total' => $deliveries->count(),
                'completed' => $deliveries->where('status', 'completed')->count(),
                'total_km' => $deliveries->sum('distance_km'),
                'total_revenue' => $deliveries->sum('fee_base') + $deliveries->sum('fee_km'),
            ],
            'pos_orders' => [
                'total' => $posOrders->count(),
                'total_amount' => $posOrders->sum('amount'),
                'total_fees' => $posOrders->sum('fee'),
                'avg_order_value' => $posOrders->avg('amount') ?? 0,
            ],
        ];
    }

    /**
     * KPI Crediti (Receivables)
     */
    private function getReceivablesKPIs(): array
    {
        $unpaidInvoices = Invoice::where('payment_status', '!=', 'pagata')->get();
        
        $overdue = $unpaidInvoices->filter(function($invoice) {
            if (!$invoice->data_scadenza) {
                $dueDate = Carbon::parse($invoice->data_emissione)->addDays(30);
            } else {
                $dueDate = Carbon::parse($invoice->data_scadenza);
            }
            return $dueDate < now();
        });

        return [
            'total_outstanding' => $unpaidInvoices->sum('totale'),
            'total_invoices' => $unpaidInvoices->count(),
            'overdue_amount' => $overdue->sum('totale'),
            'overdue_count' => $overdue->count(),
            'aging' => [
                '0-30_days' => $this->getAgingBucket($unpaidInvoices, 0, 30),
                '31-60_days' => $this->getAgingBucket($unpaidInvoices, 31, 60),
                '61-90_days' => $this->getAgingBucket($unpaidInvoices, 61, 90),
                'over_90_days' => $this->getAgingBucket($unpaidInvoices, 91, 999),
            ],
        ];
    }

    /**
     * KPI Debiti (Payables) - fatture fornitori
     */
    private function getPayablesKPIs(): array
    {
        $unpaidInvoices = \App\Models\SupplierInvoice::where('payment_status', '!=', 'pagata')->get();
        
        $overdue = $unpaidInvoices->filter(function($invoice) {
            return $invoice->data_scadenza && $invoice->data_scadenza < now();
        });

        $dueThisMonth = $unpaidInvoices->filter(function($invoice) {
            return $invoice->data_scadenza 
                && $invoice->data_scadenza >= now()->startOfMonth() 
                && $invoice->data_scadenza <= now()->endOfMonth();
        });

        return [
            'total_outstanding' => $unpaidInvoices->sum('totale'),
            'total_invoices' => $unpaidInvoices->count(),
            'overdue_amount' => $overdue->sum('totale'),
            'overdue_count' => $overdue->count(),
            'due_this_month' => $dueThisMonth->sum('totale'),
            'due_this_month_count' => $dueThisMonth->count(),
        ];
    }

    /**
     * Dashboard completa unificata con tutti i KPI
     */
    public function getUnifiedDashboard(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $startDate->diffInDays($endDate),
            ],
            'summary' => $this->getSummaryKPIs($startDate, $endDate),
            'revenue' => $this->getRevenueKPIs($startDate, $endDate),
            'margins' => $this->getMarginsKPIs($startDate, $endDate),
            'cash_flow' => $this->getEnhancedCashFlowKPIs($startDate, $endDate),
            'clients' => $this->getClientsKPIs($startDate, $endDate),
            'operations' => $this->getOperationsKPIs($startDate, $endDate),
            'receivables' => $this->getReceivablesKPIs(),
            'payables' => $this->getPayablesKPIs(),
            'trends' => $this->getMonthlyTrends(),
            'alerts' => $this->getBusinessAlerts(),
        ];
    }

    /**
     * KPI riepilogativo con confronti
     */
    private function getSummaryKPIs(Carbon $startDate, Carbon $endDate): array
    {
        $currentRevenue = Invoice::whereBetween('data_emissione', [$startDate, $endDate])->sum('totale');
        $currentCosts = \App\Models\SupplierInvoice::whereBetween('data_emissione', [$startDate, $endDate])->sum('totale');
        
        $previousPeriod = $this->getPreviousPeriod($startDate, $endDate);
        $previousRevenue = Invoice::whereBetween('data_emissione', $previousPeriod)->sum('totale');
        $previousCosts = \App\Models\SupplierInvoice::whereBetween('data_emissione', $previousPeriod)->sum('totale');

        $revenueGrowth = $previousRevenue > 0 
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;

        return [
            'net_income' => round($currentRevenue - $currentCosts, 2),
            'total_revenue' => round($currentRevenue, 2),
            'total_costs' => round($currentCosts, 2),
            'revenue_growth' => round($revenueGrowth, 2),
            'comparison' => [
                'previous_revenue' => round($previousRevenue, 2),
                'previous_costs' => round($previousCosts, 2),
            ],
        ];
    }

    /**
     * Cash Flow enhanced con previsioni
     */
    private function getEnhancedCashFlowKPIs(Carbon $startDate, Carbon $endDate): array
    {
        // Entrate effettive (fatture pagate nel periodo)
        $cashIn = Invoice::where('payment_status', 'pagata')
            ->whereBetween('data_pagamento', [$startDate, $endDate])
            ->sum('totale');

        // Uscite effettive (fatture fornitori pagate)
        $cashOut = \App\Models\SupplierInvoice::where('payment_status', 'pagata')
            ->whereBetween('data_pagamento', [$startDate, $endDate])
            ->sum('totale');

        // Da transazioni bancarie (più accurato)
        $bankIn = BankTransaction::where('type', 'entrata')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $bankOut = BankTransaction::where('type', 'uscita')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $netCashFlow = $bankIn - abs($bankOut);

        // Saldo bancario attuale
        $currentBalance = BankAccount::where('is_active', true)->sum('saldo_attuale');

        // Previsioni cash flow
        $forecast = $this->getCashFlowForecast();

        return [
            'cash_in' => round($cashIn, 2),
            'cash_out' => round(abs($cashOut), 2),
            'net_cash_flow' => round($netCashFlow, 2),
            'bank_movements' => [
                'in' => round($bankIn, 2),
                'out' => round(abs($bankOut), 2),
            ],
            'current_balance' => round($currentBalance, 2),
            'forecast' => $forecast,
            'runway_days' => $this->calculateRunwayDays($currentBalance, $forecast['avg_monthly_burn']),
        ];
    }

    /**
     * Previsione cash flow prossimi 30/60/90 giorni
     */
    private function getCashFlowForecast(): array
    {
        // Crediti in scadenza (entrate previste)
        $expectedIn30 = Invoice::where('payment_status', '!=', 'pagata')
            ->whereBetween('data_scadenza', [now(), now()->addDays(30)])
            ->sum('totale');

        $expectedIn60 = Invoice::where('payment_status', '!=', 'pagata')
            ->whereBetween('data_scadenza', [now(), now()->addDays(60)])
            ->sum('totale');

        $expectedIn90 = Invoice::where('payment_status', '!=', 'pagata')
            ->whereBetween('data_scadenza', [now(), now()->addDays(90)])
            ->sum('totale');

        // Debiti in scadenza (uscite previste)
        $expectedOut30 = \App\Models\SupplierInvoice::where('payment_status', '!=', 'pagata')
            ->whereBetween('data_scadenza', [now(), now()->addDays(30)])
            ->sum('totale');

        $expectedOut60 = \App\Models\SupplierInvoice::where('payment_status', '!=', 'pagata')
            ->whereBetween('data_scadenza', [now(), now()->addDays(60)])
            ->sum('totale');

        $expectedOut90 = \App\Models\SupplierInvoice::where('payment_status', '!=', 'pagata')
            ->whereBetween('data_scadenza', [now(), now()->addDays(90)])
            ->sum('totale');

        // Media burn rate mensile (ultimi 3 mesi)
        $avgMonthlyBurn = \App\Models\SupplierInvoice::where('payment_status', 'pagata')
            ->where('data_pagamento', '>=', now()->subMonths(3))
            ->sum('totale') / 3;

        return [
            '30_days' => [
                'expected_in' => round($expectedIn30, 2),
                'expected_out' => round($expectedOut30, 2),
                'net' => round($expectedIn30 - $expectedOut30, 2),
            ],
            '60_days' => [
                'expected_in' => round($expectedIn60, 2),
                'expected_out' => round($expectedOut60, 2),
                'net' => round($expectedIn60 - $expectedOut60, 2),
            ],
            '90_days' => [
                'expected_in' => round($expectedIn90, 2),
                'expected_out' => round($expectedOut90, 2),
                'net' => round($expectedIn90 - $expectedOut90, 2),
            ],
            'avg_monthly_burn' => round($avgMonthlyBurn, 2),
        ];
    }

    /**
     * Calcola runway (giorni di copertura finanziaria)
     */
    private function calculateRunwayDays(float $currentBalance, float $avgMonthlyBurn): ?int
    {
        if ($avgMonthlyBurn <= 0) {
            return null; // Infinite runway
        }

        $dailyBurn = $avgMonthlyBurn / 30;
        return (int) floor($currentBalance / $dailyBurn);
    }

    /**
     * Trend mensili ultimi 12 mesi
     */
    private function getMonthlyTrends(): array
    {
        $trends = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();
            
            $revenue = Invoice::whereBetween('data_emissione', [$monthStart, $monthEnd])->sum('totale');
            $costs = \App\Models\SupplierInvoice::whereBetween('data_emissione', [$monthStart, $monthEnd])->sum('totale');
            $deliveries = Delivery::whereBetween('delivery_date', [$monthStart, $monthEnd])->count();
            $newClients = Client::whereBetween('created_at', [$monthStart, $monthEnd])->count();

            $trends[] = [
                'month' => $monthStart->format('Y-m'),
                'revenue' => round($revenue, 2),
                'costs' => round($costs, 2),
                'net_income' => round($revenue - $costs, 2),
                'deliveries' => $deliveries,
                'new_clients' => $newClients,
            ];
        }

        return $trends;
    }

    /**
     * Alert business automatici
     */
    private function getBusinessAlerts(): array
    {
        $alerts = [];

        // Fatture scadute da oltre 30 giorni
        $overdueInvoices = Invoice::where('payment_status', '!=', 'pagata')
            ->where('data_scadenza', '<', now()->subDays(30))
            ->count();
        
        if ($overdueInvoices > 0) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'receivables',
                'message' => "{$overdueInvoices} fatture scadute da oltre 30 giorni",
                'action' => 'Sollecitare i clienti',
            ];
        }

        // Debiti in scadenza questa settimana
        $dueSoon = \App\Models\SupplierInvoice::where('payment_status', '!=', 'pagata')
            ->whereBetween('data_scadenza', [now(), now()->addDays(7)])
            ->sum('totale');

        if ($dueSoon > 0) {
            $alerts[] = [
                'type' => 'info',
                'category' => 'payables',
                'message' => "€" . number_format($dueSoon, 2) . " in scadenza questa settimana",
                'action' => 'Verificare liquidità',
            ];
        }

        // Contratti in scadenza
        $expiringContracts = \App\Models\Contract::where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(30)])
            ->count();

        if ($expiringContracts > 0) {
            $alerts[] = [
                'type' => 'info',
                'category' => 'contracts',
                'message' => "{$expiringContracts} contratti in scadenza nei prossimi 30 giorni",
                'action' => 'Avviare rinnovi',
            ];
        }

        // Transazioni non riconciliate
        $unreconciled = BankTransaction::where('is_reconciled', false)->count();
        
        if ($unreconciled > 20) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'accounting',
                'message' => "{$unreconciled} transazioni bancarie non riconciliate",
                'action' => 'Avviare riconciliazione',
            ];
        }

        return $alerts;
    }

    /**
     * Helper: calcola aging bucket
     */
    private function getAgingBucket($invoices, int $minDays, int $maxDays): array
    {
        $filtered = $invoices->filter(function($invoice) use ($minDays, $maxDays) {
            if (!$invoice->data_scadenza) {
                $dueDate = Carbon::parse($invoice->data_emissione)->addDays(30);
            } else {
                $dueDate = Carbon::parse($invoice->data_scadenza);
            }
            $daysOverdue = now()->diffInDays($dueDate, false);
            return $daysOverdue >= $minDays && $daysOverdue <= $maxDays;
        });

        return [
            'count' => $filtered->count(),
            'amount' => $filtered->sum('totale'),
        ];
    }

    /**
     * Helper: periodo precedente
     */
    private function getPreviousPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $duration = $startDate->diffInDays($endDate);
        return [
            $startDate->copy()->subDays($duration + 1),
            $endDate->copy()->subDays($duration + 1),
        ];
    }
}
