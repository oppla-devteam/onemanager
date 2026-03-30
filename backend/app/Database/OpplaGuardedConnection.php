<?php

namespace App\Database;

use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class OpplaGuardedConnection extends PostgresConnection
{
    /**
     * Run a select statement against the database.
     * Allow all SELECT queries without confirmation.
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return parent::select($query, $bindings, $useReadPdo);
    }

    /**
     * Run an insert statement against the database.
     * REQUIRES confirmation token.
     */
    public function insert($query, $bindings = [])
    {
        $this->requireConfirmation('INSERT', $query, $bindings);
        return parent::insert($query, $bindings);
    }

    /**
     * Run an update statement against the database.
     * REQUIRES confirmation token.
     */
    public function update($query, $bindings = [])
    {
        $this->requireConfirmation('UPDATE', $query, $bindings);
        return parent::update($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     * REQUIRES confirmation token.
     */
    public function delete($query, $bindings = [])
    {
        $this->requireConfirmation('DELETE', $query, $bindings);
        return parent::delete($query, $bindings);
    }

    /**
     * Execute a raw statement - guard against writes
     */
    public function statement($query, $bindings = [])
    {
        // Detect write operations in raw statements
        if ($this->isWriteOperation($query)) {
            $this->requireConfirmation('STATEMENT', $query, $bindings);
        }
        return parent::statement($query, $bindings);
    }

    /**
     * Require confirmation token for write operations
     */
    protected function requireConfirmation(string $operation, string $query, array $bindings): void
    {
        // Check for confirmation token in current request context
        $token = request()->header('X-Oppla-Confirmation-Token');

        if (!$token) {
            throw new Exception(
                "OPPLA WRITE BLOCKED: {$operation} operation requires user confirmation. " .
                "Use OpplaWriteService to request confirmation first."
            );
        }

        // Verify token exists in cache and matches the operation
        $cachedData = Cache::get("oppla_confirm:{$token}");

        if (!$cachedData) {
            throw new Exception(
                "OPPLA WRITE BLOCKED: Invalid or expired confirmation token"
            );
        }

        // Verify operation hash matches (prevents token reuse for different operations)
        $operationHash = hash('sha256', $operation . $query . json_encode($bindings));

        if ($cachedData['hash'] !== $operationHash) {
            throw new Exception(
                "OPPLA WRITE BLOCKED: Confirmation token does not match operation"
            );
        }

        // Token is valid - consume it (one-time use)
        Cache::forget("oppla_confirm:{$token}");

        // Log the confirmed operation
        Log::info('[OpplaGuard] Confirmed write operation', [
            'operation' => $operation,
            'query' => $query,
            'bindings' => $bindings,
            'user' => auth()->user()?->email ?? 'system',
            'ip' => request()->ip(),
            'timestamp' => now()
        ]);
    }

    /**
     * Detect if a query is a write operation
     */
    protected function isWriteOperation(string $query): bool
    {
        $query = trim(strtoupper($query));
        $writePatterns = ['INSERT', 'UPDATE', 'DELETE', 'TRUNCATE', 'DROP', 'ALTER', 'CREATE'];

        foreach ($writePatterns as $pattern) {
            if (str_starts_with($query, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
