<?php

namespace App\Http\Controllers;

use App\Http\Traits\CsvExportTrait;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    use CsvExportTrait;

    /**
     * Esporta lead in formato CSV
     */
    public function export(Request $request)
    {
        $query = Lead::with('assignedTo');

        if ($request->status) $query->where('status', $request->status);
        if ($request->source) $query->where('source', $request->source);

        $leads = $query->latest()->get();

        $data = [];
        foreach ($leads as $l) {
            $data[] = [
                'ID' => $l->id,
                'Azienda' => $l->company_name ?? '',
                'Contatto' => $l->contact_name ?? '',
                'Email' => $l->email ?? '',
                'Telefono' => $l->phone ?? '',
                'Fonte' => $l->source ?? '',
                'Stato' => $l->status ?? '',
                'Priorità' => $l->priority ?? '',
                'Rating' => $l->rating ?? '',
                'Settore' => $l->industry ?? '',
                'Valore Stimato (€)' => number_format($l->estimated_value ?? 0, 2, ',', '.'),
                'Città' => $l->city ?? '',
                'Assegnato a' => $l->assignedTo?->name ?? '',
                'Ultimo Contatto' => $l->last_contact_at ? $l->last_contact_at->format('d/m/Y') : '',
                'Prossimo Follow-up' => $l->next_follow_up_at ? $l->next_follow_up_at->format('d/m/Y') : '',
                'Note' => $l->notes ?? '',
                'Data Creazione' => $l->created_at ? $l->created_at->format('d/m/Y') : '',
            ];
        }

        return $this->streamCsv($data, 'lead_' . date('Y-m-d_His') . '.csv');
    }

    public function index(Request $request)
    {
        $query = Lead::with(['pipelineStage', 'assignedTo', 'tags']);

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->source) {
            $query->where('source', $request->source);
        }
        if ($request->rating) {
            $query->where('rating', $request->rating);
        }
        if ($request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('company_name', 'like', "%{$request->search}%")
                  ->orWhere('contact_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        // Scopes
        if ($request->filter === 'active') {
            $query->active();
        } elseif ($request->filter === 'need_followup') {
            $query->needFollowUp();
        }

        return response()->json([
            'data' => $query->latest()->paginate($request->per_page ?? 20)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'source' => 'required|in:website,referral,cold_call,social_media,event,partner,advertising,other,direct',
            'status' => 'nullable|in:new,contacted,qualified,unqualified,converted,lost',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'rating' => 'nullable|in:hot,warm,cold',
            'estimated_value' => 'nullable|numeric|min:0',
            'company_size' => 'nullable|string',
            'industry' => 'nullable|string',
            'website' => 'nullable|url',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'pipeline_stage_id' => 'nullable|exists:pipeline_stages,id',
            'tags' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $lead = Lead::create($validated);

            if ($request->tags) {
                $lead->tags()->sync($request->tags);
            }

            // Auto activity
            $lead->addActivity('note', 'Lead creato', [
                'description' => "Lead {$lead->lead_number} creato tramite {$lead->source}",
            ]);

            DB::commit();
            return response()->json($lead->load(['pipelineStage', 'assignedTo', 'tags']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Lead $lead)
    {
        return response()->json($lead->load([
            'pipelineStage',
            'assignedTo',
            'tags',
            'activities.createdBy',
            'communications.user',
            'client',
            'opportunity'
        ]));
    }

    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'company_name' => 'string|max:255',
            'contact_name' => 'string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'source' => 'in:website,referral,cold_call,social_media,event,partner,advertising,other,direct',
            'status' => 'in:new,contacted,qualified,unqualified,converted,lost',
            'priority' => 'in:low,medium,high,urgent',
            'rating' => 'in:hot,warm,cold',
            'estimated_value' => 'nullable|numeric|min:0',
            'company_size' => 'nullable|string',
            'industry' => 'nullable|string',
            'website' => 'nullable|url',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'pipeline_stage_id' => 'nullable|exists:pipeline_stages,id',
            'tags' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $lead->update($validated);

            if ($request->has('tags')) {
                $lead->tags()->sync($request->tags);
            }

            DB::commit();
            return response()->json($lead->load(['pipelineStage', 'assignedTo', 'tags']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Lead $lead)
    {
        if ($lead->isConverted()) {
            return response()->json([
                'error' => 'Impossibile eliminare un lead già convertito'
            ], 422);
        }

        $lead->delete();
        return response()->json(['message' => 'Lead eliminato']);
    }

    /**
     * Converti lead in cliente
     */
    public function convertToClient(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'client_type' => 'required|in:partner_oppla,cliente_extra,consumatore',
            'ragione_sociale' => 'required|string',
            'piva' => 'nullable|string',
            'codice_fiscale' => 'nullable|string',
            'telefono' => 'nullable|string',
            'email' => 'required|email',
            'indirizzo' => 'nullable|string',
            'cap' => 'nullable|string',
            'citta' => 'nullable|string',
            'provincia' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $client = Client::create(array_merge($validated, [
                'lead_id' => $lead->id,
                'client_type' => 'customer',
                'status' => 'active',
                'segment' => $lead->company_size,
                'industry' => $lead->industry,
                'account_manager_id' => $lead->assigned_to,
            ]));

            $lead->update([
                'status' => 'converted',
                'converted_to_client_at' => now(),
                'client_id' => $client->id,
            ]);

            $lead->addActivity('note', 'Lead convertito in cliente', [
                'description' => "Lead {$lead->lead_number} convertito in cliente {$client->ragione_sociale}",
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Lead convertito con successo',
                'client' => $client,
                'lead' => $lead->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Converti lead in opportunità
     */
    public function convertToOpportunity(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expected_close_date' => 'required|date',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $opportunity = Opportunity::create(array_merge($validated, [
                'lead_id' => $lead->id,
                'assigned_to' => $lead->assigned_to,
            ]));

            $lead->update([
                'status' => 'converted',
                'converted_to_opportunity_at' => now(),
                'opportunity_id' => $opportunity->id,
            ]);

            $lead->addActivity('note', 'Lead convertito in opportunità', [
                'description' => "Lead {$lead->lead_number} convertito in opportunità {$opportunity->opportunity_number}",
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Lead convertito in opportunità',
                'opportunity' => $opportunity,
                'lead' => $lead->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Dashboard stats
     */
    public function stats()
    {
        return response()->json([
            'total' => Lead::count(),
            'by_status' => Lead::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_rating' => Lead::select('rating', DB::raw('count(*) as count'))
                ->whereNotNull('rating')
                ->groupBy('rating')
                ->pluck('count', 'rating'),
            'by_source' => Lead::select('source', DB::raw('count(*) as count'))
                ->groupBy('source')
                ->pluck('count', 'source'),
            'active' => Lead::active()->count(),
            'need_followup' => Lead::needFollowUp()->count(),
            'converted_this_month' => Lead::converted()
                ->whereMonth('converted_to_client_at', now()->month)
                ->count(),
            'total_estimated_value' => Lead::active()->sum('estimated_value'),
        ]);
    }
}
