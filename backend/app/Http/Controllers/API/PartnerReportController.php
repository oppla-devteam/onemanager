<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\PartnerReportService;
use App\Mail\PartnerMonthlyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PartnerReportController extends Controller
{
    public function __construct(
        private PartnerReportService $reportService
    ) {}

    /**
     * Preview report data for a partner (JSON)
     */
    public function preview(Request $request, int $clientId)
    {
        $client = Client::findOrFail($clientId);

        $year = $request->input('year', now()->subMonth()->year);
        $month = $request->input('month', now()->subMonth()->month);

        $report = $this->reportService->generateMonthlyReport($client, (int) $year, (int) $month);

        return response()->json($report);
    }

    /**
     * Send monthly report email to a specific partner
     */
    public function send(Request $request, int $clientId)
    {
        $client = Client::findOrFail($clientId);

        if (!$client->email) {
            return response()->json([
                'message' => 'Il cliente non ha un indirizzo email configurato.',
            ], 422);
        }

        $year = $request->input('year', now()->subMonth()->year);
        $month = $request->input('month', now()->subMonth()->month);

        $report = $this->reportService->generateMonthlyReport($client, (int) $year, (int) $month);

        Mail::to($client->email)->send(new PartnerMonthlyReport($report));

        return response()->json([
            'message' => 'Report mensile inviato con successo a ' . $client->email,
            'period' => $report['period']['month_name'],
        ]);
    }
}
