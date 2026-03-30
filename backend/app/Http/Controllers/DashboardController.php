<?php

namespace App\Http\Controllers;

use App\Services\DashboardKPIService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardKPIService $kpiService
    ) {}

    /**
     * Get unified dashboard with all KPIs
     */
    public function getUnifiedDashboard(Request $request)
    {
        $period = $request->input('period', 'month');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');

        [$startDate, $endDate] = $this->getDateRange($period, $customStart, $customEnd);

        $dashboard = $this->kpiService->getUnifiedDashboard($startDate, $endDate);

        return response()->json($dashboard);
    }

    /**
     * Get economic dashboard KPIs
     */
    public function getEconomicKPIs(Request $request)
    {
        $period = $request->input('period', 'month'); // month, quarter, year, custom
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');

        [$startDate, $endDate] = $this->getDateRange($period, $customStart, $customEnd);

        $kpis = $this->kpiService->getEconomicKPIs($startDate, $endDate);

        return response()->json([
            'period' => [
                'type' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'kpis' => $kpis,
        ]);
    }

    /**
     * Get date range based on period
     */
    private function getDateRange(string $period, ?string $customStart, ?string $customEnd): array
    {
        return match($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [
                Carbon::parse($customStart ?? now()->subMonth()),
                Carbon::parse($customEnd ?? now())
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}
