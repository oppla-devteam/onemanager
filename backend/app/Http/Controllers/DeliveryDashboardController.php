<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\Client;
use App\Models\Rider;
use App\Services\TookanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryDashboardController extends Controller
{
    public function __construct(
        private TookanService $tookanService
    ) {}

    /**
     * Get real-time delivery operations dashboard data
     */
    public function getOperationalKPIs(Request $request)
    {
        $today = Carbon::today();
        $now = Carbon::now();

        // === KPI CONSEGNE OGGI ===
        $deliveriesToday = Delivery::whereDate('order_date', $today)->get();
        $todayStats = [
            'total' => $deliveriesToday->count(),
            'completed' => $deliveriesToday->where('status', 'completata')->count(),
            'in_progress' => $deliveriesToday->whereIn('status', ['in_attesa', 'assegnata', 'in_consegna'])->count(),
            'pending' => $deliveriesToday->where('status', 'in_attesa')->count(),
            'cancelled' => $deliveriesToday->where('status', 'annullata')->count(),
        ];
        $todayStats['completion_rate'] = $todayStats['total'] > 0 
            ? round(($todayStats['completed'] / $todayStats['total']) * 100, 1) 
            : 0;

        // === REVENUE OGGI ===
        $todayRevenue = [
            'delivery_fees' => $deliveriesToday->sum('delivery_fee_total'),
            'order_amounts' => $deliveriesToday->sum('order_amount'),
            'oppla_fees' => $deliveriesToday->sum('oppla_fee'),
            'distance_fees' => $deliveriesToday->sum('delivery_fee_distance'),
            'total_km' => $deliveriesToday->sum('distance_km'),
        ];

        // === TREND SETTIMANALE ===
        $weekStart = Carbon::now()->startOfWeek();
        $weeklyDeliveries = Delivery::whereBetween('order_date', [$weekStart, $now])
            ->selectRaw('DATE(order_date) as date, COUNT(*) as count, SUM(delivery_fee_total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $weeklyTrend = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i)->format('Y-m-d');
            $dayData = $weeklyDeliveries->firstWhere('date', $date);
            $weeklyTrend[] = [
                'date' => $date,
                'day_name' => Carbon::parse($date)->locale('it')->isoFormat('ddd'),
                'count' => $dayData?->count ?? 0,
                'revenue' => $dayData?->revenue ?? 0,
            ];
        }

        // === TOP RISTORANTI OGGI ===
        $topRestaurants = Delivery::whereDate('order_date', $today)
            ->select('client_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(delivery_fee_total) as total_fees'))
            ->with('client:id,company_name,business_name')
            ->groupBy('client_id')
            ->orderByDesc('order_count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->client?->company_name ?? $item->client?->business_name ?? 'N/A',
                    'orders' => $item->order_count,
                    'fees' => $item->total_fees,
                ];
            });

        // === DISTRIBUZIONE PER ZONA ===
        $zoneDistribution = Delivery::whereDate('order_date', $today)
            ->select('delivery_address', DB::raw('COUNT(*) as count'))
            ->groupBy('delivery_address')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // === CONSEGNE PER ORA (OGGI) ===
        $hourlyDistribution = Delivery::whereDate('order_date', $today)
            ->selectRaw('HOUR(order_date) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->hour => $item->count];
            });

        $hourlyData = [];
        for ($h = 8; $h <= 23; $h++) {
            $hourlyData[] = [
                'hour' => sprintf('%02d:00', $h),
                'count' => $hourlyDistribution[$h] ?? 0,
            ];
        }

        // === TEMPI MEDI (ultima settimana) ===
        $avgTimes = Delivery::whereBetween('order_date', [$weekStart, $now])
            ->whereNotNull('pickup_time')
            ->whereNotNull('delivery_time')
            ->selectRaw('
                AVG(TIMESTAMPDIFF(MINUTE, order_date, pickup_time)) as avg_pickup_time,
                AVG(TIMESTAMPDIFF(MINUTE, pickup_time, delivery_time)) as avg_delivery_time,
                AVG(TIMESTAMPDIFF(MINUTE, order_date, delivery_time)) as avg_total_time
            ')
            ->first();

        // === CONFRONTO CON IERI ===
        $yesterday = Carbon::yesterday();
        $yesterdayDeliveries = Delivery::whereDate('order_date', $yesterday)->count();
        $yesterdayRevenue = Delivery::whereDate('order_date', $yesterday)->sum('delivery_fee_total');

        $comparison = [
            'deliveries_diff' => $todayStats['total'] - $yesterdayDeliveries,
            'deliveries_diff_percent' => $yesterdayDeliveries > 0 
                ? round((($todayStats['total'] - $yesterdayDeliveries) / $yesterdayDeliveries) * 100, 1) 
                : 0,
            'revenue_diff' => $todayRevenue['delivery_fees'] - $yesterdayRevenue,
            'revenue_diff_percent' => $yesterdayRevenue > 0 
                ? round((($todayRevenue['delivery_fees'] - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1) 
                : 0,
        ];

        // === METODI DI PAGAMENTO OGGI ===
        $paymentMethods = Delivery::whereDate('order_date', $today)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(order_amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->payment_method ?? 'non_specificato' => [
                    'count' => $item->count,
                    'total' => $item->total,
                ]];
            });

        // === PARTNER LOGISTICO vs NORMALE ===
        $partnerLogistico = Delivery::whereDate('order_date', $today)
            ->where('is_partner_logistico', true)
            ->count();
        $deliveryNormale = $todayStats['total'] - $partnerLogistico;

        // === RIDER/AGENTS FROM LOCAL DATABASE (WITH TOOKAN FALLBACK) ===
        $lastSyncTime = Rider::max('last_synced_at');
        $isStale = !$lastSyncTime || Carbon::parse($lastSyncTime)->lt(now()->subMinutes(10));
        $riderCount = Rider::count();

        // If no data or stale, try to fetch from Tookan API directly
        if ($riderCount === 0 || $isStale) {
            $tookanSummary = $this->tookanService->getAgentsSummary();
            if (!isset($tookanSummary['error'])) {
                $riders = [
                    'total' => $tookanSummary['total'],
                    'available' => $tookanSummary['available'],
                    'busy' => $tookanSummary['busy'],
                    'offline' => $tookanSummary['offline'],
                    'agents' => array_slice($tookanSummary['agents'], 0, 8),
                    'last_synced_at' => now()->toIso8601String(),
                    'using_fallback' => true,
                ];
            } else {
                // Fallback failed, use local data (even if stale)
                $riders = [
                    'total' => Rider::count(),
                    'available' => Rider::available()->count(),
                    'busy' => Rider::busy()->count(),
                    'offline' => Rider::offline()->count(),
                    'agents' => Rider::orderBy('status_code')
                                     ->limit(8)
                                     ->get()
                                     ->map(function($rider) {
                                         return [
                                             'fleet_id' => $rider->fleet_id,
                                             'name' => $rider->name,
                                             'status' => $rider->status,
                                             'transport_type' => $rider->transport_type,
                                         ];
                                     })
                                     ->toArray(),
                    'last_synced_at' => $lastSyncTime,
                    'error' => $tookanSummary['error'],
                ];
            }
        } else {
            // Use fresh local data
            $riders = [
                'total' => Rider::count(),
                'available' => Rider::available()->count(),
                'busy' => Rider::busy()->count(),
                'offline' => Rider::offline()->count(),
                'agents' => Rider::orderBy('status_code')
                                 ->limit(8)
                                 ->get()
                                 ->map(function($rider) {
                                     return [
                                         'fleet_id' => $rider->fleet_id,
                                         'name' => $rider->name,
                                         'status' => $rider->status,
                                         'transport_type' => $rider->transport_type,
                                     ];
                                 })
                                 ->toArray(),
                'last_synced_at' => $lastSyncTime,
            ];
        }

        // Tasks still come from Tookan (not cached locally)
        $tookanTasks = $this->tookanService->getTodayTasksSummary();

        return response()->json([
            'timestamp' => $now->toIso8601String(),
            'today' => $todayStats,
            'revenue' => $todayRevenue,
            'weekly_trend' => $weeklyTrend,
            'top_restaurants' => $topRestaurants,
            'hourly_distribution' => $hourlyData,
            'avg_times' => [
                'pickup' => round($avgTimes?->avg_pickup_time ?? 0),
                'delivery' => round($avgTimes?->avg_delivery_time ?? 0),
                'total' => round($avgTimes?->avg_total_time ?? 0),
            ],
            'comparison' => $comparison,
            'payment_methods' => $paymentMethods,
            'delivery_types' => [
                'partner_logistico' => $partnerLogistico,
                'normale' => $deliveryNormale,
            ],
            'riders' => $riders,
            'tookan_tasks' => $tookanTasks,
        ]);
    }

    /**
     * Get riders status from Tookan
     */
    public function getRidersStatus()
    {
        $summary = $this->tookanService->getAgentsSummary();
        
        return response()->json([
            'success' => !isset($summary['error']),
            'data' => $summary,
        ]);
    }

    /**
     * Get monthly summary for delivery operations
     */
    public function getMonthlySummary(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $deliveries = Delivery::whereBetween('order_date', [$startDate, $endDate])->get();

        // Daily breakdown
        $dailyBreakdown = Delivery::whereBetween('order_date', [$startDate, $endDate])
            ->selectRaw('DATE(order_date) as date, COUNT(*) as count, SUM(delivery_fee_total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top clients
        $topClients = Delivery::whereBetween('order_date', [$startDate, $endDate])
            ->select('client_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(delivery_fee_total) as total_fees'))
            ->with('client:id,company_name,business_name')
            ->groupBy('client_id')
            ->orderByDesc('order_count')
            ->limit(10)
            ->get();

        return response()->json([
            'period' => [
                'month' => $month,
                'year' => $year,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_deliveries' => $deliveries->count(),
                'completed' => $deliveries->where('status', 'completata')->count(),
                'total_revenue' => $deliveries->sum('delivery_fee_total'),
                'total_km' => $deliveries->sum('distance_km'),
                'avg_per_day' => round($deliveries->count() / $startDate->daysInMonth, 1),
            ],
            'daily_breakdown' => $dailyBreakdown,
            'top_clients' => $topClients->map(function ($item) {
                return [
                    'name' => $item->client?->company_name ?? $item->client?->business_name ?? 'N/A',
                    'orders' => $item->order_count,
                    'fees' => $item->total_fees,
                ];
            }),
        ]);
    }
}
