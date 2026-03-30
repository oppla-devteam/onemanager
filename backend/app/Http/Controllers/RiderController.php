<?php

namespace App\Http\Controllers;

use App\Http\Traits\CsvExportTrait;
use App\Models\Rider;
use App\Services\TookanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class RiderController extends Controller
{
    use CsvExportTrait;

    public function __construct(
        private TookanService $tookanService
    ) {}

    /**
     * Esporta riders in formato CSV
     */
    public function export()
    {
        $riders = Rider::orderBy('first_name')->get();

        $data = [];
        foreach ($riders as $r) {
            $data[] = [
                'ID' => $r->id,
                'Fleet ID' => $r->fleet_id ?? '',
                'Username' => $r->username ?? '',
                'Nome' => $r->first_name ?? '',
                'Cognome' => $r->last_name ?? '',
                'Email' => $r->email ?? '',
                'Telefono' => $r->phone ?? '',
                'Stato' => $r->status ?? '',
                'Bloccato' => $r->is_blocked ? 'Sì' : 'No',
                'Tipo Trasporto' => $r->transport_type ?? '',
                'Team' => $r->team_name ?? '',
                'Ultimo Sync' => $r->last_synced_at ? $r->last_synced_at->format('d/m/Y H:i') : '',
            ];
        }

        return $this->streamCsv($data, 'riders_' . date('Y-m-d_His') . '.csv');
    }

    /**
     * Get all riders with their status (from local database)
     */
    public function index(Request $request)
    {
        // Build query from local database
        $query = Rider::query();

        // Apply filters
        if ($request->has('status')) {
            $statusMap = [
                '0' => 'available',
                '1' => 'offline',
                '2' => 'busy',
            ];
            $status = $statusMap[$request->input('status')] ?? $request->input('status');
            $query->where('status', $status);
        }

        if ($request->has('team_id')) {
            $query->where('team_id', $request->input('team_id'));
        }

        // Check data freshness
        $lastSyncTime = Rider::max('last_synced_at');
        $isStale = !$lastSyncTime || Carbon::parse($lastSyncTime)->lt(now()->subMinutes(15));

        // If no data exists or data is very stale (>15 mins), fallback to Tookan API
        if (Rider::count() === 0 || $isStale) {
            if ($this->tookanService->isConfigured()) {
                // Trigger background sync
                Artisan::queue('tookan:sync-riders', ['--source' => 'fallback']);

                // If no local data at all, fetch from Tookan directly
                if (Rider::count() === 0) {
                    $result = $this->tookanService->getAllAgents();
                    if ($result['success']) {
                        // Format and return Tookan data
                        $riders = $this->formatTookanRiders($result['data']);
                        return response()->json([
                            'success' => true,
                            'data' => $riders,
                            'summary' => $this->calculateSummary($riders),
                            'last_synced_at' => null,
                            'using_fallback' => true,
                        ]);
                    } else {
                        // Tookan API call failed and no local data
                        return response()->json([
                            'success' => false,
                            'error' => $result['error'] ?? 'Errore di connessione a Tookan',
                            'data' => [],
                            'summary' => ['total' => 0, 'available' => 0, 'busy' => 0, 'offline' => 0],
                        ], 503);
                    }
                }
            } else if (Rider::count() === 0) {
                // Tookan not configured and no local data
                return response()->json([
                    'success' => false,
                    'error' => 'API Tookan non configurata. Contatta l\'amministratore.',
                    'data' => [],
                    'summary' => ['total' => 0, 'available' => 0, 'busy' => 0, 'offline' => 0],
                ], 503);
            }
        }

        // Get riders from local database
        $riders = $query->orderBy('status_code', 'asc')
                       ->orderBy('first_name', 'asc')
                       ->get()
                       ->map(function ($rider) {
                           return [
                               'id' => $rider->fleet_id,
                               'fleet_id' => $rider->fleet_id,
                               'username' => $rider->username,
                               'first_name' => $rider->first_name,
                               'last_name' => $rider->last_name,
                               'name' => $rider->name,
                               'email' => $rider->email,
                               'phone' => $rider->phone,
                               'status' => $rider->status,
                               'status_code' => $rider->status_code,
                               'is_blocked' => $rider->is_blocked,
                               'transport_type' => $rider->transport_type,
                               'transport_type_code' => $rider->transport_type_code,
                               'latitude' => $rider->latitude,
                               'longitude' => $rider->longitude,
                               'last_updated' => $rider->fleet_last_updated_at?->toIso8601String(),
                               'team_id' => $rider->team_id,
                               'team_name' => $rider->team_name,
                               'tags' => $rider->tags,
                               'profile_image' => $rider->profile_image,
                           ];
                       })
                       ->toArray();

        $summary = [
            'total' => Rider::count(),
            'available' => Rider::available()->count(),
            'busy' => Rider::busy()->count(),
            'offline' => Rider::offline()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $riders,
            'summary' => $summary,
            'last_synced_at' => $lastSyncTime,
            'is_stale' => $isStale,
            'using_fallback' => false,
        ]);
    }

    /**
     * Manually trigger rider sync
     */
    public function syncNow(Request $request)
    {
        try {
            // Run sync command synchronously
            Artisan::call('tookan:sync-riders', ['--source' => 'manual', '--force' => true]);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Rider sync completed successfully',
                'last_synced_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Format Tookan riders for response
     */
    private function formatTookanRiders(array $agents): array
    {
        return array_map(function ($agent) {
            $status = $agent['status'] ?? 1;
            $statusLabel = match ($status) {
                0 => 'available',
                2 => 'busy',
                default => 'offline',
            };

            return [
                'id' => $agent['fleet_id'] ?? null,
                'fleet_id' => $agent['fleet_id'] ?? null,
                'username' => $agent['username'] ?? null,
                'first_name' => $agent['first_name'] ?? '',
                'last_name' => $agent['last_name'] ?? '',
                'name' => trim(($agent['first_name'] ?? '') . ' ' . ($agent['last_name'] ?? '')),
                'email' => $agent['email'] ?? null,
                'phone' => $agent['phone'] ?? null,
                'status' => $statusLabel,
                'status_code' => $status,
                'is_blocked' => $agent['is_blocked'] ?? false,
                'transport_type' => $this->getTransportLabel($agent['transport_type'] ?? 0),
                'transport_type_code' => $agent['transport_type'] ?? 0,
                'latitude' => $agent['latitude'] ?? null,
                'longitude' => $agent['longitude'] ?? null,
                'last_updated' => $agent['fleet_last_updated_at'] ?? null,
                'team_id' => $agent['team_id'] ?? null,
                'team_name' => $agent['team_name'] ?? null,
                'tags' => $agent['tags'] ?? '',
                'profile_image' => $agent['fleet_image'] ?? null,
            ];
        }, $agents);
    }

    /**
     * Helper: Calculate summary from riders array
     */
    private function calculateSummary(array $riders): array
    {
        return [
            'total' => count($riders),
            'available' => count(array_filter($riders, fn($r) => $r['status'] === 'available')),
            'busy' => count(array_filter($riders, fn($r) => $r['status'] === 'busy')),
            'offline' => count(array_filter($riders, fn($r) => $r['status'] === 'offline')),
        ];
    }

    /**
     * Get rider profile with details
     */
    public function show(string $fleetId)
    {
        $result = $this->tookanService->getAgentProfile($fleetId);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to fetch rider profile',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Get tasks assigned to a rider
     */
    public function getTasks(Request $request, string $fleetId)
    {
        $startDate = $request->input('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));

        $result = $this->tookanService->getAgentTasks($fleetId, $startDate, $endDate);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to fetch rider tasks',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Get rider location
     */
    public function getLocation(string $fleetId)
    {
        $location = $this->tookanService->getAgentLocation($fleetId);

        if (!$location) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch rider location',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $location,
        ]);
    }

    /**
     * Create a new rider
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'string|max:100',
            'username' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'email|max:255',
            'password' => 'required|string|min:6',
            'team_id' => 'required|integer',
            'transport_type' => 'string|in:car,motorcycle,bicycle,scooter,foot,truck',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name', ''),
            'username' => $request->input('username'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email', ''),
            'password' => $request->input('password'),
            'team_id' => $request->input('team_id'),
            'transport_type' => $this->tookanService->getTransportTypeCode(
                $request->input('transport_type', 'motorcycle')
            ),
        ];

        $result = $this->tookanService->addAgent($data);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to create rider',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rider created successfully',
            'data' => $result['data'],
        ], 201);
    }

    /**
     * Update a rider
     */
    public function update(Request $request, string $fleetId)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'string|max:100',
            'last_name' => 'string|max:100',
            'phone' => 'string|max:20',
            'email' => 'email|max:255',
            'team_id' => 'integer',
            'transport_type' => 'string|in:car,motorcycle,bicycle,scooter,foot,truck',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = array_filter([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'team_id' => $request->input('team_id'),
            'transport_type' => $request->has('transport_type') 
                ? $this->tookanService->getTransportTypeCode($request->input('transport_type'))
                : null,
        ], fn($v) => $v !== null);

        $result = $this->tookanService->editAgent($fleetId, $data);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to update rider',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rider updated successfully',
        ]);
    }

    /**
     * Block or unblock a rider
     */
    public function toggleBlock(Request $request, string $fleetId)
    {
        $block = $request->boolean('block', true);
        $reason = $request->input('reason');

        $result = $this->tookanService->blockAgent($fleetId, $block, $reason);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to update rider status',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $block ? 'Rider blocked' : 'Rider unblocked',
        ]);
    }

    /**
     * Delete a rider
     */
    public function destroy(string $fleetId)
    {
        $result = $this->tookanService->deleteAgent($fleetId);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to delete rider',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rider deleted successfully',
        ]);
    }

    /**
     * Send notification to rider(s)
     */
    public function sendNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fleet_ids' => 'required|array',
            'fleet_ids.*' => 'required|string',
            'message' => 'required|string|min:4|max:160',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->tookanService->sendNotificationToAgent(
            $request->input('fleet_ids'),
            $request->input('message')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to send notification',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully',
        ]);
    }

    /**
     * Get all teams
     */
    public function getTeams()
    {
        $result = $this->tookanService->getAllTeams();

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to fetch teams',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Create a new team
     */
    public function createTeam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->tookanService->createTeam($request->input('team_name'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to create team',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Team created successfully',
            'data' => $result['data'] ?? null,
        ], 201);
    }

    /**
     * Update a team
     */
    public function updateTeam(Request $request, int $teamId)
    {
        $validator = Validator::make($request->all(), [
            'team_name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->tookanService->updateTeam($teamId, $request->input('team_name'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to update team',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Team updated successfully',
        ]);
    }

    /**
     * Delete a team
     */
    public function deleteTeam(int $teamId)
    {
        $result = $this->tookanService->deleteTeam($teamId);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to delete team',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Team deleted successfully',
        ]);
    }

    /**
     * Assign rider to team
     */
    public function assignRiderToTeam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fleet_id' => 'required|string',
            'team_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->tookanService->assignRiderToTeam(
            $request->input('fleet_id'),
            $request->input('team_id')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to assign rider to team',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rider assigned to team successfully',
        ]);
    }

    /**
     * Get riders with real-time location for map
     */
    public function getRealtime()
    {
        $result = $this->tookanService->getAllAgentsRealtime();

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to fetch realtime data',
            ], 500);
        }

        // Format for map display
        $riders = array_map(function ($agent) {
            return [
                'fleet_id' => $agent['fleet_id'] ?? null,
                'name' => $agent['name'] ?? trim(($agent['first_name'] ?? '') . ' ' . ($agent['last_name'] ?? '')),
                'phone' => $agent['phone'] ?? null,
                'latitude' => $agent['latitude'] ?? null,
                'longitude' => $agent['longitude'] ?? null,
                'status' => $agent['status'] ?? 1,
                'is_available' => $agent['is_available'] ?? false,
                'is_online' => $agent['is_online'] ?? false,
                'minutes_since_update' => $agent['minutes_since_update'] ?? null,
                'location_update_datetime' => $agent['location_update_datetime'] ?? null,
                'battery_level' => $agent['battery_level'] ?? null,
                'transport_type' => $agent['transport_type'] ?? 0,
                'team_id' => $agent['team_id'] ?? null,
            ];
        }, $result['data']);

        // Filter only riders with valid coordinates
        $ridersWithLocation = array_filter($riders, fn($r) => $r['latitude'] && $r['longitude']);

        // Calculate summary
        $summary = [
            'total' => count($riders),
            'with_location' => count($ridersWithLocation),
            'online' => count(array_filter($riders, fn($r) => $r['is_online'])),
            'available' => count(array_filter($riders, fn($r) => $r['status'] === 0)),
        ];

        return response()->json([
            'success' => true,
            'data' => array_values($ridersWithLocation),
            'summary' => $summary,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Assign task to rider
     */
    public function assignTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|integer',
            'fleet_id' => 'required|string',
            'team_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->tookanService->assignTaskToAgent(
            $request->input('job_id'),
            $request->input('fleet_id'),
            $request->input('team_id')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to assign task',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task assigned successfully',
        ]);
    }

    /**
     * Get all unassigned tasks
     */
    public function getUnassignedTasks(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));

        $result = $this->tookanService->getAllTasks($startDate, $endDate);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to fetch tasks',
            ], 500);
        }

        // Filter only unassigned tasks (status 6)
        $unassigned = array_filter($result['data'], fn($task) => ($task['job_status'] ?? 0) === 6);

        $tasks = array_map(function ($task) {
            return [
                'job_id' => $task['job_id'] ?? null,
                'order_id' => $task['order_id'] ?? null,
                'customer_name' => $task['customer_username'] ?? 'N/A',
                'customer_phone' => $task['customer_phone'] ?? null,
                'customer_address' => $task['job_address'] ?? null,
                'pickup_address' => $task['job_pickup_address'] ?? null,
                'scheduled_time' => $task['job_delivery_datetime'] ?? null,
                'description' => $task['job_description'] ?? null,
            ];
        }, array_values($unassigned));

        return response()->json([
            'success' => true,
            'data' => $tasks,
            'count' => count($tasks),
        ]);
    }

    /**
     * Get rider activity logs
     */
    public function getLogs(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $teamIds = $request->input('team_ids', []);

        $result = $this->tookanService->getAgentLogs($date, $teamIds);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to fetch logs',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'date' => $date,
        ]);
    }

    /**
     * Test Tookan connection
     */
    public function testConnection()
    {
        $result = $this->tookanService->testConnection();

        return response()->json($result);
    }

    /**
     * Helper: Get transport label
     */
    private function getTransportLabel(int $type): string
    {
        return match ($type) {
            1 => 'car',
            2 => 'motorcycle',
            3 => 'bicycle',
            4 => 'scooter',
            5 => 'foot',
            6 => 'truck',
            default => 'unknown',
        };
    }
}
