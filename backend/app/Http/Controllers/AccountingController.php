<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Models\BankTransaction;
use App\Models\AccountingCategory;
use App\Services\BankStatementImportService;
use App\Services\AccountingReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AccountingController extends Controller
{
    protected $importService;
    protected $reconciliationService;

    public function __construct(
        BankStatementImportService $importService,
        AccountingReconciliationService $reconciliationService
    ) {
        $this->importService = $importService;
        $this->reconciliationService = $reconciliationService;
    }

    /**
     * Dashboard contabilità
     */
    public function dashboard(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $accounts = BankAccount::where('is_active', true)->get();
        
        // Saldi totali
        $totalBalance = $accounts->sum('saldo_attuale');

        // Transazioni del periodo
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        $transactions = BankTransaction::inPeriod($startDate, $endDate)->get();

        $totalEntrate = $transactions->where('type', 'entrata')->sum('amount');
        $totalUscite = $transactions->where('type', 'uscita')->sum('amount');
        $balance = $totalEntrate - $totalUscite;

        // Transazioni non riconciliate
        $unreconciledCount = $transactions->where('is_reconciled', false)->count();
        $unreconciledAmount = $transactions->where('is_reconciled', false)->sum('amount');

        // Per categoria
        $byCategory = BankTransaction::with('category')
            ->inPeriod($startDate, $endDate)
            ->get()
            ->groupBy('category_id')
            ->map(function($items) {
                return [
                    'category' => $items->first()?->category?->name ?? 'Non categorizzato',
                    'color' => $items->first()?->category?->color ?? '#999',
                    'total' => $items->sum('amount'),
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        // Ultimi movimenti
        $recentTransactions = BankTransaction::with(['bankAccount', 'category', 'invoice', 'supplierInvoice'])
            ->orderBy('transaction_date', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'period' => [
                'month' => $month,
                'year' => $year,
            ],
            'accounts' => $accounts,
            'summary' => [
                'total_balance' => round($totalBalance, 2),
                'total_entrate' => round($totalEntrate, 2),
                'total_uscite' => round($totalUscite, 2),
                'balance' => round($balance, 2),
                'unreconciled_count' => $unreconciledCount,
                'unreconciled_amount' => round($unreconciledAmount, 2),
            ],
            'by_category' => $byCategory,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    /**
     * Lista conti bancari
     */
    public function accounts()
    {
        $accounts = BankAccount::with(['statements' => function($q) {
            $q->latest()->limit(3);
        }])->get();

        return response()->json($accounts);
    }

    /**
     * Crea o aggiorna conto bancario
     */
    public function storeAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'iban' => 'required|string|unique:bank_accounts,iban,' . $request->id,
            'type' => 'required|in:corrente,stripe,vivawallet,altro',
            'saldo_iniziale' => 'nullable|numeric',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $account = BankAccount::updateOrCreate(
            ['id' => $request->id],
            $request->only(['name', 'bank_name', 'iban', 'type', 'saldo_iniziale', 'is_active', 'note'])
        );

        return response()->json($account, $request->id ? 200 : 201);
    }

    /**
     * Upload e import estratto conto
     */
    public function importStatement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $bankAccount = BankAccount::findOrFail($request->bank_account_id);
            
            // Salva il file
            $path = $request->file('file')->store('bank_statements', 'local');
            $fullPath = Storage::path($path);

            // Importa
            $statement = $this->importService->importFromFile(
                $bankAccount,
                $fullPath,
                $request->month,
                $request->year
            );

            return response()->json([
                'message' => 'Estratto conto importato con successo',
                'statement' => $statement->load('transactions'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Errore durante l\'importazione',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista transazioni
     */
    public function transactions(Request $request)
    {
        $query = BankTransaction::with(['bankAccount', 'category', 'invoice', 'supplierInvoice']);

        // Filtri
        if ($request->has('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_reconciled')) {
            $query->where('is_reconciled', $request->boolean('is_reconciled'));
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->inPeriod($request->start_date, $request->end_date);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('descrizione', 'like', "%{$search}%")
                  ->orWhere('beneficiario', 'like', "%{$search}%")
                  ->orWhere('causale', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 50);
        $transactions = $query->orderBy('transaction_date', 'desc')->paginate($perPage);

        return response()->json($transactions);
    }

    /**
     * Aggiorna transazione manualmente
     */
    public function updateTransaction(Request $request, $id)
    {
        $transaction = BankTransaction::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:accounting_categories,id',
            'is_reconciled' => 'boolean',
            'invoice_id' => 'nullable|exists:invoices,id',
            'supplier_invoice_id' => 'nullable|exists:supplier_invoices,id',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $transaction->update($request->only([
            'category_id',
            'is_reconciled',
            'invoice_id',
            'supplier_invoice_id',
            'note'
        ]));

        return response()->json($transaction->load(['category', 'invoice', 'supplierInvoice']));
    }

    /**
     * Riconciliazione automatica
     */
    public function reconcile(Request $request)
    {
        $transactionIds = $request->get('transaction_ids', []);

        $results = $this->reconciliationService->reconcileTransactions($transactionIds);

        return response()->json([
            'message' => 'Riconciliazione completata',
            'results' => $results,
        ]);
    }

    /**
     * Auto-riconciliazione intelligente con scoring
     */
    public function autoReconcile(Request $request)
    {
        $results = $this->reconciliationService->autoReconcileWithScoring();

        return response()->json([
            'message' => 'Auto-riconciliazione completata',
            'summary' => [
                'high_confidence' => $results['high_confidence'],
                'medium_confidence' => $results['medium_confidence'],
                'low_confidence' => $results['low_confidence'],
                'unmatched' => $results['unmatched'],
            ],
            'matches' => $results['matches'],
        ]);
    }

    /**
     * Report riconciliazione
     */
    public function reconciliationReport(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $report = $this->reconciliationService->generateReconciliationReport($month, $year);

        return response()->json($report);
    }

    /**
     * Lista categorie
     */
    public function categories()
    {
        $categories = AccountingCategory::with('children')
            ->whereNull('parent_id')
            ->active()
            ->get();

        return response()->json($categories);
    }

    /**
     * Crea o aggiorna categoria
     */
    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:accounting_categories,slug,' . $request->id,
            'type' => 'required|in:entrata,uscita',
            'parent_id' => 'nullable|exists:accounting_categories,id',
            'keywords' => 'nullable|array',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = AccountingCategory::updateOrCreate(
            ['id' => $request->id],
            $request->only(['name', 'slug', 'type', 'parent_id', 'keywords', 'color', 'icon', 'description', 'is_active'])
        );

        return response()->json($category, $request->id ? 200 : 201);
    }

    /**
     * Report finanziario completo
     */
    public function financialReport(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfYear());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $transactions = BankTransaction::with('category')
            ->inPeriod($startDate, $endDate)
            ->get();

        // Entrate per categoria
        $entrateByCategory = $transactions
            ->where('type', 'entrata')
            ->groupBy('category_id')
            ->map(function($items) {
                return [
                    'category' => $items->first()?->category?->name ?? 'Non categorizzato',
                    'total' => round($items->sum('amount'), 2),
                    'count' => $items->count(),
                    'average' => round($items->avg('amount'), 2),
                ];
            })
            ->sortByDesc('total')
            ->values();

        // Uscite per categoria
        $usciteByCategory = $transactions
            ->where('type', 'uscita')
            ->groupBy('category_id')
            ->map(function($items) {
                return [
                    'category' => $items->first()?->category?->name ?? 'Non categorizzato',
                    'total' => round($items->sum('amount'), 2),
                    'count' => $items->count(),
                    'average' => round($items->avg('amount'), 2),
                ];
            })
            ->sortByDesc('total')
            ->values();

        // Trend mensile
        $monthlyTrend = $transactions
            ->groupBy(function($transaction) {
                return $transaction->transaction_date->format('Y-m');
            })
            ->map(function($items, $month) {
                $entrate = $items->where('type', 'entrata')->sum('amount');
                $uscite = $items->where('type', 'uscita')->sum('amount');
                
                return [
                    'month' => $month,
                    'entrate' => round($entrate, 2),
                    'uscite' => round($uscite, 2),
                    'balance' => round($entrate - $uscite, 2),
                ];
            })
            ->sortKeys()
            ->values();

        $totalEntrate = $transactions->where('type', 'entrata')->sum('amount');
        $totalUscite = $transactions->where('type', 'uscita')->sum('amount');

        return response()->json([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_entrate' => round($totalEntrate, 2),
                'total_uscite' => round($totalUscite, 2),
                'balance' => round($totalEntrate - $totalUscite, 2),
                'transactions_count' => $transactions->count(),
            ],
            'entrate_by_category' => $entrateByCategory,
            'uscite_by_category' => $usciteByCategory,
            'monthly_trend' => $monthlyTrend,
        ]);
    }
}
