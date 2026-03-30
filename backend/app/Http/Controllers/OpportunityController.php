<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $query = Opportunity::with(['pipelineStage', 'client', 'lead', 'assignedTo']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // Scopes
        if ($request->filter === 'open') {
            $query->open();
        } elseif ($request->filter === 'closing_soon') {
            $query->closingSoon();
        } elseif ($request->filter === 'overdue') {
            $query->overdue();
        }

        return response()->json([
            'data' => $query->latest()->paginate($request->per_page ?? 20)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expected_close_date' => 'required|date',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'client_id' => 'nullable|exists:clients,id',
            'lead_id' => 'nullable|exists:leads,id',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'next_step' => 'nullable|string',
            'competitors' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $opportunity = Opportunity::create($validated);

            if ($request->tags) {
                $opportunity->tags()->sync($request->tags);
            }

            DB::commit();
            return response()->json($opportunity->load(['pipelineStage', 'client', 'assignedTo']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Opportunity $opportunity)
    {
        return response()->json($opportunity->load([
            'pipelineStage',
            'client',
            'lead',
            'assignedTo',
            'tags',
            'activities.createdBy',
            'communications.user'
        ]));
    }

    public function update(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'amount' => 'numeric|min:0',
            'expected_close_date' => 'date',
            'pipeline_stage_id' => 'exists:pipeline_stages,id',
            'client_id' => 'nullable|exists:clients,id',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'next_step' => 'nullable|string',
            'competitors' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $opportunity->update($validated);

            if ($request->has('tags')) {
                $opportunity->tags()->sync($request->tags);
            }

            DB::commit();
            return response()->json($opportunity->load(['pipelineStage', 'client', 'assignedTo']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Opportunity $opportunity)
    {
        $opportunity->delete();
        return response()->json(['message' => 'Opportunità eliminata']);
    }

    /**
     * Sposta opportunità in un altro stage
     */
    public function moveStage(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'note' => 'nullable|string',
        ]);

        $oldStage = $opportunity->pipelineStage;
        $opportunity->update(['pipeline_stage_id' => $validated['pipeline_stage_id']]);

        $opportunity->addActivity('note', 'Stage cambiato', [
            'description' => "Spostato da {$oldStage->name} a {$opportunity->pipelineStage->name}",
            'notes' => $request->note,
        ]);

        return response()->json([
            'message' => 'Stage aggiornato',
            'opportunity' => $opportunity->fresh(['pipelineStage'])
        ]);
    }

    /**
     * Segna come vinto
     */
    public function markAsWon(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'actual_close_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $opportunity->markAsWon($validated['actual_close_date'] ?? now());

        if ($request->note) {
            $opportunity->addNote($request->note);
        }

        return response()->json([
            'message' => 'Opportunità chiusa come vinta',
            'opportunity' => $opportunity->fresh()
        ]);
    }

    /**
     * Segna come perso
     */
    public function markAsLost(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'lost_reason' => 'required|string',
            'note' => 'nullable|string',
        ]);

        $opportunity->markAsLost($validated['lost_reason']);

        if ($request->note) {
            $opportunity->addNote($request->note);
        }

        return response()->json([
            'message' => 'Opportunità chiusa come persa',
            'opportunity' => $opportunity->fresh()
        ]);
    }

    /**
     * Pipeline stats
     */
    public function stats()
    {
        return response()->json([
            'total' => Opportunity::count(),
            'open' => Opportunity::open()->count(),
            'won' => Opportunity::won()->count(),
            'lost' => Opportunity::lost()->count(),
            'by_stage' => Opportunity::with('pipelineStage')
                ->select('pipeline_stage_id', DB::raw('count(*) as count'), DB::raw('sum(weighted_amount) as weighted_value'))
                ->groupBy('pipeline_stage_id')
                ->get()
                ->map(function($item) {
                    return [
                        'stage' => $item->pipelineStage->name ?? 'N/A',
                        'count' => $item->count,
                        'weighted_value' => $item->weighted_value,
                    ];
                }),
            'total_value' => Opportunity::open()->sum('amount'),
            'weighted_value' => Opportunity::open()->sum('weighted_amount'),
            'closing_this_month' => Opportunity::open()
                ->whereMonth('expected_close_date', now()->month)
                ->sum('amount'),
            'overdue' => Opportunity::overdue()->count(),
            'win_rate' => Opportunity::whereIn('status', ['won', 'lost'])->count() > 0
                ? round(Opportunity::won()->count() / Opportunity::whereIn('status', ['won', 'lost'])->count() * 100, 2)
                : 0,
        ]);
    }
}
