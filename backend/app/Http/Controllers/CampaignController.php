<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::query();

        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        return response()->json([
            'data' => $query->latest()->paginate($request->per_page ?? 20)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,sms,social,event,webinar,content,advertising,other',
            'status' => 'nullable|in:draft,scheduled,active,paused,completed,cancelled',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'expected_revenue' => 'nullable|numeric|min:0',
            'target_audience' => 'nullable|string',
        ]);

        $campaign = Campaign::create($validated);

        return response()->json($campaign, 201);
    }

    public function show(Campaign $campaign)
    {
        return response()->json($campaign->load([
            'members.member',
            'members' => function($q) {
                $q->select('campaign_id', 
                    DB::raw('count(*) as total'),
                    DB::raw('sum(case when status = "sent" then 1 else 0 end) as sent'),
                    DB::raw('sum(case when status = "delivered" then 1 else 0 end) as delivered'),
                    DB::raw('sum(case when opened_at is not null then 1 else 0 end) as opened'),
                    DB::raw('sum(case when clicked_at is not null then 1 else 0 end) as clicked'),
                    DB::raw('sum(case when converted_at is not null then 1 else 0 end) as converted')
                )->groupBy('campaign_id');
            }
        ]));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'type' => 'in:email,sms,social,event,webinar,content,advertising,other',
            'status' => 'in:draft,scheduled,active,paused,completed,cancelled',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'expected_revenue' => 'nullable|numeric|min:0',
            'actual_revenue' => 'nullable|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'target_audience' => 'nullable|string',
        ]);

        $campaign->update($validated);

        return response()->json($campaign);
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return response()->json(['message' => 'Campagna eliminata']);
    }

    /**
     * Aggiungi membri alla campagna
     */
    public function addMembers(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'members' => 'required|array',
            'members.*.member_type' => 'required|string',
            'members.*.member_id' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['members'] as $member) {
                CampaignMember::firstOrCreate([
                    'campaign_id' => $campaign->id,
                    'member_type' => $member['member_type'],
                    'member_id' => $member['member_id'],
                ], [
                    'status' => 'pending',
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => 'Membri aggiunti',
                'campaign' => $campaign->fresh(['members'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update member status
     */
    public function updateMemberStatus(Request $request, Campaign $campaign, CampaignMember $member)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,sent,delivered,bounced,failed',
            'opened_at' => 'nullable|date',
            'clicked_at' => 'nullable|date',
            'converted_at' => 'nullable|date',
            'revenue_generated' => 'nullable|numeric|min:0',
        ]);

        $member->update($validated);

        // Update campaign metrics
        $this->updateCampaignMetrics($campaign);

        return response()->json([
            'message' => 'Stato aggiornato',
            'member' => $member->fresh()
        ]);
    }

    /**
     * Campaign stats
     */
    public function stats()
    {
        return response()->json([
            'total' => Campaign::count(),
            'active' => Campaign::where('status', 'active')->count(),
            'completed' => Campaign::where('status', 'completed')->count(),
            'by_type' => Campaign::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type'),
            'total_budget' => Campaign::where('status', 'active')->sum('budget'),
            'total_spent' => Campaign::sum('actual_cost'),
            'total_revenue' => Campaign::sum('actual_revenue'),
            'average_roi' => Campaign::whereNotNull('actual_cost')
                ->where('actual_cost', '>', 0)
                ->get()
                ->average('roi'),
        ]);
    }

    /**
     * Update campaign aggregate metrics
     */
    private function updateCampaignMetrics(Campaign $campaign)
    {
        $metrics = $campaign->members()
            ->selectRaw('
                count(*) as total_sent,
                sum(case when status = "delivered" then 1 else 0 end) as total_delivered,
                sum(case when opened_at is not null then 1 else 0 end) as total_opened,
                sum(case when clicked_at is not null then 1 else 0 end) as total_clicked,
                sum(case when converted_at is not null then 1 else 0 end) as total_converted,
                sum(revenue_generated) as total_revenue
            ')
            ->first();

        $campaign->update([
            'total_sent' => $metrics->total_sent ?? 0,
            'total_delivered' => $metrics->total_delivered ?? 0,
            'total_opened' => $metrics->total_opened ?? 0,
            'total_clicked' => $metrics->total_clicked ?? 0,
            'total_converted' => $metrics->total_converted ?? 0,
            'actual_revenue' => $metrics->total_revenue ?? 0,
        ]);
    }
}
