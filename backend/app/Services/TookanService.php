<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TookanService
{
    private string $baseUrl = 'https://api.tookanapp.com/v2';
    private string $apiKey;
    private int $timezone = -60; // Italy UTC+1 = -60 minutes

    public function __construct()
    {
        $this->apiKey = config('services.tookan.api_key') ?? '';
    }

    /**
     * Get HTTP client with proper SSL settings
     * Disables SSL verification in local environment to avoid certificate issues
     */
    private function http(int $timeout = 10): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::timeout($timeout)->asForm();
        
        // Disable SSL verification in local environment
        if (app()->environment('local')) {
            $client = $client->withOptions(['verify' => false]);
        }
        
        return $client;
    }

    /**
     * Get all agents/riders with their current status
     * Status: 0 = available, 1 = offline, 2 = busy
     */
    public function getAllAgents(array $filters = []): array
    {
        $cacheKey = 'tookan_agents_' . md5(json_encode($filters));
        
        // Cache for 30 seconds for real-time dashboard
        return Cache::remember($cacheKey, 30, function () use ($filters) {
            try {
                $response = $this->http(10)->post("{$this->baseUrl}/get_all_fleets", array_merge([
                    'api_key' => $this->apiKey,
                ], $filters));

                if ($response->successful() && $response->json('status') === 200) {
                    return [
                        'success' => true,
                        'data' => $response->json('data', []),
                    ];
                }

                Log::warning('Tookan API getAllAgents error', [
                    'http_status' => $response->status(),
                    'json_status' => $response->json('status'),
                    'message' => $response->json('message'),
                    'body' => substr($response->body(), 0, 500),
                ]);

                // Handle 404 errors specifically
                if ($response->status() === 404) {
                    return [
                        'success' => false,
                        'data' => [],
                        'error' => 'Tookan API endpoint non raggiungibile. Verifica la chiave API.',
                    ];
                }

                return [
                    'success' => false,
                    'data' => [],
                    'error' => $response->json('message') ?: 'Errore API Tookan (HTTP ' . $response->status() . ')',
                ];
            } catch (\Exception $e) {
                Log::error('Tookan API exception', ['error' => $e->getMessage()]);
                return [
                    'success' => false,
                    'data' => [],
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Get agent/rider summary for dashboard
     */
    public function getAgentsSummary(): array
    {
        $result = $this->getAllAgents();
        
        if (!$result['success']) {
            return [
                'total' => 0,
                'available' => 0,
                'busy' => 0,
                'offline' => 0,
                'agents' => [],
                'error' => $result['error'] ?? 'Failed to fetch agents',
            ];
        }

        $agents = $result['data'];
        
        $summary = [
            'total' => count($agents),
            'available' => 0,
            'busy' => 0,
            'offline' => 0,
            'agents' => [],
        ];

        foreach ($agents as $agent) {
            $status = $agent['status'] ?? 1;
            
            switch ($status) {
                case 0:
                    $summary['available']++;
                    $statusLabel = 'available';
                    break;
                case 2:
                    $summary['busy']++;
                    $statusLabel = 'busy';
                    break;
                default:
                    $summary['offline']++;
                    $statusLabel = 'offline';
            }

            $summary['agents'][] = [
                'fleet_id' => $agent['fleet_id'] ?? null,
                'name' => trim(($agent['first_name'] ?? '') . ' ' . ($agent['last_name'] ?? '')),
                'phone' => $agent['phone'] ?? null,
                'email' => $agent['email'] ?? null,
                'status' => $statusLabel,
                'status_code' => $status,
                'transport_type' => $this->getTransportTypeLabel($agent['transport_type'] ?? 0),
                'latitude' => $agent['latitude'] ?? null,
                'longitude' => $agent['longitude'] ?? null,
                'last_updated' => $agent['fleet_last_updated_at'] ?? null,
                'team_id' => $agent['team_id'] ?? null,
                'tags' => $agent['tags'] ?? '',
            ];
        }

        // Sort by status: available first, then busy, then offline
        usort($summary['agents'], function ($a, $b) {
            $order = ['available' => 0, 'busy' => 1, 'offline' => 2];
            return ($order[$a['status']] ?? 3) <=> ($order[$b['status']] ?? 3);
        });

        return $summary;
    }

    /**
     * Get agent location
     */
    public function getAgentLocation(string $fleetId): ?array
    {
        try {
            $response = $this->http(10)->post("{$this->baseUrl}/get_fleet_location", [
                'api_key' => $this->apiKey,
                'fleet_id' => $fleetId,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Tookan get location error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get agent logs (online/offline history)
     */
    public function getAgentLogs(string $date, array $teamIds = []): array
    {
        try {
            $params = [
                'api_key' => $this->apiKey,
                'date' => $date,
            ];

            if (!empty($teamIds)) {
                $params['team_ids'] = $teamIds;
            }

            $response = $this->http(10)->post("{$this->baseUrl}/get_fleet_logs", $params);

            if ($response->successful() && $response->json('status') === 200) {
                return [
                    'success' => true,
                    'data' => $response->json('data', []),
                ];
            }

            return [
                'success' => false,
                'data' => [],
                'error' => $response->json('message', 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan agent logs error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'data' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all teams
     */
    public function getAllTeams(): array
    {
        $cacheKey = 'tookan_teams';
        
        return Cache::remember($cacheKey, 300, function () {
            try {
                $response = $this->http(10)->post("{$this->baseUrl}/view_all_team_only", [
                    'api_key' => $this->apiKey,
                ]);

                if ($response->successful() && $response->json('status') === 200) {
                    return [
                        'success' => true,
                        'data' => $response->json('data', []),
                    ];
                }

                Log::warning('Tookan API getAllTeams error', [
                    'http_status' => $response->status(),
                    'json_status' => $response->json('status'),
                    'message' => $response->json('message'),
                    'body' => $response->body(),
                ]);

                // Handle 404 errors specifically
                if ($response->status() === 404) {
                    return [
                        'success' => false,
                        'data' => [],
                        'error' => 'Tookan API endpoint non raggiungibile. Verifica la chiave API.',
                    ];
                }

                return [
                    'success' => false,
                    'data' => [],
                    'error' => $response->json('message') ?: 'Errore API Tookan (HTTP ' . $response->status() . ')',
                ];
            } catch (\Exception $e) {
                Log::error('Tookan teams error', ['error' => $e->getMessage()]);
                return [
                    'success' => false,
                    'data' => [],
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Get task statistics from Tookan
     */
    public function getTaskStatistics(?string $startDate = null, ?string $endDate = null): array
    {
        try {
            $params = [
                'api_key' => $this->apiKey,
            ];

            if ($startDate) {
                $params['start_date'] = $startDate;
            }
            if ($endDate) {
                $params['end_date'] = $endDate;
            }

            $response = $this->http(10)->post("{$this->baseUrl}/get_task_statistics", $params);

            if ($response->successful() && $response->json('status') === 200) {
                return [
                    'success' => true,
                    'data' => $response->json('data', []),
                ];
            }

            return [
                'success' => false,
                'data' => [],
                'error' => $response->json('message', 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan task stats error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'data' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all tasks for a date range
     */
    public function getAllTasks(string $startDate, string $endDate, int $jobType = 1): array
    {
        try {
            $response = $this->http(30)->post("{$this->baseUrl}/get_all_tasks", [
                'api_key' => $this->apiKey,
                'job_type' => $jobType, // 0=Pickup, 1=Delivery, 2=Appointment
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_pagination' => 0,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                return [
                    'success' => true,
                    'data' => $response->json('data', []),
                ];
            }

            return [
                'success' => false,
                'data' => [],
                'error' => $response->json('message', 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan get tasks error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'data' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get today's tasks summary
     */
    public function getTodayTasksSummary(): array
    {
        $today = now()->format('Y-m-d');
        $result = $this->getAllTasks($today, $today);

        if (!$result['success']) {
            return [
                'total' => 0,
                'assigned' => 0,
                'started' => 0,
                'successful' => 0,
                'failed' => 0,
                'in_progress' => 0,
                'unassigned' => 0,
                'error' => $result['error'] ?? 'Failed to fetch tasks',
            ];
        }

        $tasks = $result['data'];
        
        $summary = [
            'total' => count($tasks),
            'assigned' => 0,
            'started' => 0,
            'successful' => 0,
            'failed' => 0,
            'in_progress' => 0,
            'unassigned' => 0,
            'cancelled' => 0,
        ];

        foreach ($tasks as $task) {
            $status = $task['job_status'] ?? 6;
            
            switch ($status) {
                case 0: $summary['assigned']++; break;
                case 1: $summary['started']++; break;
                case 2: $summary['successful']++; break;
                case 3: $summary['failed']++; break;
                case 4: $summary['in_progress']++; break;
                case 6: $summary['unassigned']++; break;
                case 9: $summary['cancelled']++; break;
            }
        }

        return $summary;
    }

    /**
     * Convert transport type code to label
     */
    private function getTransportTypeLabel(int $type): string
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

    /**
     * Check if Tookan API is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Tookan API key not configured',
            ];
        }

        $result = $this->getAllTeams();
        
        return [
            'success' => $result['success'],
            'error' => $result['error'] ?? null,
            'teams_count' => count($result['data'] ?? []),
        ];
    }

    /**
     * Add a new agent/rider
     */
    public function addAgent(array $data): array
    {
        try {
            $response = $this->http(15)->post("{$this->baseUrl}/add_agent", array_merge([
                'api_key' => $this->apiKey,
                'timezone' => $this->timezone,
            ], $data));

            if ($response->successful() && $response->json('status') === 200) {
                Cache::forget('tookan_agents_' . md5(json_encode([])));
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                    'message' => 'Agent created successfully',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to create agent'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan add agent error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Edit an existing agent/rider
     */
    public function editAgent(string $fleetId, array $data): array
    {
        try {
            $response = $this->http(15)->post("{$this->baseUrl}/edit_agent", array_merge([
                'api_key' => $this->apiKey,
                'fleet_id' => $fleetId,
            ], $data));

            if ($response->successful() && $response->json('status') === 200) {
                Cache::forget('tookan_agents_' . md5(json_encode([])));
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                    'message' => 'Agent updated successfully',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to update agent'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan edit agent error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Block or unblock an agent
     */
    public function blockAgent(string $fleetId, bool $block, ?string $reason = null): array
    {
        try {
            $params = [
                'api_key' => $this->apiKey,
                'fleet_id' => $fleetId,
                'block_status' => $block ? 0 : 1, // 0 = block, 1 = unblock
            ];

            if ($block && $reason) {
                $params['block_reason'] = $reason;
            }

            $response = $this->http(10)->post("{$this->baseUrl}/block_and_unblock_fleet", $params);

            if ($response->successful() && $response->json('status') === 200) {
                Cache::forget('tookan_agents_' . md5(json_encode([])));
                return [
                    'success' => true,
                    'message' => $block ? 'Agent blocked' : 'Agent unblocked',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to update agent status'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan block agent error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete an agent
     */
    public function deleteAgent(string $fleetId): array
    {
        try {
            $response = $this->http(10)->post("{$this->baseUrl}/delete_fleet", [
                'api_key' => $this->apiKey,
                'fleet_id' => $fleetId,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                Cache::forget('tookan_agents_' . md5(json_encode([])));
                return [
                    'success' => true,
                    'message' => 'Agent deleted successfully',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to delete agent'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan delete agent error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get agent profile with details
     */
    public function getAgentProfile(string $fleetId): array
    {
        try {
            $response = $this->http(10)->post("{$this->baseUrl}/view_fleet_profile", [
                'api_key' => $this->apiKey,
                'fleet_id' => $fleetId,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to get agent profile'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan agent profile error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get tasks assigned to a specific agent
     */
    public function getAgentTasks(string $fleetId, string $startDate, string $endDate): array
    {
        try {
            $response = $this->http(30)->post("{$this->baseUrl}/get_all_tasks", [
                'api_key' => $this->apiKey,
                'fleet_id' => $fleetId,
                'job_type' => 1, // Delivery
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_pagination' => 0,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                $tasks = $response->json('data', []);
                
                // Format tasks for frontend
                $formattedTasks = array_map(function ($task) {
                    return [
                        'job_id' => $task['job_id'] ?? null,
                        'order_id' => $task['order_id'] ?? null,
                        'status' => $this->getTaskStatusLabel($task['job_status'] ?? 6),
                        'status_code' => $task['job_status'] ?? 6,
                        'customer_name' => $task['customer_username'] ?? 'N/A',
                        'customer_phone' => $task['customer_phone'] ?? null,
                        'customer_address' => $task['job_address'] ?? null,
                        'pickup_address' => $task['job_pickup_address'] ?? null,
                        'scheduled_time' => $task['job_delivery_datetime'] ?? null,
                        'started_at' => $task['started_datetime'] ?? null,
                        'completed_at' => $task['completed_datetime'] ?? null,
                        'description' => $task['job_description'] ?? null,
                        'tracking_link' => $task['tracking_link'] ?? null,
                    ];
                }, $tasks);

                return [
                    'success' => true,
                    'data' => $formattedTasks,
                ];
            }

            return [
                'success' => false,
                'data' => [],
                'error' => $response->json('message', 'Failed to get agent tasks'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan agent tasks error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'data' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Assign a task to an agent
     */
    public function assignTaskToAgent(int $jobId, string $fleetId, int $teamId): array
    {
        try {
            $response = $this->http(10)->post("{$this->baseUrl}/assign_task", [
                'api_key' => $this->apiKey,
                'job_id' => $jobId,
                'fleet_id' => $fleetId,
                'team_id' => $teamId,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                return [
                    'success' => true,
                    'message' => 'Task assigned successfully',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to assign task'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan assign task error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get task details
     */
    public function getTaskDetails(array $jobIds): array
    {
        try {
            $response = $this->http(15)->post("{$this->baseUrl}/get_task_details", [
                'api_key' => $this->apiKey,
                'job_ids' => $jobIds,
                'include_task_history' => 1,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                return [
                    'success' => true,
                    'data' => $response->json('data', []),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to get task details'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan task details error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update task status
     */
    public function updateTaskStatus(int $jobId, int $status): array
    {
        try {
            $response = $this->http(10)->post("{$this->baseUrl}/update_task_status", [
                'api_key' => $this->apiKey,
                'job_id' => $jobId,
                'job_status' => $status,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                return [
                    'success' => true,
                    'message' => 'Task status updated',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to update task status'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan update task error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send notification to agent
     */
    public function sendNotificationToAgent(array $fleetIds, string $message): array
    {
        try {
            $response = $this->http(10)->post("{$this->baseUrl}/send_notification", [
                'api_key' => $this->apiKey,
                'fleet_ids' => $fleetIds,
                'message' => $message,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                return [
                    'success' => true,
                    'message' => 'Notification sent',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to send notification'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan notification error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get task status label
     */
    private function getTaskStatusLabel(int $status): string
    {
        return match ($status) {
            0 => 'assigned',
            1 => 'started',
            2 => 'successful',
            3 => 'failed',
            4 => 'in_progress',
            6 => 'unassigned',
            7 => 'accepted',
            8 => 'declined',
            9 => 'cancelled',
            default => 'unknown',
        };
    }

    /**
     * Get transport type code from label
     */
    public function getTransportTypeCode(string $label): int
    {
        return match (strtolower($label)) {
            'car' => 1,
            'motorcycle' => 2,
            'bicycle' => 3,
            'scooter' => 4,
            'foot' => 5,
            'truck' => 6,
            default => 2, // motorcycle as default
        };
    }

    /**
     * Create a new team
     */
    public function createTeam(string $teamName): array
    {
        try {
            $response = $this->http(10)->post("{$this->baseUrl}/create_team", [
                'api_key' => $this->apiKey,
                'team_name' => $teamName,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                Cache::forget('tookan_teams');
                return [
                    'success' => true,
                    'data' => $response->json('data'),
                    'message' => 'Team created successfully',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to create team'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan create team error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update a team
     */
    public function updateTeam(int $teamId, string $teamName): array
    {
        try {
            $response = $this->http(10)->post("{$this->baseUrl}/update_team", [
                'api_key' => $this->apiKey,
                'team_id' => $teamId,
                'team_name' => $teamName,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                Cache::forget('tookan_teams');
                return [
                    'success' => true,
                    'message' => 'Team updated successfully',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to update team'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan update team error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a team
     */
    public function deleteTeam(int $teamId): array
    {
        try {
            $response = $this->http(10)->post("{$this->baseUrl}/delete_team", [
                'api_key' => $this->apiKey,
                'team_id' => $teamId,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                Cache::forget('tookan_teams');
                return [
                    'success' => true,
                    'message' => 'Team deleted successfully',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to delete team'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan delete team error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Assign rider to a team
     */
    public function assignRiderToTeam(string $fleetId, int $teamId): array
    {
        try {
            $response = $this->http(10)->post("{$this->baseUrl}/assign_team", [
                'api_key' => $this->apiKey,
                'fleet_id' => $fleetId,
                'team_id' => $teamId,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                Cache::forget('tookan_agents_' . md5(json_encode([])));
                return [
                    'success' => true,
                    'message' => 'Rider assigned to team successfully',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to assign rider to team'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan assign team error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get riders by team
     */
    public function getRidersByTeam(int $teamId): array
    {
        try {
            $response = $this->http(10)->post("{$this->baseUrl}/get_available_agents", [
                'api_key' => $this->apiKey,
                'team_id' => $teamId,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                return [
                    'success' => true,
                    'data' => $response->json('data', []),
                ];
            }

            return [
                'success' => false,
                'data' => [],
                'error' => $response->json('message', 'Failed to get riders by team'),
            ];
        } catch (\Exception $e) {
            Log::error('Tookan get riders by team error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'data' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel a task on Tookan (set status to 9 = cancelled)
     */
    public function cancelTask(int $jobId): array
    {
        return $this->updateTaskStatus($jobId, 9);
    }

    /**
     * Find a Tookan task by matching delivery details (date, rider, address)
     * Returns the job_id if found, null otherwise
     */
    public function findTaskByDetails(string $date, ?string $fleetId = null, ?string $address = null): ?int
    {
        try {
            $params = [
                'api_key' => $this->apiKey,
                'job_type' => 1, // Delivery
                'start_date' => $date,
                'end_date' => $date,
                'is_pagination' => 0,
            ];

            if ($fleetId) {
                $params['fleet_id'] = $fleetId;
            }

            $response = $this->http(30)->post("{$this->baseUrl}/get_all_tasks", $params);

            if (!$response->successful() || $response->json('status') !== 200) {
                return null;
            }

            $tasks = $response->json('data', []);

            if (empty($tasks)) {
                return null;
            }

            // If we have an address, try to match by address similarity
            if ($address) {
                $normalizedAddress = strtolower(trim($address));
                foreach ($tasks as $task) {
                    $taskAddress = strtolower(trim($task['job_address'] ?? ''));
                    if ($taskAddress && str_contains($taskAddress, $normalizedAddress) || str_contains($normalizedAddress, $taskAddress)) {
                        return (int) $task['job_id'];
                    }
                }
            }

            // If only one task matches the fleet_id + date, return it
            if ($fleetId && count($tasks) === 1) {
                return (int) $tasks[0]['job_id'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Tookan findTaskByDetails error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get all riders with real-time location (bypasses cache for live tracking)
     */
    public function getAllAgentsRealtime(): array
    {
        try {
            $response = $this->http(10)->post("{$this->baseUrl}/get_all_fleets", [
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful() && $response->json('status') === 200) {
                $agents = $response->json('data', []);

                // Add computed online status based on location_update_datetime
                $now = \Carbon\Carbon::now();
                foreach ($agents as &$agent) {
                    $lastUpdate = $agent['location_update_datetime'] ?? null;
                    if ($lastUpdate) {
                        $lastUpdateTime = \Carbon\Carbon::parse($lastUpdate);
                        // Use absolute diff - always positive
                        $minutesAgo = abs($lastUpdateTime->diffInMinutes($now));
                        $agent['is_online'] = $minutesAgo <= 30; // Online if updated in last 30 mins
                        $agent['minutes_since_update'] = (int) $minutesAgo;
                    } else {
                        $agent['is_online'] = false;
                        $agent['minutes_since_update'] = null;
                    }
                }

                return [
                    'success' => true,
                    'data' => $agents,
                ];
            }

            return [
                'success' => false,
                'data' => [],
                'error' => $response->json('message') ?: 'Errore API Tookan',
            ];
        } catch (\Exception $e) {
            Log::error('Tookan API realtime exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'data' => [],
                'error' => $e->getMessage(),
            ];
        }
    }
}

