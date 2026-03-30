<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with(['related', 'assignedTo', 'createdBy']);

        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Scopes
        if ($request->filter === 'pending') {
            $query->pending();
        } elseif ($request->filter === 'overdue') {
            $query->overdue();
        } elseif ($request->filter === 'due_today') {
            $query->dueToday();
        } elseif ($request->filter === 'need_reminder') {
            $query->needReminder();
        }

        // Related entity filter
        if ($request->related_type && $request->related_id) {
            $query->where('related_type', $request->related_type)
                  ->where('related_id', $request->related_id);
        }

        return response()->json([
            'data' => $query->latest('due_date')->paginate($request->per_page ?? 50)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:call,email,meeting,task,note,sms',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'related_type' => 'nullable|string',
            'related_id' => 'nullable|integer',
            
            // Email specific
            'email_from' => 'nullable|email',
            'email_to' => 'nullable|email',
            'email_cc' => 'nullable|string',
            'email_subject' => 'nullable|string',
            'email_body' => 'nullable|string',
            
            // Call specific
            'call_direction' => 'nullable|in:inbound,outbound',
            'call_outcome' => 'nullable|in:answered,no_answer,busy,voicemail,failed',
            'call_duration' => 'nullable|integer',
            
            // Meeting specific
            'meeting_location' => 'nullable|string',
            'meeting_attendees' => 'nullable|string',
            
            // Reminder
            'reminder_at' => 'nullable|date',
            'reminder_sent' => 'nullable|boolean',
        ]);

        $validated['created_by'] = auth()->id();

        $activity = Activity::create($validated);

        return response()->json($activity->load(['related', 'assignedTo', 'createdBy']), 201);
    }

    public function show(Activity $activity)
    {
        return response()->json($activity->load(['related', 'assignedTo', 'createdBy']));
    }

    public function update(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'type' => 'in:call,email,meeting,task,note,sms',
            'subject' => 'string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'email_from' => 'nullable|email',
            'email_to' => 'nullable|email',
            'email_cc' => 'nullable|string',
            'email_subject' => 'nullable|string',
            'email_body' => 'nullable|string',
            'call_direction' => 'nullable|in:inbound,outbound',
            'call_outcome' => 'nullable|in:answered,no_answer,busy,voicemail,failed',
            'call_duration' => 'nullable|integer',
            'meeting_location' => 'nullable|string',
            'meeting_attendees' => 'nullable|string',
            'reminder_at' => 'nullable|date',
            'reminder_sent' => 'nullable|boolean',
        ]);

        $activity->update($validated);

        return response()->json($activity->load(['related', 'assignedTo', 'createdBy']));
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();
        return response()->json(['message' => 'Attività eliminata']);
    }

    /**
     * Segna come completata
     */
    public function complete(Activity $activity)
    {
        $activity->markAsCompleted();

        return response()->json([
            'message' => 'Attività completata',
            'activity' => $activity->fresh()
        ]);
    }

    /**
     * Dashboard stats
     */
    public function stats()
    {
        return response()->json([
            'total' => Activity::count(),
            'pending' => Activity::pending()->count(),
            'completed' => Activity::completed()->count(),
            'overdue' => Activity::overdue()->count(),
            'due_today' => Activity::dueToday()->count(),
            'by_type' => Activity::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type'),
            'by_priority' => Activity::select('priority', DB::raw('count(*) as count'))
                ->whereNotNull('priority')
                ->groupBy('priority')
                ->pluck('count', 'priority'),
            'completion_rate' => Activity::count() > 0
                ? round(Activity::completed()->count() / Activity::count() * 100, 2)
                : 0,
        ]);
    }
}
