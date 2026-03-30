<?php

namespace App\Http\Controllers;

use App\Services\StripeReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StripeReportExport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StripeReportController extends Controller
{
    protected $stripeReportService;

    public function __construct(StripeReportService $stripeReportService)
    {
        $this->stripeReportService = $stripeReportService;
    }

    /**
     * Ottieni report Stripe per un mese specifico
     * GET /api/stripe-report/{year}/{month}
     */
    public function getMonthlyReport(int $year, int $month): JsonResponse
    {
        try {
            // Validazione
            if ($month < 1 || $month > 12) {
                return response()->json(['error' => 'Mese non valido'], 400);
            }

            $transactions = $this->stripeReportService->getMonthlyTransactions($year, $month);
            $totals = $this->stripeReportService->calculateTotals($transactions);
            $restaurantFees = $this->stripeReportService->getRestaurantFees($year, $month);

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions,
                    'totals' => $totals,
                    'restaurant_fees' => $restaurantFees,
                    'needs_normalization' => abs($totals['differenza']) > 0.01
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Errore recupero report Stripe: ' . $e->getMessage());
            return response()->json(['error' => 'Errore recupero report'], 500);
        }
    }

    /**
     * Normalizza automaticamente le transazioni
     * POST /api/stripe-report/{year}/{month}/normalize
     */
    public function normalizeTransactions(int $year, int $month): JsonResponse
    {
        try {
            $transactions = $this->stripeReportService->getMonthlyTransactions($year, $month);
            $normalized = $this->stripeReportService->normalizeTransactions($transactions);
            
            // Salva solo le transazioni modificate nel database e raccogli i dettagli
            $correctionCount = 0;
            $corrections = [];
            
            foreach ($normalized as $transaction) {
                if (isset($transaction->auto_corrected) && $transaction->auto_corrected) {
                    $success = $this->stripeReportService->updateTransactionType(
                        $transaction->transaction_id,
                        $transaction->type
                    );
                    if ($success) {
                        $correctionCount++;
                        // Raccogli dettagli della correzione
                        $corrections[] = [
                            'transaction_id' => $transaction->transaction_id,
                            'old_type' => $transaction->correction_old_type ?? 'sconosciuto',
                            'new_type' => $transaction->type,
                            'amount' => $transaction->amount,
                            'date' => $transaction->created_at,
                            'description' => $transaction->description ?? '',
                            'reason' => $transaction->correction_reason ?? 'Normalizzazione automatica'
                        ];
                    }
                }
            }

            // Ricalcola totali con i dati normalizzati
            $totals = $this->stripeReportService->calculateTotals($normalized);

            return response()->json([
                'success' => true,
                'message' => 'Transazioni normalizzate con successo',
                'data' => [
                    'transactions' => $normalized,
                    'totals' => $totals,
                    'corrections_count' => $correctionCount,
                    'corrections' => $corrections
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Errore normalizzazione transazioni: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Errore normalizzazione: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Aggiorna manualmente il tipo di una transazione
     * PUT /api/stripe-report/transaction/{id}
     */
    public function updateTransactionType(Request $request, string $transactionId): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:charge,transfer,payment,application_fee,stripe_fee,network_cost,coupon,refund,payout'
        ]);

        try {
            $success = $this->stripeReportService->updateTransactionType(
                $transactionId,
                $request->type
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tipo transazione aggiornato'
                ]);
            }

            return response()->json(['error' => 'Aggiornamento fallito'], 500);
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento transazione: ' . $e->getMessage());
            return response()->json(['error' => 'Errore aggiornamento'], 500);
        }
    }

    /**
     * Esporta report in Excel
     * GET /api/stripe-report/{year}/{month}/export
     */
    public function exportToExcel(int $year, int $month)
    {
        try {
            $transactions = $this->stripeReportService->getMonthlyTransactions($year, $month);
            $totals = $this->stripeReportService->calculateTotals($transactions);
            
            // Filtra le application fees dalle transazioni già caricate
            $applicationFees = collect($transactions)
                ->filter(fn($t) => $t->type === 'application_fee')
                ->values()
                ->all();

            $filename = "stripe_report_{$year}_{$month}.xlsx";

            return Excel::download(
                new StripeReportExport($transactions, $totals, $applicationFees, $year, $month),
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Errore export Excel: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Errore export: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Invia report al commercialista via email
     * POST /api/stripe-report/{year}/{month}/send
     */
    public function sendToAccountant(Request $request, int $year, int $month): JsonResponse
    {
        $request->validate([
            'email' => 'sometimes|email',
            'save_email' => 'sometimes|boolean'
        ]);

        try {
            // Ottieni email dal request o dalle impostazioni salvate
            $email = $request->email ?? $this->stripeReportService->getAccountantEmail();

            if (!$email) {
                return response()->json(['error' => 'Email commercialista non specificata'], 400);
            }

            // Salva email se richiesto
            if ($request->save_email && $request->email) {
                $this->stripeReportService->saveAccountantEmail($request->email);
            }

            // Genera Excel
            $transactions = $this->stripeReportService->getMonthlyTransactions($year, $month);
            $totals = $this->stripeReportService->calculateTotals($transactions);
            $restaurantFees = $this->stripeReportService->getRestaurantFees($year, $month);

            $filename = "stripe_report_{$year}_{$month}.xlsx";
            $export = new StripeReportExport($transactions, $totals, $restaurantFees, $year, $month);

            // Invia email
            Mail::send('emails.stripe_report', [
                'year' => $year,
                'month' => $month,
                'totals' => $totals
            ], function ($message) use ($email, $filename, $export) {
                $message->to($email)
                    ->subject('Report Stripe - ' . date('F Y'))
                    ->attachData(
                        Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX),
                        $filename,
                        ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                    );
            });

            return response()->json([
                'success' => true,
                'message' => "Report inviato con successo a {$email}"
            ]);
        } catch (\Exception $e) {
            Log::error('Errore invio email: ' . $e->getMessage());
            return response()->json(['error' => 'Errore invio email: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Ottieni/Salva email commercialista
     */
    public function accountantEmail(Request $request): JsonResponse
    {
        if ($request->isMethod('get')) {
            $email = $this->stripeReportService->getAccountantEmail();
            return response()->json(['email' => $email]);
        }

        if ($request->isMethod('post')) {
            $request->validate(['email' => 'required|email']);
            $success = $this->stripeReportService->saveAccountantEmail($request->email);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Email salvata' : 'Errore salvataggio'
            ]);
        }
    }

    /**
     * Resetta tutte le normalizzazioni Stripe
     * POST /api/stripe-report/reset
     */
    public function resetNormalizations(): JsonResponse
    {
        try {
            $result = $this->stripeReportService->resetNormalizations();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "Reset completato: {$result['reset_count']} transazioni resettate",
                    'data' => $result
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Errore sconosciuto'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Errore reset normalizzazioni: ' . $e->getMessage());
            return response()->json([
                'error' => 'Errore reset: ' . $e->getMessage()
            ], 500);
        }
    }
}
