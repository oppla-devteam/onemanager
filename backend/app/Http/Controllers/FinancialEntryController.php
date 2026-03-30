<?php

namespace App\Http\Controllers;

use App\Models\FinancialEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinancialEntryController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancialEntry::with('category:id,name,slug,color,icon,parent_id');

        if ($request->filled('entry_type')) {
            $query->where('entry_type', $request->input('entry_type'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('is_recurring')) {
            $query->where('is_recurring', $request->boolean('is_recurring'));
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('vendor_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort_by', 'date');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 25);
        return response()->json($query->paginate($perPage));
    }

    public function show(FinancialEntry $financialEntry)
    {
        $financialEntry->load('category');
        return response()->json($financialEntry);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id'        => 'nullable|exists:accounting_categories,id',
            'entry_type'         => 'required|in:costo_fisso,costo_variabile,entrata_fissa,entrata_variabile,debito,credito',
            'description'        => 'required|string|max:255',
            'amount'             => 'required|numeric|min:0.01',
            'paid_amount'        => 'nullable|numeric|min:0',
            'date'               => 'required|date',
            'due_date'           => 'nullable|date',
            'is_recurring'       => 'boolean',
            'recurring_interval' => 'nullable|in:monthly,quarterly,yearly',
            'next_renewal_date'  => 'nullable|date',
            'vendor_name'        => 'nullable|string|max:255',
            'notes'              => 'nullable|string',
            'status'             => 'nullable|in:active,paid,cancelled,overdue',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'category_id', 'entry_type', 'description', 'amount', 'paid_amount',
            'date', 'due_date', 'is_recurring', 'recurring_interval',
            'next_renewal_date', 'vendor_name', 'notes', 'status',
        ]);
        $data['status'] = $data['status'] ?? 'active';
        $data['paid_amount'] = $data['paid_amount'] ?? 0;

        $entry = FinancialEntry::create($data);
        return response()->json($entry->load('category'), 201);
    }

    public function update(Request $request, FinancialEntry $financialEntry)
    {
        $validator = Validator::make($request->all(), [
            'category_id'        => 'nullable|exists:accounting_categories,id',
            'entry_type'         => 'sometimes|in:costo_fisso,costo_variabile,entrata_fissa,entrata_variabile,debito,credito',
            'description'        => 'sometimes|string|max:255',
            'amount'             => 'sometimes|numeric|min:0.01',
            'paid_amount'        => 'nullable|numeric|min:0',
            'date'               => 'sometimes|date',
            'due_date'           => 'nullable|date',
            'is_recurring'       => 'boolean',
            'recurring_interval' => 'nullable|in:monthly,quarterly,yearly',
            'next_renewal_date'  => 'nullable|date',
            'vendor_name'        => 'nullable|string|max:255',
            'notes'              => 'nullable|string',
            'status'             => 'nullable|in:active,paid,cancelled,overdue',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $financialEntry->update($request->only([
            'category_id', 'entry_type', 'description', 'amount', 'paid_amount',
            'date', 'due_date', 'is_recurring', 'recurring_interval',
            'next_renewal_date', 'vendor_name', 'notes', 'status',
        ]));

        return response()->json($financialEntry->load('category'));
    }

    public function destroy(FinancialEntry $financialEntry)
    {
        $financialEntry->delete();
        return response()->json(['message' => 'Voce eliminata con successo']);
    }

    public function summary(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Voci attive nel periodo (escluse cancellate)
        $entries = FinancialEntry::where('status', '!=', 'cancelled')->get();
        $periodEntries = FinancialEntry::where('status', '!=', 'cancelled')
            ->inPeriod($startDate, $endDate)
            ->get();

        // Totali per tipo (periodo)
        $totalsByType = [
            'costi_fissi'        => round($periodEntries->where('entry_type', 'costo_fisso')->sum('amount'), 2),
            'costi_variabili'    => round($periodEntries->where('entry_type', 'costo_variabile')->sum('amount'), 2),
            'entrate_fisse'      => round($periodEntries->where('entry_type', 'entrata_fissa')->sum('amount'), 2),
            'entrate_variabili'  => round($periodEntries->where('entry_type', 'entrata_variabile')->sum('amount'), 2),
            'debiti'             => round($entries->where('entry_type', 'debito')->where('status', 'active')->sum('amount'), 2),
            'debiti_remaining'   => round($entries->where('entry_type', 'debito')->where('status', 'active')->sum('remaining_amount'), 2),
            'crediti'            => round($entries->where('entry_type', 'credito')->where('status', 'active')->sum('amount'), 2),
            'crediti_remaining'  => round($entries->where('entry_type', 'credito')->where('status', 'active')->sum('remaining_amount'), 2),
        ];

        $totalCosts = $totalsByType['costi_fissi'] + $totalsByType['costi_variabili'];
        $totalIncomes = $totalsByType['entrate_fisse'] + $totalsByType['entrate_variabili'];

        // Breakdown per categoria (periodo)
        $byCategory = $periodEntries
            ->groupBy('category_id')
            ->map(function ($items) {
                $cat = $items->first()->category;
                return [
                    'category_id'   => $items->first()->category_id,
                    'category_name' => $cat?->name ?? 'Non categorizzato',
                    'color'         => $cat?->color ?? '#6b7280',
                    'icon'          => $cat?->icon,
                    'total'         => round($items->sum('amount'), 2),
                    'count'         => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        // Rinnovi prossimi 30 giorni
        $renewals = FinancialEntry::with('category:id,name,color,icon')
            ->renewingSoon(30)
            ->orderBy('next_renewal_date')
            ->get();

        // Voci scadute
        $overdue = FinancialEntry::with('category:id,name,color')
            ->overdue()
            ->orderBy('due_date')
            ->get();

        // Trend mensile (ultimi 6 mesi)
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $mStart = now()->subMonths($i)->startOfMonth();
            $mEnd = now()->subMonths($i)->endOfMonth();
            $monthEntries = FinancialEntry::where('status', '!=', 'cancelled')
                ->inPeriod($mStart, $mEnd)->get();

            $monthlyTrend[] = [
                'month'   => $mStart->translatedFormat('M Y'),
                'costs'   => round($monthEntries->whereIn('entry_type', ['costo_fisso', 'costo_variabile'])->sum('amount'), 2),
                'incomes' => round($monthEntries->whereIn('entry_type', ['entrata_fissa', 'entrata_variabile'])->sum('amount'), 2),
            ];
        }

        return response()->json([
            'period'         => ['start' => $startDate, 'end' => $endDate],
            'totals_by_type' => $totalsByType,
            'total_costs'    => round($totalCosts, 2),
            'total_incomes'  => round($totalIncomes, 2),
            'net_balance'    => round($totalIncomes - $totalCosts, 2),
            'by_category'    => $byCategory,
            'renewals'       => $renewals,
            'overdue'        => $overdue,
            'monthly_trend'  => $monthlyTrend,
        ]);
    }
}
