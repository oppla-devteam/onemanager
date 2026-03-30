<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class OpplaWriteService
{
    protected string $connection = 'oppla_guarded';

    /**
     * Request confirmation for a write operation
     * Returns preview data and confirmation token
     */
    public function requestConfirmation(
        string $operation,
        string $table,
        array $data,
        ?array $conditions = null
    ): array {
        // Generate operation preview
        $preview = $this->generatePreview($operation, $table, $data, $conditions);

        // Generate unique token
        $token = Str::random(64);

        // Build query and bindings (without executing)
        [$query, $bindings] = $this->buildQuery($operation, $table, $data, $conditions);

        // Calculate operation hash
        $operationHash = hash('sha256', $operation . $query . json_encode($bindings));

        // Store confirmation request in cache (5 minute expiry)
        Cache::put("oppla_confirm:{$token}", [
            'hash' => $operationHash,
            'operation' => $operation,
            'query' => $query,
            'bindings' => $bindings,
            'preview' => $preview,
            'user' => auth()->user()?->email ?? 'system',
            'created_at' => now()
        ], now()->addMinutes(5));

        Log::info('[OpplaWrite] Confirmation requested', [
            'operation' => $operation,
            'table' => $table,
            'user' => auth()->user()?->email ?? 'system',
            'token' => substr($token, 0, 16) . '...' // Log only first 16 chars for security
        ]);

        return [
            'confirmation_required' => true,
            'token' => $token,
            'operation' => $operation,
            'table' => $table,
            'preview' => $preview,
            'expires_at' => now()->addMinutes(5)->toIso8601String()
        ];
    }

    /**
     * Execute operation with confirmation token
     */
    public function executeWithConfirmation(string $token): array
    {
        $cachedData = Cache::get("oppla_confirm:{$token}");

        if (!$cachedData) {
            throw new Exception('Invalid or expired confirmation token');
        }

        $operation = $cachedData['operation'];
        $query = $cachedData['query'];
        $bindings = $cachedData['bindings'];

        // Execute the operation with the token in request context
        request()->headers->set('X-Oppla-Confirmation-Token', $token);

        try {
            $result = match ($operation) {
                'INSERT' => DB::connection($this->connection)->insert($query, $bindings),
                'UPDATE' => DB::connection($this->connection)->update($query, $bindings),
                'DELETE' => DB::connection($this->connection)->delete($query, $bindings),
                default => throw new Exception("Unsupported operation: {$operation}")
            };

            Log::info('[OpplaWrite] Operation executed successfully', [
                'operation' => $operation,
                'affected_rows' => $result,
                'user' => auth()->user()?->email ?? 'system'
            ]);

            return [
                'success' => true,
                'operation' => $operation,
                'affected_rows' => $result,
                'executed_at' => now()->toIso8601String()
            ];
        } catch (Exception $e) {
            Log::error('[OpplaWrite] Execution failed', [
                'error' => $e->getMessage(),
                'operation' => $operation,
                'user' => auth()->user()?->email ?? 'system'
            ]);

            throw $e;
        }
    }

    /**
     * Generate human-readable preview of operation
     */
    protected function generatePreview(
        string $operation,
        string $table,
        array $data,
        ?array $conditions
    ): array {
        $preview = [
            'operation_type' => $operation,
            'table' => $table,
            'description' => $this->getOperationDescription($operation, $table, $data, $conditions)
        ];

        switch ($operation) {
            case 'INSERT':
                $preview['data'] = $data;
                $preview['summary'] = "Insert 1 record into {$table}";
                break;

            case 'UPDATE':
                $preview['changes'] = $data;
                $preview['conditions'] = $conditions;
                $preview['summary'] = "Update records in {$table} where " . $this->formatConditions($conditions);
                break;

            case 'DELETE':
                $preview['conditions'] = $conditions;
                $preview['summary'] = "Delete records from {$table} where " . $this->formatConditions($conditions);
                break;
        }

        return $preview;
    }

    /**
     * Build SQL query and bindings without executing
     */
    protected function buildQuery(
        string $operation,
        string $table,
        array $data,
        ?array $conditions
    ): array {
        return match ($operation) {
            'INSERT' => $this->buildInsertQuery($table, $data),
            'UPDATE' => $this->buildUpdateQuery($table, $data, $conditions),
            'DELETE' => $this->buildDeleteQuery($table, $conditions),
            default => throw new Exception("Unsupported operation: {$operation}")
        };
    }

    /**
     * Build INSERT query
     */
    protected function buildInsertQuery(string $table, array $data): array
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        return [$query, array_values($data)];
    }

    /**
     * Build UPDATE query
     */
    protected function buildUpdateQuery(string $table, array $data, ?array $conditions): array
    {
        if (!$conditions) {
            throw new Exception('UPDATE operation requires conditions (WHERE clause)');
        }

        $setClauses = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $setClauses[] = "{$column} = ?";
            $bindings[] = $value;
        }

        $whereClauses = [];
        foreach ($conditions as $column => $value) {
            $whereClauses[] = "{$column} = ?";
            $bindings[] = $value;
        }

        $query = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setClauses),
            implode(' AND ', $whereClauses)
        );

        return [$query, $bindings];
    }

    /**
     * Build DELETE query
     */
    protected function buildDeleteQuery(string $table, ?array $conditions): array
    {
        if (!$conditions) {
            throw new Exception('DELETE operation requires conditions (WHERE clause) for safety');
        }

        $whereClauses = [];
        $bindings = [];

        foreach ($conditions as $column => $value) {
            $whereClauses[] = "{$column} = ?";
            $bindings[] = $value;
        }

        $query = sprintf(
            "DELETE FROM %s WHERE %s",
            $table,
            implode(' AND ', $whereClauses)
        );

        return [$query, $bindings];
    }

    /**
     * Format conditions for human-readable display
     */
    protected function formatConditions(?array $conditions): string
    {
        if (!$conditions) {
            return 'ALL RECORDS (⚠️ NO CONDITIONS)';
        }

        $formatted = [];
        foreach ($conditions as $key => $value) {
            $displayValue = is_null($value) ? 'NULL' : $value;
            $formatted[] = "{$key} = {$displayValue}";
        }

        return implode(' AND ', $formatted);
    }

    /**
     * Get operation description
     */
    protected function getOperationDescription(
        string $operation,
        string $table,
        array $data,
        ?array $conditions
    ): string {
        return match ($operation) {
            'INSERT' => "Creating new record in Oppla's {$table} table",
            'UPDATE' => "Updating records in Oppla's {$table} table",
            'DELETE' => "Deleting records from Oppla's {$table} table",
            default => "Performing {$operation} on {$table}"
        };
    }
}
