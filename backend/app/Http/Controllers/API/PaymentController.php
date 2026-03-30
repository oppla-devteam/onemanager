<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\CsvExportTrait;
use Illuminate\Http\Request;
use App\Models\BankTransaction;
use App\Models\BankAccount;
use App\Models\Client;
use App\Models\PartnerCommission;
use App\Services\StripeService;
use App\Services\PaymentImportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    use CsvExportTrait;

    /**
     * Esporta pagamenti/transazioni in formato CSV
     */
    public function exportCsv(Request $request)
    {
        $query = BankTransaction::with('bankAccount');

        if ($request->filled('source')) $query->where('source', $request->input('source'));
        if ($request->filled('date_from')) $query->where('transaction_date', '>=', $request->input('date_from'));
        if ($request->filled('date_to')) $query->where('transaction_date', '<=', $request->input('date_to'));

        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        $data = [];
        foreach ($transactions as $t) {
            $data[] = [
                'ID' => $t->id,
                'Data' => $t->transaction_date ? $t->transaction_date->format('d/m/Y') : '',
                'Fonte' => $t->source ?? '',
                'Tipo' => $t->type ?? '',
                'Importo (€)' => number_format($t->amount ?? 0, 2, ',', '.'),
                'Commissione (€)' => number_format($t->fee ?? 0, 2, ',', '.'),
                'Netto (€)' => number_format($t->net_amount ?? 0, 2, ',', '.'),
                'Valuta' => $t->currency ?? 'EUR',
                'Descrizione' => $t->descrizione ?? '',
                'Causale' => $t->causale ?? '',
                'Beneficiario' => $t->beneficiario ?? '',
                'Categoria' => $t->category ?? '',
                'Riconciliata' => $t->is_reconciled ? 'Sì' : 'No',
                'Conto' => $t->bankAccount?->name ?? '',
                'Note' => $t->note ?? '',
            ];
        }

        return $this->streamCsv($data, 'pagamenti_' . date('Y-m-d_His') . '.csv');
    }

    /**
     * Importa transazioni da Stripe
     */
    public function importStripe(Request $request)
    {
        try {
            // Verifica che le chiavi Stripe siano configurate
            $stripeSecret = config('services.stripe.secret');
            if (empty($stripeSecret) || 
                str_contains($stripeSecret, 'your_stripe_secret_key') ||
                str_contains($stripeSecret, 'INSERISCI_QUI') ||
                $stripeSecret === 'sk_test_') {
                return response()->json([
                    'success' => false,
                    'message' => 'Chiavi API Stripe non configurate. Consulta STRIPE_SETUP.md per la configurazione.',
                    'error' => 'STRIPE_NOT_CONFIGURED',
                ], 422);
            }

            $validated = $request->validate([
                'days' => 'nullable|integer|min:1|max:365',
                'force' => 'nullable|boolean',
            ]);

            $days = $validated['days'] ?? 30;
            $force = $validated['force'] ?? false;
            $endDate = now();
            $startDate = now()->subDays($days);

            // Se force=true, svuota prima tutte le transazioni Stripe dal database locale
            if ($force) {
                Log::info('[StripeImport] FORCE SYNC - Eliminazione transazioni Stripe esistenti dal DB locale');
                $deletedCount = \DB::table('bank_transactions')
                    ->where('source', 'stripe')
                    ->delete();
                Log::info('[StripeImport] Eliminate ' . $deletedCount . ' transazioni Stripe dal database locale');
            }

            Log::info('[StripeImport] Inizio importazione transazioni', [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'force_full_sync' => $force,
            ]);

            $stripeService = new StripeService();
            $result = $stripeService->importTransactions($startDate, $endDate, $force);

            // Sync Application Fees (commissioni riscosse per fatturazione differita)
            $feesResult = ['imported' => 0, 'skipped' => 0, 'errors' => 0];
            try {
                $feesResult = $stripeService->syncApplicationFees($startDate, $endDate);
                Log::info('[StripeImport] Application fees sincronizzate', $feesResult);
            } catch (\Exception $e) {
                Log::error('[StripeImport] Errore sync application fees: ' . $e->getMessage());
                $feesResult['error'] = $e->getMessage();
            }

            return response()->json([
                'success' => true,
                'message' => "Importate {$result['imported']} transazioni e {$feesResult['imported']} commissioni da Stripe",
                'data' => array_merge($result, ['application_fees' => $feesResult]),
            ]);

        } catch (\Exception $e) {
            Log::error('[StripeImport] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'importazione',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Statistiche pagamenti
     */
    public function stats(Request $request)
    {
        try {
            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();

            // Mese corrente - Entrate
            $currentIncome = BankTransaction::where('type', 'entrata')
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            // Mese corrente - Uscite
            $currentExpenses = BankTransaction::whereIn('type', ['uscita', 'addebito', 'carta'])
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            // Bonifici in uscita (prelievi)
            $withdrawals = BankTransaction::where('type', 'bonifico')
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->where('amount', '<', 0)
                ->sum('amount');

            // Transazioni non riconciliate
            $pending = BankTransaction::where('is_reconciled', false)
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->count();

            // Mese scorso per confronto
            $lastMonthIncome = BankTransaction::where('type', 'entrata')
                ->whereBetween('transaction_date', [
                    $startOfMonth->copy()->subMonth(),
                    $endOfMonth->copy()->subMonth()
                ])
                ->sum('amount');

            // Calcola variazione
            $incomeChange = 0;
            if ($lastMonthIncome > 0) {
                $incomeChange = round((($currentIncome - $lastMonthIncome) / $lastMonthIncome) * 100, 1);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'income' => $currentIncome,
                    'expenses' => abs($currentExpenses + $withdrawals),
                    'pending' => $pending,
                    'failed' => 0, // Non tracciato in questa struttura
                    'income_change' => $incomeChange,
                    'last_sync' => BankTransaction::max('updated_at'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Statistiche aggregate per dashboard con disambiguazione Stripe
     */
    public function dashboardStats(Request $request)
    {
        try {
            $startDate = $request->input('start_date', now()->startOfYear());
            $endDate = $request->input('end_date', now());

            // Aggregazione per fonte
            $bySource = BankTransaction::selectRaw('
                    source,
                    COUNT(*) as count,
                    SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_expenses,
                    SUM(amount) as net_amount
                ')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->groupBy('source')
                ->get()
                ->keyBy('source');

            // Analisi Stripe con destinazioni trasferimenti
            $stripeDestinations = BankTransaction::where('source', 'stripe')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->get()
                ->map(function ($transaction) {
                    $sourceData = $transaction->source_data ? json_decode($transaction->source_data, true) : null;
                    $destination = $sourceData['transfer_destination'] ?? 'unknown';
                    $restaurantName = $sourceData['restaurant_name'] ?? null;
                    
                    return [
                        'id' => $transaction->id,
                        'date' => $transaction->transaction_date,
                        'amount' => $transaction->amount,
                        'type' => $transaction->type,
                        'destination' => $destination,
                        'restaurant_name' => $restaurantName,
                        'beneficiary' => $transaction->beneficiario,
                        'description' => $transaction->descrizione,
                        'is_oppla_client' => !empty($restaurantName), // Cliente Oppla se ha restaurant_name
                    ];
                })
                ->groupBy('destination');

            // Separa pagamenti Oppla da pagamenti diretti
            $opplaClientPayments = BankTransaction::where('source', 'stripe')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->get()
                ->filter(function ($transaction) {
                    $sourceData = $transaction->source_data ? json_decode($transaction->source_data, true) : null;
                    return !empty($sourceData['restaurant_name']);
                });

            $directOpplaPayments = BankTransaction::where('source', 'stripe')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->get()
                ->filter(function ($transaction) {
                    $sourceData = $transaction->source_data ? json_decode($transaction->source_data, true) : null;
                    return empty($sourceData['restaurant_name']);
                });

            // Aggregazione per mese
            $monthlyData = [];
            for ($i = 0; $i < 12; $i++) {
                $monthStart = now()->startOfYear()->addMonths($i);
                $monthEnd = $monthStart->copy()->endOfMonth();
                
                $monthlyIncome = BankTransaction::where('type', 'entrata')
                    ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                    ->sum('amount');
                
                $monthlyExpenses = BankTransaction::whereIn('type', ['uscita', 'addebito', 'carta', 'bonifico'])
                    ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                    ->where('amount', '<', 0)
                    ->sum('amount');
                
                $monthlyTransactions = BankTransaction::whereBetween('transaction_date', [$monthStart, $monthEnd])
                    ->count();

                $monthlyData[] = [
                    'month' => $monthStart->format('Y-m'),
                    'month_name' => $monthStart->locale('it')->isoFormat('MMMM'),
                    'income' => $monthlyIncome,
                    'expenses' => abs($monthlyExpenses),
                    'net' => $monthlyIncome + $monthlyExpenses,
                    'transactions' => $monthlyTransactions,
                ];
            }

            // Raggruppamento per beneficiari Stripe (clienti Oppla)
            $opplaClientsRevenue = [];
            foreach ($opplaClientPayments as $payment) {
                $sourceData = $payment->source_data ? json_decode($payment->source_data, true) : null;
                $restaurantName = $sourceData['restaurant_name'] ?? 'Unknown';
                
                if (!isset($opplaClientsRevenue[$restaurantName])) {
                    $opplaClientsRevenue[$restaurantName] = [
                        'restaurant_name' => $restaurantName,
                        'total_amount' => 0,
                        'transaction_count' => 0,
                        'first_payment' => $payment->transaction_date,
                        'last_payment' => $payment->transaction_date,
                    ];
                }
                
                $opplaClientsRevenue[$restaurantName]['total_amount'] += $payment->amount;
                $opplaClientsRevenue[$restaurantName]['transaction_count']++;
                
                if ($payment->transaction_date < $opplaClientsRevenue[$restaurantName]['first_payment']) {
                    $opplaClientsRevenue[$restaurantName]['first_payment'] = $payment->transaction_date;
                }
                if ($payment->transaction_date > $opplaClientsRevenue[$restaurantName]['last_payment']) {
                    $opplaClientsRevenue[$restaurantName]['last_payment'] = $payment->transaction_date;
                }
            }

            // Ordina per fatturato decrescente
            $opplaClientsRevenue = collect($opplaClientsRevenue)
                ->sortByDesc('total_amount')
                ->values()
                ->all();

            return response()->json([
                'success' => true,
                'data' => [
                    'by_source' => $bySource,
                    'stripe_destinations' => $stripeDestinations->map(function ($items, $destination) {
                        return [
                            'destination' => $destination,
                            'count' => $items->count(),
                            'total_amount' => $items->sum('amount'),
                            'avg_amount' => $items->avg('amount'),
                            'is_oppla_clients' => $items->first()['is_oppla_client'] ?? false,
                        ];
                    })->values(),
                    'oppla_summary' => [
                        'client_payments' => [
                            'count' => $opplaClientPayments->count(),
                            'total' => $opplaClientPayments->sum('amount'),
                            'avg' => $opplaClientPayments->avg('amount'),
                        ],
                        'direct_payments' => [
                            'count' => $directOpplaPayments->count(),
                            'total' => $directOpplaPayments->sum('amount'),
                            'avg' => $directOpplaPayments->avg('amount'),
                        ],
                    ],
                    'oppla_clients_revenue' => $opplaClientsRevenue,
                    'monthly_data' => $monthlyData,
                    'total_summary' => [
                        'total_transactions' => BankTransaction::whereBetween('transaction_date', [$startDate, $endDate])->count(),
                        'total_income' => BankTransaction::where('type', 'entrata')->whereBetween('transaction_date', [$startDate, $endDate])->sum('amount'),
                        'total_expenses' => abs(BankTransaction::whereIn('type', ['uscita', 'addebito', 'carta'])->whereBetween('transaction_date', [$startDate, $endDate])->sum('amount')),
                        'net_amount' => BankTransaction::whereBetween('transaction_date', [$startDate, $endDate])->sum('amount'),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[DashboardStats] Errore: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function index(Request $request)
    {
        $query = BankTransaction::with('bankAccount');

        Log::info('[PaymentController::index] Fetching payments', [
            'total_count' => BankTransaction::count(),
            'sources' => BankTransaction::selectRaw('source, COUNT(*) as count')->groupBy('source')->get()->toArray(),
        ]);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('descrizione', 'like', "%{$search}%")
                  ->orWhere('beneficiario', 'like', "%{$search}%")
                  ->orWhere('causale', 'like', "%{$search}%");
            });
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $sortBy = $request->get('sort_by', 'transaction_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginazione - default 5000 per includere tutte le transazioni (3149 totali al momento)
        $perPage = $request->get('per_page', 5000);
        $result = $query->paginate($perPage);
        
        Log::info('[PaymentController::index] Returning results', [
            'count' => $result->count(),
            'total' => $result->total(),
            'first_item_source' => $result->first()?->source ?? 'N/A',
            'first_item_type' => $result->first()?->type ?? 'N/A',
        ]);
        
        return response()->json(['success' => true, 'data' => $result]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'transaction_date' => 'required|date',
            'value_date' => 'nullable|date',
            'type' => 'required|in:entrata,uscita,bonifico,addebito,carta,altro',
            'amount' => 'required|numeric',
            'descrizione' => 'required|string',
            'beneficiario' => 'nullable|string',
            'causale' => 'nullable|string',
            'category' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $transaction = BankTransaction::create($validated);

        return response()->json($transaction->load('bankAccount'), 201);
    }

    public function show(string $id)
    {
        $transaction = BankTransaction::with('bankAccount')->findOrFail($id);
        return response()->json($transaction);
    }

    public function update(Request $request, string $id)
    {
        $transaction = BankTransaction::findOrFail($id);

        $validated = $request->validate([
            'transaction_date' => 'sometimes|date',
            'value_date' => 'sometimes|date',
            'type' => 'sometimes|in:entrata,uscita',
            'amount' => 'sometimes|numeric',
            'description' => 'sometimes|string',
            'beneficiary' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        $transaction->update($validated);
        return response()->json($transaction->load('bankAccount'));
    }

    public function destroy(string $id)
    {
        $transaction = BankTransaction::findOrFail($id);
        $transaction->delete();
        return response()->json(['message' => 'Transazione eliminata con successo']);
    }

    /**
     * Verifica lo stato della configurazione Stripe
     */
    public function checkStripeStatus()
    {
        try {
            $stripeSecret = config('services.stripe.secret');
            
            Log::info('[checkStripeStatus] Checking Stripe configuration', [
                'has_key' => !empty($stripeSecret),
                'key_length' => $stripeSecret ? strlen($stripeSecret) : 0,
            ]);
            
            // Controlla se la chiave è configurata correttamente
            $isConfigured = !empty($stripeSecret) && 
                           !str_contains($stripeSecret, 'your_stripe_secret_key') &&
                           !str_contains($stripeSecret, 'INSERISCI_QUI') &&
                           $stripeSecret !== 'sk_test_';

            if (!$isConfigured) {
                return response()->json([
                    'configured' => false,
                    'connected' => false,
                    'message' => 'Chiavi API Stripe non configurate',
                    'help' => 'Consulta il file STRIPE_SETUP.md nella root del progetto per le istruzioni di configurazione.',
                ]);
            }

            // Tenta di connettersi a Stripe per verificare la chiave
            try {
                $stripeService = new StripeService();
                $balance = $stripeService->getBalance();
                
                return response()->json([
                    'configured' => true,
                    'connected' => true,
                    'message' => 'Stripe configurato e connesso correttamente',
                    'balance' => $balance,
                ]);
            } catch (\Exception $e) {
                Log::error('[checkStripeStatus] Connection error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                return response()->json([
                    'configured' => true,
                    'connected' => false,
                    'message' => 'Chiave API configurata ma connessione fallita',
                    'error' => $e->getMessage(),
                ], 200); // Cambiato da 422 a 200 per evitare errore frontend
            }

        } catch (\Exception $e) {
            Log::error('[checkStripeStatus] Fatal error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'configured' => false,
                'connected' => false,
                'message' => 'Errore durante la verifica dello stato Stripe',
                'error' => $e->getMessage(),
            ], 200); // Cambiato da 500 a 200 per evitare errore frontend
        }
    }

    /**
     * Importa CSV da sorgenti diverse
     */
    public function importCSV(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
            'source' => 'nullable|in:bank,vivawallet,nexi,paypal', // Opzionale, se non specificato auto-rileva
        ]);

        try {
            $file = $request->file('file');
            
            // Salva temporaneamente il file
            $path = $file->storeAs('temp', uniqid() . '.csv');
            $fullPath = Storage::path($path);
            
            // Auto-rileva la sorgente se non specificata
            $importService = new PaymentImportService();
            $source = $validated['source'] ?? $importService->detectCSVSource($fullPath);
            
            if (!$source) {
                Storage::delete($path);
                return response()->json([
                    'success' => false,
                    'message' => 'Impossibile rilevare automaticamente la sorgente del CSV. Formato non riconosciuto.',
                ], 400);
            }
            
            Log::info('[ImportCSV] Detected source', ['source' => $source]);
            
            // Trova o crea bank account in base alla sorgente
            $bankAccountName = match($source) {
                'bank' => 'Conto Bancario Principale',
                'vivawallet' => 'Vivawallet Account',
                'nexi' => 'Carta Nexi',
                'paypal' => 'PayPal Account',
            };
            
            $bankAccount = BankAccount::firstOrCreate(
                ['name' => $bankAccountName],
                [
                    'bank_name' => $source === 'bank' ? 'Banca' : ucfirst($source),
                    'type' => $source,
                    'iban' => strtoupper($source) . '_MAIN',
                    'saldo_iniziale' => 0,
                    'saldo_attuale' => 0,
                    'is_active' => true,
                    'auto_sync' => false,
                ]
            );
            
            Log::info('[ImportCSV] Using bank account', [
                'id' => $bankAccount->id,
                'name' => $bankAccount->name,
                'source' => $source,
            ]);
            
            // Importa in base alla sorgente
            $result = match($source) {
                'bank' => $importService->importCRV($fullPath, $bankAccount->id),
                'vivawallet' => $importService->importVivawallet($fullPath, $bankAccount->id),
                'nexi' => $importService->importNexi($fullPath, $bankAccount->id),
                'paypal' => $importService->importPayPal($fullPath, $bankAccount->id),
            };
            
            // Cancella file temporaneo
            Storage::delete($path);
            
            Log::info('[ImportCSV] Import completed', $result);
            
            return response()->json([
                'success' => true,
                'message' => "Importazione completata da {$source}: {$result['imported']} transazioni importate, {$result['skipped']} già esistenti",
                'data' => $result,
            ]);
            
        } catch (\Exception $e) {
            Log::error('[ImportCSV] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'importazione del CSV',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aggregazione pagamenti per cliente
     */
    public function aggregateByClient(Request $request)
    {
        try {
            $filters = $request->only(['date_from', 'date_to', 'source', 'transfer_destination']);
            
            $importService = new PaymentImportService();
            $result = $importService->aggregateByClient($filters);
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
            
        } catch (\Exception $e) {
            Log::error('[AggregateByClient] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import CSV commissioni Stripe (da "Commissioni riscosse")
     */
    public function importStripeCommissions(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $path = $file->storeAs('temp', uniqid() . '_commissions.csv');
            $fullPath = Storage::path($path);

            Log::info('[ImportStripeCommissions] Inizio importazione');

            // Leggi CSV
            $csvData = [];
            if (($handle = fopen($fullPath, 'r')) !== false) {
                $headers = fgetcsv($handle, 0, ',');
                
                // Mappa headers (Stripe usa nomi italiani)
                $headerMap = [];
                foreach ($headers as $index => $header) {
                    $headerMap[strtolower(trim($header))] = $index;
                }

                while (($row = fgetcsv($handle, 0, ',')) !== false) {
                    $csvData[] = $row;
                }
                fclose($handle);
            }

            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($csvData as $rowIndex => $row) {
                try {
                    // Estrai dati dalla riga
                    $amount = isset($headerMap['importo']) ? floatval(str_replace([',', '€', ' '], ['', '', ''], $row[$headerMap['importo']])) : 0;
                    $description = isset($headerMap['descrizione']) ? trim($row[$headerMap['descrizione']]) : '';
                    $date = isset($headerMap['data']) ? $row[$headerMap['data']] : null;
                    
                    // Estrai email e account ID dalla descrizione
                    // Format: "email@example.com - acct_xxxxx"
                    preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $description, $emailMatch);
                    preg_match('/acct_[a-zA-Z0-9]+/', $description, $acctMatch);
                    
                    $email = $emailMatch[1] ?? null;
                    $stripeAccountId = $acctMatch[0] ?? null;
                    $chargeId = isset($headerMap['tipo']) ? trim($row[$headerMap['tipo']]) : null;

                    if (!$email || !$date) {
                        $skipped++;
                        continue;
                    }

                    // Parse data
                    $transactionDate = Carbon::parse($date);
                    $periodMonth = $transactionDate->format('Y-m');

                    // Cerca cliente by email
                    $client = Client::where('email', $email)->first();

                    // Controlla se già esiste
                    $exists = PartnerCommission::where('partner_email', $email)
                        ->where('transaction_date', $transactionDate)
                        ->where('commission_amount', $amount)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    // Crea record
                    PartnerCommission::create([
                        'client_id' => $client?->id,
                        'partner_email' => $email,
                        'partner_name' => $client?->ragione_sociale,
                        'stripe_account_id' => $stripeAccountId,
                        'stripe_charge_id' => $chargeId,
                        'commission_amount' => $amount,
                        'currency' => 'EUR',
                        'transaction_date' => $transactionDate,
                        'description' => $description,
                        'stripe_data' => json_encode($row),
                        'period_month' => $periodMonth,
                        'invoiced' => false,
                    ]);

                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Riga {$rowIndex}: {$e->getMessage()}";
                    Log::error("[ImportStripeCommissions] Errore riga {$rowIndex}", [
                        'error' => $e->getMessage(),
                        'row' => $row,
                    ]);
                }
            }

            Storage::delete($path);

            Log::info('[ImportStripeCommissions] Completato', [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => count($errors),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Importate {$imported} commissioni, {$skipped} già esistenti",
                'data' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => $errors,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[ImportStripeCommissions] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'importazione',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aggregazione commissioni per partner
     */
    public function aggregateCommissionsByPartner(Request $request)
    {
        try {
            $filters = $request->only(['period_month', 'date_from', 'date_to']);
            
            $query = PartnerCommission::query()
                ->select([
                    'partner_email',
                    'partner_name',
                    'client_id',
                    'period_month',
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('SUM(commission_amount) as total_commissions'),
                    DB::raw('MIN(transaction_date) as first_transaction'),
                    DB::raw('MAX(transaction_date) as last_transaction'),
                    DB::raw('MAX(invoiced) as is_invoiced'),
                ])
                ->groupBy('partner_email', 'partner_name', 'client_id', 'period_month');
            
            // Filtri
            if (!empty($filters['period_month'])) {
                $query->where('period_month', $filters['period_month']);
            }
            
            if (!empty($filters['date_from'])) {
                $query->where('transaction_date', '>=', $filters['date_from']);
            }
            
            if (!empty($filters['date_to'])) {
                $query->where('transaction_date', '<=', $filters['date_to']);
            }
            
            $aggregated = $query->get();
            
            $result = $aggregated->map(function ($item) {
                return [
                    'partner_email' => $item->partner_email,
                    'partner_name' => $item->partner_name,
                    'client_id' => $item->client_id,
                    'period_month' => $item->period_month,
                    'transaction_count' => $item->transaction_count,
                    'total_commissions' => round($item->total_commissions, 2),
                    'first_transaction' => $item->first_transaction,
                    'last_transaction' => $item->last_transaction,
                    'is_invoiced' => (bool) $item->is_invoiced,
                    'can_generate_invoice' => !$item->is_invoiced && $item->client_id !== null,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
            
        } catch (\Exception $e) {
            Log::error('[AggregateCommissionsByPartner] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aggregazione pagamenti per destinazione trasferimento Stripe
     */
    public function aggregateByDestination(Request $request)
    {
        try {
            $filters = $request->only(['date_from', 'date_to']);
            
            $importService = new PaymentImportService();
            $result = $importService->aggregateByDestination($filters);
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
            
        } catch (\Exception $e) {
            Log::error('[AggregateByDestination] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rimborsa un pagamento via Stripe
     */
    public function refundPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'payment_id' => 'required|exists:bank_transactions,id',
                'amount' => 'nullable|numeric|min:0.01',
                'reason' => 'nullable|string|in:duplicate,fraudulent,requested_by_customer',
            ]);

            $payment = BankTransaction::findOrFail($validated['payment_id']);

            // Verifica che sia un pagamento Stripe
            if ($payment->source !== 'stripe') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo i pagamenti Stripe possono essere rimborsati tramite questa API',
                ], 422);
            }

            // Recupera il charge ID dal note
            $note = json_decode($payment->note, true);
            $chargeId = $note['stripe_source'] ?? null;

            if (!$chargeId || !str_starts_with($chargeId, 'ch_')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossibile trovare il charge ID Stripe per questo pagamento',
                ], 422);
            }

            $stripeService = new StripeService();
            $refund = $stripeService->createRefund(
                $chargeId,
                $validated['amount'] ?? null,
                $validated['reason'] ?? 'requested_by_customer'
            );

            // Aggiorna il pagamento
            $payment->update([
                'note' => json_encode(array_merge($note, [
                    'refunded' => true,
                    'refund_id' => $refund['id'],
                    'refund_amount' => $refund['amount'],
                    'refund_date' => now()->toDateTimeString(),
                ])),
            ]);

            // Ri-sincronizza per aggiornare i dati
            $startDate = now()->subDays(30);
            $endDate = now();
            $stripeService->importTransactions($startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'Rimborso effettuato con successo',
                'data' => $refund,
            ]);

        } catch (\Exception $e) {
            Log::error('[RefundPayment] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il rimborso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recupera commissioni riscosse da Stripe (Application Fees)
     */
    public function getApplicationFees(Request $request)
    {
        try {
            $validated = $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'period_month' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
                'group_by_owner' => 'nullable|in:true,false,1,0',
            ]);

            // Converti stringa booleana a vero booleano
            if (isset($validated['group_by_owner'])) {
                $validated['group_by_owner'] = filter_var($validated['group_by_owner'], FILTER_VALIDATE_BOOLEAN);
            }

            // Determina periodo
            if (!empty($validated['period_month'])) {
                $startDate = Carbon::createFromFormat('Y-m', $validated['period_month'])->startOfMonth();
                $endDate = Carbon::createFromFormat('Y-m', $validated['period_month'])->endOfMonth();
            } else {
                $startDate = !empty($validated['date_from']) 
                    ? Carbon::parse($validated['date_from'])->startOfDay()
                    : Carbon::now()->startOfMonth();
                
                $endDate = !empty($validated['date_to']) 
                    ? Carbon::parse($validated['date_to'])->endOfDay()
                    : Carbon::now()->endOfMonth();
            }

            $groupByOwner = $validated['group_by_owner'] ?? false;

            Log::info('[ApplicationFees] Recupero commissioni riscosse', [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'group_by_owner' => $groupByOwner,
            ]);

            $stripeService = new StripeService();
            
            // Recupera commissioni da Stripe
            $fees = $stripeService->getApplicationFees($startDate, $endDate);
            
            // Aggrega
            $aggregated = $stripeService->aggregateApplicationFees($fees, $groupByOwner);

            return response()->json([
                'success' => true,
                'data' => [
                    'aggregated' => $aggregated,
                    'total_amount' => array_sum(array_column($aggregated, 'total_amount')),
                    'total_count' => array_sum(array_column($aggregated, 'transaction_count')),
                    'period' => [
                        'from' => $startDate->toDateString(),
                        'to' => $endDate->toDateString(),
                    ],
                    'grouped_by_owner' => $groupByOwner,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[ApplicationFees] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il recupero delle commissioni',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dettaglio transazioni commissioni riscosse (non aggregate)
     * Per export e fatturazione differita
     */
    public function getApplicationFeesDetailed(Request $request)
    {
        try {
            $validated = $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'period_month' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
                'partner_email' => 'nullable|email',
            ]);

            // Determina periodo
            if (!empty($validated['period_month'])) {
                $startDate = Carbon::createFromFormat('Y-m', $validated['period_month'])->startOfMonth();
                $endDate = Carbon::createFromFormat('Y-m', $validated['period_month'])->endOfMonth();
            } else {
                $startDate = !empty($validated['date_from']) 
                    ? Carbon::parse($validated['date_from'])->startOfDay()
                    : Carbon::now()->startOfMonth();
                
                $endDate = !empty($validated['date_to']) 
                    ? Carbon::parse($validated['date_to'])->endOfDay()
                    : Carbon::now()->endOfMonth();
            }

            Log::info('[ApplicationFeesDetailed] Recupero commissioni riscosse dettagliate', [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'partner_filter' => $validated['partner_email'] ?? 'none',
            ]);

            $stripeService = new StripeService();
            
            // Recupera tutte le commissioni NON aggregate
            $fees = $stripeService->getApplicationFees($startDate, $endDate);
            
            // Filtra per partner se richiesto
            if (!empty($validated['partner_email'])) {
                $fees = array_filter($fees, function($fee) use ($validated) {
                    return $fee['partner_email'] === $validated['partner_email'];
                });
            }

            // Ordina per data decrescente (più recenti prima)
            usort($fees, function($a, $b) {
                return ($b['created_timestamp'] ?? 0) - ($a['created_timestamp'] ?? 0);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => array_values($fees),
                    'total_amount' => array_sum(array_column($fees, 'amount')),
                    'total_count' => count($fees),
                    'period' => [
                        'from' => $startDate->toDateString(),
                        'to' => $endDate->toDateString(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[ApplicationFeesDetailed] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il recupero delle commissioni',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export Excel commissioni riscosse dettagliate
     */
    public function exportApplicationFees(Request $request)
    {
        try {
            $validated = $request->validate([
                'period_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            ]);

            $startDate = Carbon::createFromFormat('Y-m', $validated['period_month'])->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $validated['period_month'])->endOfMonth();

            $stripeService = new StripeService();
            $fees = $stripeService->getApplicationFees($startDate, $endDate);

            // Ordina per data decrescente
            usort($fees, function($a, $b) {
                return $b['created']->timestamp - $a['created']->timestamp;
            });

            // Crea Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Intestazioni
            $sheet->setCellValue('A1', 'IMPORTO');
            $sheet->setCellValue('B1', 'DESCRIZIONE');
            $sheet->setCellValue('C1', 'TIPO');
            $sheet->setCellValue('D1', 'DATA');
            $sheet->setCellValue('E1', 'RAGIONE SOCIALE');
            $sheet->setCellValue('F1', 'EMAIL PARTNER');
            $sheet->setCellValue('G1', 'CHARGE ID');
            
            // Stile intestazioni
            $headerStyle = [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            ];
            $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

            // Dati
            $row = 2;
            foreach ($fees as $fee) {
                $sheet->setCellValue('A' . $row, number_format($fee['amount'], 2, ',', '.') . ' €');
                $sheet->setCellValue('B' . $row, $fee['description']);
                $sheet->setCellValue('C' . $row, 'Charge');
                $sheet->setCellValue('D' . $row, $fee['created']->format('d/m/Y, H:i:s'));
                $sheet->setCellValue('E' . $row, $fee['partner_name']);
                $sheet->setCellValue('F' . $row, $fee['partner_email'] ?? 'N/A');
                $sheet->setCellValue('G' . $row, $fee['charge_id']);
                $row++;
            }

            // Auto-size colonne
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Genera file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = 'commissioni_riscosse_' . $validated['period_month'] . '.xlsx';
            $tempFile = storage_path('app/temp/' . $filename);
            
            // Crea directory se non esiste
            if (!file_exists(dirname($tempFile))) {
                mkdir(dirname($tempFile), 0755, true);
            }
            
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('[ExportApplicationFees] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'export',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pre-genera fatture commissioni Stripe per revisione manuale
     * GET /api/payments/commission-invoices/pregenerate/{year}/{month}
     */
    public function pregenerateCommissionInvoices(int $year, int $month)
    {
        try {
            $service = new \App\Services\StripeCommissionInvoicingService(
                new StripeService(),
                new \App\Services\FattureInCloudService()
            );

            $previews = $service->pregenerateCommissionInvoices($month, $year);

            return response()->json([
                'success' => true,
                'data' => [
                    'previews' => $previews,
                    'total_invoices' => count($previews),
                    'ready_invoices' => count(array_filter($previews, fn($p) => $p['invoice_ready'] ?? false)),
                    'period' => [
                        'month' => $month,
                        'year' => $year,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[PregenerateCommissionInvoices] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la pre-generazione delle fatture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera fatture differite commissioni Stripe
     * POST /api/payments/commission-invoices/generate/{year}/{month}
     */
    public function generateCommissionInvoices(int $year, int $month)
    {
        try {
            $service = new \App\Services\StripeCommissionInvoicingService(
                new StripeService(),
                new \App\Services\FattureInCloudService()
            );

            $invoices = $service->generateMonthlyCommissionInvoices($month, $year);
            
            // Converti in Collection se è un array
            $invoicesCollection = is_array($invoices) ? collect($invoices) : $invoices;
            $invoiceCount = is_array($invoices) ? count($invoices) : $invoices->count();

            return response()->json([
                'success' => true,
                'message' => "Generate {$invoiceCount} fatture differite per commissioni Stripe",
                'data' => [
                    'invoices' => $invoicesCollection->map(function ($invoice) {
                        return [
                            'id' => $invoice->id,
                            'numero_fattura' => $invoice->numero_fattura,
                            'client_id' => $invoice->client_id,
                            'client_name' => $invoice->client->ragione_sociale,
                            'totale' => $invoice->totale,
                            'status' => $invoice->status,
                        ];
                    }),
                    'total_amount' => $invoicesCollection->sum('totale'),
                    'count' => $invoiceCount,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[GenerateCommissionInvoices] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la generazione delle fatture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera singola fattura differita per un partner
     * POST /api/payments/commission-invoices/generate-single/{year}/{month}
     */
    public function generateSingleCommissionInvoice(Request $request, int $year, int $month)
    {
        $request->validate([
            'partner_email' => 'required|email',
            'client_id' => 'required|exists:clients,id'
        ]);

        try {
            $service = new \App\Services\StripeCommissionInvoicingService(
                new StripeService(),
                new \App\Services\FattureInCloudService()
            );

            $invoice = $service->generateSinglePartnerInvoice(
                $month,
                $year,
                $request->partner_email,
                $request->client_id
            );

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossibile generare la fattura - verificare che ci siano commissioni per questo partner',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => "Fattura generata con successo per {$invoice->client->ragione_sociale}",
                'data' => [
                    'invoice' => [
                        'id' => $invoice->id,
                        'numero_fattura' => $invoice->numero_fattura,
                        'client_id' => $invoice->client_id,
                        'client_name' => $invoice->client->ragione_sociale,
                        'totale' => $invoice->totale,
                        'status' => $invoice->status,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[GenerateSingleCommissionInvoice] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la generazione della fattura',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Invia fatture differite a Fatture in Cloud
     * POST /api/payments/commission-invoices/send-to-fic/{year}/{month}
     */
    public function sendCommissionInvoicesToFIC(int $year, int $month)
    {
        try {
            $service = new \App\Services\StripeCommissionInvoicingService(
                new StripeService(),
                new \App\Services\FattureInCloudService()
            );

            $results = $service->sendDeferredInvoicesToFIC($month, $year);

            $successCount = count(array_filter($results, fn($r) => $r['success']));
            $errorCount = count($results) - $successCount;

            return response()->json([
                'success' => true,
                'message' => "Inviate {$successCount} fatture a Fatture in Cloud" . ($errorCount > 0 ? " ({$errorCount} errori)" : ''),
                'data' => [
                    'results' => $results,
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[SendCommissionInvoicesToFIC] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'invio delle fatture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crea una Checkout Session Stripe per generare un link di pagamento
     * POST /api/stripe/checkout-sessions
     */
    public function createCheckoutSession(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.50',
            'description' => 'required|string|max:500',
            'customer_email' => 'nullable|email',
        ]);

        try {
            $service = new StripeService();
            $session = $service->createCheckoutSession(
                $validated['amount'],
                $validated['description'],
                $validated['customer_email'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Link di pagamento creato con successo',
                'data' => $session,
            ]);

        } catch (\Exception $e) {
            Log::error('[CreateCheckoutSession] Errore: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la creazione del link di pagamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista checkout sessions
     * GET /api/stripe/checkout-sessions
     */
    public function listCheckoutSessions(Request $request)
    {
        try {
            $service = new StripeService();
            $sessions = $service->listCheckoutSessions($request->query('status'));

            return response()->json([
                'success' => true,
                'data' => $sessions,
            ]);

        } catch (\Exception $e) {
            Log::error('[ListCheckoutSessions] Errore: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante il recupero dei link di pagamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aggiorna stato di una checkout session
     * POST /api/stripe/checkout-sessions/{sessionId}/refresh
     */
    public function refreshCheckoutSession(string $sessionId)
    {
        try {
            $service = new StripeService();
            $session = $service->refreshCheckoutSessionStatus($sessionId);

            return response()->json([
                'success' => true,
                'data' => $session,
            ]);

        } catch (\Exception $e) {
            Log::error('[RefreshCheckoutSession] Errore: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento dello stato',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}