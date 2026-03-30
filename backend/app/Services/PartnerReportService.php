<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Order;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\PartnerCommission;
use App\Models\PosOrder;
use App\Models\UpsellingSale;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PartnerReportService
{
    /**
     * Generate monthly report data for a partner
     */
    public function generateMonthlyReport(Client $partner, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $periodMonth = sprintf('%04d-%02d', $year, $month);

        // Previous month for growth comparison
        $prevStartDate = Carbon::create($year, $month, 1)->subMonth()->startOfMonth();
        $prevEndDate = Carbon::create($year, $month, 1)->subMonth()->endOfMonth();
        $prevPeriodMonth = $prevStartDate->format('Y-m');

        // 1. Orders metrics
        $orders = Order::where('client_id', $partner->id)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->get();

        $completedOrders = $orders->where('status', 'Completed');
        $cancelledOrders = $orders->whereIn('status', ['Rejected', 'CancelledByCustomer']);

        $ordersStats = [
            'total_count' => $orders->count(),
            'total_amount' => $orders->sum('total_amount') / 100,
            'average_order_value' => $orders->count() > 0 ? $orders->avg('total_amount') / 100 : 0,
            'completed_count' => $completedOrders->count(),
            'cancelled_count' => $cancelledOrders->count(),
            'completion_rate' => $orders->count() > 0
                ? round(($completedOrders->count() / $orders->count()) * 100, 1)
                : 0,
        ];

        // Top 3 days by order count
        $topDays = $orders->groupBy(fn ($o) => Carbon::parse($o->order_date)->format('Y-m-d'))
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->take(3)
            ->map(fn ($count, $date) => [
                'date' => Carbon::parse($date)->locale('it')->isoFormat('ddd D MMM'),
                'count' => $count,
            ])
            ->values()
            ->toArray();

        // 2. Deliveries metrics
        $deliveries = Delivery::where('client_id', $partner->id)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->get();

        $completedDeliveries = $deliveries->where('status', 'completata');

        $deliveriesStats = [
            'total_count' => $deliveries->count(),
            'total_distance_km' => round($deliveries->sum('distance_km'), 1),
            'average_distance_km' => $deliveries->count() > 0 ? round($deliveries->avg('distance_km'), 1) : 0,
            'total_delivery_fees' => $deliveries->sum('delivery_fee_total') / 100,
            'completed_count' => $completedDeliveries->count(),
            'completion_rate' => $deliveries->count() > 0
                ? round(($completedDeliveries->count() / $deliveries->count()) * 100, 1)
                : 0,
        ];

        // 3. Invoices metrics
        $invoices = Invoice::where('client_id', $partner->id)
            ->whereBetween('data_emissione', [$startDate, $endDate])
            ->get();

        $invoicesStats = [
            'total_count' => $invoices->count(),
            'total_amount' => $invoices->sum('totale'),
            'paid_count' => $invoices->where('payment_status', 'pagata')->count(),
            'paid_amount' => $invoices->where('payment_status', 'pagata')->sum('totale'),
            'unpaid_count' => $invoices->where('payment_status', 'non_pagata')->count(),
            'unpaid_amount' => $invoices->where('payment_status', 'non_pagata')->sum('totale'),
        ];

        // 4. Commissions (Stripe)
        $commissions = PartnerCommission::where('client_id', $partner->id)
            ->forPeriod($periodMonth)
            ->get();

        $commissionsStats = [
            'total_count' => $commissions->count(),
            'total_amount' => $commissions->sum('commission_amount'),
            'invoiced_count' => $commissions->where('invoiced', true)->count(),
            'not_invoiced_count' => $commissions->where('invoiced', false)->count(),
        ];

        // 5. POS Orders
        $posOrders = PosOrder::where('client_id', $partner->id)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->get();

        $posStats = [
            'total_count' => $posOrders->count(),
            'total_amount' => $posOrders->sum('total_amount'),
            'total_commission' => $posOrders->sum('commission'),
            'net_amount' => $posOrders->sum('net_amount'),
        ];

        // 6. Upselling
        $upselling = UpsellingSale::where('client_id', $partner->id)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->get();

        $upsellingStats = [
            'total_count' => $upselling->count(),
            'total_amount' => $upselling->sum('amount'),
            'total_commission' => $upselling->sum('commission'),
        ];

        // 7. Revenue breakdown
        $grossRevenue = $ordersStats['total_amount'] + $posStats['total_amount'] + $upsellingStats['total_amount'];
        $platformFees = $commissionsStats['total_amount'] + $posStats['total_commission'] + $upsellingStats['total_commission'];
        $deliveryFees = $deliveriesStats['total_delivery_fees'];

        $revenue = [
            'gross_revenue' => $grossRevenue,
            'delivery_fees' => $deliveryFees,
            'platform_fees' => $platformFees,
            'net_revenue' => $grossRevenue - $platformFees,
            'invoiced' => $invoicesStats['total_amount'],
            'collected' => $invoicesStats['paid_amount'],
            'outstanding' => $invoicesStats['unpaid_amount'],
        ];

        // 8. Growth comparison (vs previous month)
        $prevOrdersCount = Order::where('client_id', $partner->id)
            ->whereBetween('order_date', [$prevStartDate, $prevEndDate])
            ->count();

        $prevOrdersAmount = Order::where('client_id', $partner->id)
            ->whereBetween('order_date', [$prevStartDate, $prevEndDate])
            ->sum('total_amount') / 100;

        $prevDeliveriesCount = Delivery::where('client_id', $partner->id)
            ->whereBetween('order_date', [$prevStartDate, $prevEndDate])
            ->count();

        $growth = [
            'orders_count' => $prevOrdersCount > 0
                ? round((($ordersStats['total_count'] - $prevOrdersCount) / $prevOrdersCount) * 100, 1)
                : 0,
            'orders_amount' => $prevOrdersAmount > 0
                ? round((($ordersStats['total_amount'] - $prevOrdersAmount) / $prevOrdersAmount) * 100, 1)
                : 0,
            'deliveries_count' => $prevDeliveriesCount > 0
                ? round((($deliveriesStats['total_count'] - $prevDeliveriesCount) / $prevDeliveriesCount) * 100, 1)
                : 0,
        ];

        return [
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->ragione_sociale,
                'email' => $partner->email,
                'city' => $partner->citta,
            ],
            'period' => [
                'month' => $month,
                'year' => $year,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'month_name' => $startDate->locale('it')->isoFormat('MMMM YYYY'),
            ],
            'orders' => $ordersStats,
            'top_days' => $topDays,
            'deliveries' => $deliveriesStats,
            'invoices' => $invoicesStats,
            'commissions' => $commissionsStats,
            'pos' => $posStats,
            'upselling' => $upsellingStats,
            'revenue' => $revenue,
            'growth' => $growth,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate reports for all active partners
     */
    public function generateAllPartnerReports(int $year, int $month): array
    {
        $partners = Client::where('tipo', 'partner_oppla')
            ->where('is_active', true)
            ->get();

        $reports = [];
        foreach ($partners as $partner) {
            $reports[] = $this->generateMonthlyReport($partner, $year, $month);
        }

        return $reports;
    }
}
