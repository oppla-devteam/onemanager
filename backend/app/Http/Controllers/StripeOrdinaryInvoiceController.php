<?php

namespace App\Http\Controllers;

use App\Services\StripeOrdinaryInvoicingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeOrdinaryInvoiceController extends Controller
{
    protected $invoicingService;

    public function __construct(StripeOrdinaryInvoicingService $invoicingService)
    {
        $this->invoicingService = $invoicingService;
    }

    /**
     * Pre-genera anteprima fatture ordinarie Stripe (dal 20 al 1° mese successivo)
     * 
     * @param int $year
     * @param int $month
     * @return \Illuminate\Http\JsonResponse
     */
    public function pregenerate(int $year, int $month)
    {
        try {
            Log::info("Pre-generazione fatture ordinarie Stripe", [
                'year' => $year,
                'month' => $month
            ]);

            $previews = $this->invoicingService->pregenerateOrdinaryInvoices($year, $month);

            return response()->json([
                'success' => true,
                'message' => 'Preview generata con successo',
                'data' => [
                    'previews' => $previews,
                    'total_invoices' => count($previews),
                    'period' => sprintf('%02d/%d', $month, $year)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Errore pre-generazione fatture ordinarie", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la pre-generazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera tutte le fatture ordinarie Stripe per il periodo
     * 
     * @param int $year
     * @param int $month
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(int $year, int $month)
    {
        try {
            Log::info("Generazione fatture ordinarie Stripe", [
                'year' => $year,
                'month' => $month
            ]);

            $result = $this->invoicingService->generateOrdinaryInvoices($year, $month);

            return response()->json([
                'success' => true,
                'message' => sprintf('Generate %d fatture ordinarie', $result['invoices_created']),
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error("Errore generazione fatture ordinarie", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la generazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera fattura ordinaria per singolo partner
     * 
     * @param Request $request
     * @param int $year
     * @param int $month
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateSingle(Request $request, int $year, int $month)
    {
        $validated = $request->validate([
            'partner_email' => 'required|email'
        ]);

        try {
            Log::info("Generazione fattura ordinaria singola", [
                'year' => $year,
                'month' => $month,
                'partner_email' => $validated['partner_email']
            ]);

            $invoice = $this->invoicingService->generateSingleOrdinaryInvoice(
                $year,
                $month,
                $validated['partner_email']
            );

            return response()->json([
                'success' => true,
                'message' => 'Fattura generata con successo',
                'data' => [
                    'invoice' => $invoice
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Errore generazione fattura ordinaria singola", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la generazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invia tutte le fatture ordinarie generate a Fatture in Cloud
     * 
     * @param int $year
     * @param int $month
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendToFIC(int $year, int $month)
    {
        try {
            Log::info("Invio fatture ordinarie a FIC", [
                'year' => $year,
                'month' => $month
            ]);

            $result = $this->invoicingService->sendOrdinaryInvoicesToFIC($year, $month);

            return response()->json([
                'success' => true,
                'message' => sprintf('Inviate %d fatture a Fatture in Cloud', $result['sent_count']),
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error("Errore invio fatture ordinarie a FIC", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'invio: ' . $e->getMessage()
            ], 500);
        }
    }
}
