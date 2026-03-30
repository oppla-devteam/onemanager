<?php

namespace App\Http\Controllers;

use App\Models\EmailSequence;
use App\Models\EmailSequenceStep;
use App\Models\EmailSequenceEnrollment;
use App\Models\SentEmail;
use App\Services\EmailAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailAutomationController extends Controller
{
    public function __construct(
        private EmailAutomationService $emailService
    ) {}

    /**
     * List all email sequences
     */
    public function index(Request $request)
    {
        $query = EmailSequence::with('steps');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('trigger_type')) {
            $query->where('trigger_type', $request->trigger_type);
        }

        $sequences = $query->orderBy('created_at', 'desc')->paginate(25);

        // Add stats to each sequence
        $sequences->getCollection()->transform(function ($sequence) {
            $sequence->stats = $this->emailService->getSequenceStats($sequence);
            return $sequence;
        });

        return response()->json($sequences);
    }

    /**
     * Create a new email sequence
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|in:lead_created,client_onboarded,invoice_overdue,contract_expiring,manual',
            'trigger_conditions' => 'nullable|array',
            'target_segment' => 'nullable|in:leads,clients,partners,all',
            'steps' => 'nullable|array',
            'steps.*.subject' => 'required_with:steps|string',
            'steps.*.body' => 'required_with:steps|string',
            'steps.*.delay_days' => 'nullable|integer|min:0',
            'steps.*.delay_hours' => 'nullable|integer|min:0|max:23',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sequence = EmailSequence::create([
            'name' => $request->name,
            'description' => $request->description,
            'trigger_type' => $request->trigger_type,
            'trigger_conditions' => $request->trigger_conditions,
            'target_segment' => $request->target_segment ?? 'all',
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        // Create steps if provided
        if ($request->has('steps')) {
            foreach ($request->steps as $index => $stepData) {
                EmailSequenceStep::create([
                    'email_sequence_id' => $sequence->id,
                    'order' => $index + 1,
                    'subject' => $stepData['subject'],
                    'body' => $stepData['body'],
                    'delay_days' => $stepData['delay_days'] ?? 0,
                    'delay_hours' => $stepData['delay_hours'] ?? 0,
                    'send_conditions' => $stepData['send_conditions'] ?? null,
                    'is_active' => true,
                ]);
            }
        }

        return response()->json($sequence->load('steps'), 201);
    }

    /**
     * Show a single sequence with stats
     */
    public function show(EmailSequence $emailSequence)
    {
        $emailSequence->load(['steps', 'enrollments.enrollable']);
        $emailSequence->stats = $this->emailService->getSequenceStats($emailSequence);

        return response()->json($emailSequence);
    }

    /**
     * Update a sequence
     */
    public function update(Request $request, EmailSequence $emailSequence)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'sometimes|in:lead_created,client_onboarded,invoice_overdue,contract_expiring,manual',
            'trigger_conditions' => 'nullable|array',
            'target_segment' => 'nullable|in:leads,clients,partners,all',
            'status' => 'sometimes|in:draft,active,paused,archived',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $emailSequence->update($request->only([
            'name', 'description', 'trigger_type', 'trigger_conditions', 'target_segment', 'status'
        ]));

        return response()->json($emailSequence->load('steps'));
    }

    /**
     * Delete a sequence
     */
    public function destroy(EmailSequence $emailSequence)
    {
        $emailSequence->delete();
        return response()->json(['message' => 'Sequence deleted']);
    }

    /**
     * Activate a sequence
     */
    public function activate(EmailSequence $emailSequence)
    {
        if ($emailSequence->steps()->count() === 0) {
            return response()->json(['error' => 'Cannot activate sequence without steps'], 422);
        }

        $emailSequence->update(['status' => 'active']);
        return response()->json(['message' => 'Sequence activated', 'sequence' => $emailSequence]);
    }

    /**
     * Pause a sequence
     */
    public function pause(EmailSequence $emailSequence)
    {
        $emailSequence->update(['status' => 'paused']);
        return response()->json(['message' => 'Sequence paused', 'sequence' => $emailSequence]);
    }

    /**
     * Add a step to a sequence
     */
    public function addStep(Request $request, EmailSequence $emailSequence)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'delay_days' => 'nullable|integer|min:0',
            'delay_hours' => 'nullable|integer|min:0|max:23',
            'send_conditions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $maxOrder = $emailSequence->steps()->max('order') ?? 0;

        $step = EmailSequenceStep::create([
            'email_sequence_id' => $emailSequence->id,
            'order' => $maxOrder + 1,
            'subject' => $request->subject,
            'body' => $request->body,
            'delay_days' => $request->delay_days ?? 0,
            'delay_hours' => $request->delay_hours ?? 0,
            'send_conditions' => $request->send_conditions,
            'is_active' => true,
        ]);

        return response()->json($step, 201);
    }

    /**
     * Update a step
     */
    public function updateStep(Request $request, EmailSequenceStep $step)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'delay_days' => 'nullable|integer|min:0',
            'delay_hours' => 'nullable|integer|min:0|max:23',
            'send_conditions' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $step->update($request->only([
            'subject', 'body', 'delay_days', 'delay_hours', 'send_conditions', 'is_active'
        ]));

        return response()->json($step);
    }

    /**
     * Delete a step
     */
    public function deleteStep(EmailSequenceStep $step)
    {
        $sequenceId = $step->email_sequence_id;
        $deletedOrder = $step->order;
        
        $step->delete();

        // Reorder remaining steps
        EmailSequenceStep::where('email_sequence_id', $sequenceId)
            ->where('order', '>', $deletedOrder)
            ->decrement('order');

        return response()->json(['message' => 'Step deleted']);
    }

    /**
     * Manually enroll an entity in a sequence
     */
    public function enroll(Request $request, EmailSequence $emailSequence)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|in:lead,client',
            'entity_id' => 'required|integer',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $entityClass = match($request->entity_type) {
            'lead' => \App\Models\Lead::class,
            'client' => \App\Models\Client::class,
        };

        $entity = $entityClass::find($request->entity_id);

        if (!$entity) {
            return response()->json(['error' => 'Entity not found'], 404);
        }

        $enrollment = $this->emailService->enroll($entity, $emailSequence, $request->metadata ?? []);

        if (!$enrollment) {
            return response()->json(['error' => 'Failed to enroll entity'], 500);
        }

        return response()->json($enrollment, 201);
    }

    /**
     * Get enrollments for a sequence
     */
    public function enrollments(EmailSequence $emailSequence, Request $request)
    {
        $query = $emailSequence->enrollments()->with('enrollable');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $enrollments = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json($enrollments);
    }

    /**
     * Pause an enrollment
     */
    public function pauseEnrollment(EmailSequenceEnrollment $enrollment)
    {
        $enrollment->pause();
        return response()->json(['message' => 'Enrollment paused', 'enrollment' => $enrollment]);
    }

    /**
     * Resume an enrollment
     */
    public function resumeEnrollment(EmailSequenceEnrollment $enrollment)
    {
        $enrollment->resume();
        return response()->json(['message' => 'Enrollment resumed', 'enrollment' => $enrollment]);
    }

    /**
     * Get sent emails history
     */
    public function sentEmails(Request $request)
    {
        $query = SentEmail::with(['enrollment.sequence', 'step']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sequence_id')) {
            $query->whereHas('enrollment', function ($q) use ($request) {
                $q->where('email_sequence_id', $request->sequence_id);
            });
        }

        $emails = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json($emails);
    }

    /**
     * Get email automation stats overview
     */
    public function stats()
    {
        $totalSequences = EmailSequence::count();
        $activeSequences = EmailSequence::where('status', 'active')->count();
        $totalEnrollments = EmailSequenceEnrollment::count();
        $activeEnrollments = EmailSequenceEnrollment::where('status', 'active')->count();
        $totalSent = SentEmail::whereNotNull('sent_at')->count();
        $totalOpened = SentEmail::whereNotNull('opened_at')->count();
        $totalClicked = SentEmail::whereNotNull('clicked_at')->count();

        return response()->json([
            'sequences' => [
                'total' => $totalSequences,
                'active' => $activeSequences,
            ],
            'enrollments' => [
                'total' => $totalEnrollments,
                'active' => $activeEnrollments,
            ],
            'emails' => [
                'sent' => $totalSent,
                'opened' => $totalOpened,
                'clicked' => $totalClicked,
                'open_rate' => $totalSent > 0 ? round($totalOpened / $totalSent * 100, 2) : 0,
                'click_rate' => $totalSent > 0 ? round($totalClicked / $totalSent * 100, 2) : 0,
            ],
        ]);
    }
}
