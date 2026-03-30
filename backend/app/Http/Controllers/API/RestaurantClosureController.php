<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Models\MassClosureBatch;
use App\Models\MassClosureHolidayMapping;

class RestaurantClosureController extends Controller
{
    /**
     * Chiude tutti i ristoranti OPPLA per un periodo specifico
     * Esegue lo script Python in modalità headless
     * 
     * POST /api/restaurants/close-period
     * 
     * Body:
     * {
     *   "start_date": "2026-08-01T18:00",
     *   "end_date": "2026-08-31T11:00",
     *   "reason": "Chiusura estiva"
     * }
     */
    public function closePeriod(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date_format:Y-m-d\TH:i',
            'end_date' => 'required|date_format:Y-m-d\TH:i|after:start_date',
            'reason' => 'nullable|string|max:255',
        ]);

        Log::info('[RestaurantClosure] Richiesta chiusura ristoranti', $validated);

        try {
            // Crea un batch ID univoco
            $batchId = 'batch_' . date('Ymd_His') . '_' . Str::random(8);

            // Crea il record del batch nel database
            $batch = MassClosureBatch::create([
                'batch_id' => $batchId,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'reason' => $validated['reason'] ?? 'Chiusura programmata',
                'status' => 'running',
                'total_restaurants' => 0,
                'successful_closures' => 0,
                'failed_closures' => 0,
            ]);

            Log::info('[RestaurantClosure] Batch creato', ['batch_id' => $batchId]);

            // Path allo script wrapper
            $wrapperScript = base_path('scripts/run_closure.sh');

            if (!file_exists($wrapperScript)) {
                Log::error('[RestaurantClosure] Script wrapper non trovato', ['path' => $wrapperScript]);
                $batch->update(['status' => 'failed']);
                return response()->json([
                    'success' => false,
                    'message' => 'Script di chiusura non trovato',
                ], 500);
            }

            $reason = $validated['reason'] ?? 'Chiusura programmata';

            // API URL per il callback del Python script
            $apiUrl = url('/');

            // ID univoco per tracciare il job
            $jobId = uniqid('closure_', true);

            // Path per log e output
            $outputFile = storage_path("logs/closure_{$jobId}.log");

            // Usa lo script wrapper bash che gestisce xvfb e timezone
            $shellCommand = sprintf(
                '%s %s %s %s %s %s',
                escapeshellarg($wrapperScript),
                escapeshellarg($validated['start_date']),
                escapeshellarg($validated['end_date']),
                escapeshellarg($reason),
                escapeshellarg($batchId),
                escapeshellarg($apiUrl)
            );

            // Esegui in background e ottieni il PID
            $pid = trim(shell_exec($shellCommand));

            Log::info('[RestaurantClosure] Processo avviato in background', [
                'job_id' => $jobId,
                'batch_id' => $batchId,
                'pid' => $pid,
                'output_file' => $outputFile
            ]);

            // Salva info job in cache per status check
            cache()->put("restaurant_closure_{$jobId}", [
                'status' => 'running',
                'batch_id' => $batchId,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'reason' => $reason,
                'started_at' => now()->toIso8601String(),
                'pid' => $pid,
                'output_file' => $outputFile,
            ], now()->addHours(24));

            return response()->json([
                'success' => true,
                'message' => 'Chiusura ristoranti avviata in background',
                'data' => [
                    'job_id' => $jobId,
                    'batch_id' => $batchId,
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'reason' => $reason,
                    'check_status_url' => url("/api/restaurants/close-status/{$jobId}"),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[RestaurantClosure] Errore avvio chiusura', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'avvio della chiusura: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Controlla lo stato di un job di chiusura
     *
     * GET /api/restaurants/close-status/{jobId}
     */
    public function checkStatus(string $jobId)
    {
        $status = cache()->get("restaurant_closure_{$jobId}");

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'Job non trovato o scaduto',
            ], 404);
        }

        // Se il job è ancora in esecuzione, controlla se il processo è ancora attivo
        if ($status['status'] === 'running' && isset($status['pid'])) {
            $isRunning = shell_exec("ps -p {$status['pid']} | grep -v PID");

            // Se il processo non è più in esecuzione, leggi l'output
            if (empty($isRunning)) {
                $outputFile = $status['output_file'] ?? null;
                $output = '';
                $exitCode = 0;

                if ($outputFile && file_exists($outputFile)) {
                    $output = file_get_contents($outputFile);

                    // Determina exit code dall'output
                    $exitCode = (strpos($output, '❌') !== false) ? 1 : 0;
                }

                // Analizza l'output
                $stats = $this->parseScriptOutput($output);

                // Aggiorna il batch nel database se presente
                if (isset($status['batch_id'])) {
                    $batch = MassClosureBatch::where('batch_id', $status['batch_id'])->first();
                    if ($batch) {
                        $batch->update([
                            'status' => $exitCode === 0 ? 'completed' : 'failed',
                            'total_restaurants' => $stats['total'],
                            'successful_closures' => $stats['success'],
                            'failed_closures' => $stats['failed'],
                            'output' => $output,
                        ]);
                    }
                }

                // Aggiorna cache
                $status = array_merge($status, [
                    'status' => $exitCode === 0 ? 'completed' : 'failed',
                    'exit_code' => $exitCode,
                    'stats' => $stats,
                    'output' => $output,
                    'completed_at' => now()->toIso8601String(),
                ]);

                cache()->put("restaurant_closure_{$jobId}", $status, now()->addHours(24));
            }
        }

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Analizza l'output dello script Python per estrarre statistiche
     */
    private function parseScriptOutput(string $output): array
    {
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        // Prima cerca il JSON stats (nuovo formato)
        if (preg_match('/__JSON_STATS__:(.*?):__END_JSON__/', $output, $matches)) {
            try {
                $jsonStats = json_decode($matches[1], true);
                if ($jsonStats) {
                    $stats['total'] = $jsonStats['total'] ?? 0;
                    $stats['success'] = $jsonStats['success'] ?? 0;
                    $stats['failed'] = $jsonStats['failed'] ?? 0;
                    return $stats;
                }
            } catch (\Exception $e) {
                // Fallback al parsing testuale
            }
        }

        // Fallback: cerca pattern tipo "Successi: 45/50"
        if (preg_match('/✅ Successi:\s*(\d+)\/(\d+)/', $output, $matches)) {
            $stats['success'] = (int) $matches[1];
            $stats['total'] = (int) $matches[2];
            $stats['failed'] = $stats['total'] - $stats['success'];
        }

        return $stats;
    }

    /**
     * Test endpoint per verificare che Python sia installato
     * 
     * GET /api/restaurants/check-python
     */
    public function checkPython()
    {
        try {
            $process = new Process(['python3', '--version']);
            $process->run();

            if ($process->isSuccessful()) {
                $version = trim($process->getOutput());
                
                // Verifica anche Selenium
                $seleniumProcess = new Process(['python3', '-c', 'import selenium; print(selenium.__version__)']);
                $seleniumProcess->run();
                
                $seleniumVersion = $seleniumProcess->isSuccessful() 
                    ? trim($seleniumProcess->getOutput()) 
                    : 'Non installato';

                return response()->json([
                    'success' => true,
                    'python_version' => $version,
                    'selenium_version' => $seleniumVersion,
                    'selenium_installed' => $seleniumProcess->isSuccessful(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Python3 non trovato',
                    'error' => $process->getErrorOutput(),
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore verifica Python',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Salva il mapping tra batch_id e holiday_id
     * Chiamato dal Python script per ogni chiusura creata
     *
     * POST /api/restaurants/save-holiday-mapping
     */
    public function saveHolidayMapping(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => 'required|string|exists:mass_closure_batches,batch_id',
            'oppla_holiday_id' => 'required|string',
            'oppla_restaurant_id' => 'required|string',
            'restaurant_name' => 'nullable|string',
        ]);

        try {
            MassClosureHolidayMapping::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Mapping salvato',
            ]);
        } catch (\Exception $e) {
            Log::error('[RestaurantClosure] Errore salvataggio mapping', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante il salvataggio del mapping',
            ], 500);
        }
    }

    /**
     * Riapre tutti i ristoranti di un batch eliminando le chiusure
     *
     * POST /api/restaurants/reopen-batch/{batchId}
     */
    public function reopenBatch(string $batchId)
    {
        $batch = MassClosureBatch::where('batch_id', $batchId)->first();

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Batch non trovato',
            ], 404);
        }

        try {
            // Recupera tutti gli holiday IDs per questo batch
            $holidayMappings = MassClosureHolidayMapping::where('batch_id', $batchId)->get();

            if ($holidayMappings->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nessuna chiusura trovata per questo batch',
                ], 404);
            }

            $holidayIds = $holidayMappings->pluck('oppla_holiday_id')->toArray();

            // Path allo script wrapper
            $wrapperScript = base_path('scripts/run_reopen.sh');

            if (!file_exists($wrapperScript)) {
                Log::error('[RestaurantClosure] Script reopen non trovato', ['path' => $wrapperScript]);
                return response()->json([
                    'success' => false,
                    'message' => 'Script di riapertura non trovato',
                ], 500);
            }

            // ID univoco per tracciare il job
            $jobId = uniqid('reopen_', true);

            // Prepara il comando
            $holidayIdsJson = json_encode($holidayIds);
            $shellCommand = sprintf(
                '%s %s',
                escapeshellarg($wrapperScript),
                escapeshellarg($holidayIdsJson)
            );

            // Esegui in background e ottieni il PID
            $pid = trim(shell_exec($shellCommand));

            Log::info('[RestaurantClosure] Processo riapertura avviato', [
                'job_id' => $jobId,
                'batch_id' => $batchId,
                'holiday_count' => count($holidayIds),
                'pid' => $pid,
            ]);

            // Path per log
            $outputFile = storage_path("logs/reopen_{$jobId}.log");

            // Salva info job in cache per status check
            cache()->put("restaurant_reopen_{$jobId}", [
                'status' => 'running',
                'batch_id' => $batchId,
                'holiday_count' => count($holidayIds),
                'started_at' => now()->toIso8601String(),
                'pid' => $pid,
                'output_file' => $outputFile,
            ], now()->addHours(24));

            return response()->json([
                'success' => true,
                'message' => 'Riapertura ristoranti avviata in background',
                'data' => [
                    'job_id' => $jobId,
                    'batch_id' => $batchId,
                    'holiday_count' => count($holidayIds),
                    'check_status_url' => url("/api/restaurants/reopen-status/{$jobId}"),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[RestaurantClosure] Errore avvio riapertura', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'avvio della riapertura: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Controlla lo stato di un job di riapertura
     *
     * GET /api/restaurants/reopen-status/{jobId}
     */
    public function checkReopenStatus(string $jobId)
    {
        $status = cache()->get("restaurant_reopen_{$jobId}");

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'Job non trovato o scaduto',
            ], 404);
        }

        // Se il job è ancora in esecuzione, controlla se il processo è ancora attivo
        if ($status['status'] === 'running' && isset($status['pid'])) {
            $isRunning = shell_exec("ps -p {$status['pid']} | grep -v PID");

            // Se il processo non è più in esecuzione, leggi l'output
            if (empty($isRunning)) {
                $outputFile = $status['output_file'] ?? null;
                $output = '';
                $exitCode = 0;

                if ($outputFile && file_exists($outputFile)) {
                    $output = file_get_contents($outputFile);
                    $exitCode = (strpos($output, '❌') !== false) ? 1 : 0;
                }

                // Parse JSON stats
                $stats = $this->parseScriptOutput($output);

                // Se completato con successo, elimina i mapping
                if ($exitCode === 0 && isset($status['batch_id'])) {
                    MassClosureHolidayMapping::where('batch_id', $status['batch_id'])->delete();
                }

                // Aggiorna cache
                $status = array_merge($status, [
                    'status' => $exitCode === 0 ? 'completed' : 'failed',
                    'exit_code' => $exitCode,
                    'stats' => $stats,
                    'output' => $output,
                    'completed_at' => now()->toIso8601String(),
                ]);

                cache()->put("restaurant_reopen_{$jobId}", $status, now()->addHours(24));
            }
        }

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Ottieni la lista dei batch recenti
     *
     * GET /api/restaurants/closure-batches
     */
    public function getBatches(Request $request)
    {
        $limit = $request->query('limit', 20);

        $batches = MassClosureBatch::with('holidayMappings')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($batch) {
                return [
                    'batch_id' => $batch->batch_id,
                    'start_date' => $batch->start_date->toIso8601String(),
                    'end_date' => $batch->end_date->toIso8601String(),
                    'reason' => $batch->reason,
                    'status' => $batch->status,
                    'total_restaurants' => $batch->total_restaurants,
                    'successful_closures' => $batch->successful_closures,
                    'failed_closures' => $batch->failed_closures,
                    'holiday_count' => $batch->holidayMappings->count(),
                    'created_at' => $batch->created_at->toIso8601String(),
                    'updated_at' => $batch->updated_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }

    /**
     * Chiude un singolo ristorante creando un holiday direttamente nel DB Oppla
     *
     * POST /api/restaurants/{restaurantId}/close
     */
    public function closeSingle(Request $request, string $restaurantId)
    {
        $validated = $request->validate([
            'start_date' => 'required|date_format:Y-m-d\TH:i',
            'end_date' => 'required|date_format:Y-m-d\TH:i|after:start_date',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $oppla = DB::connection('oppla');

            // Verify the restaurant exists
            $restaurant = $oppla->table('restaurants')
                ->where('id', $restaurantId)
                ->first();

            if (!$restaurant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ristorante non trovato nel database Oppla',
                ], 404);
            }

            // Convert dates to UTC (input is Europe/Rome, DB stores UTC = -1h)
            $startUtc = \Carbon\Carbon::parse($validated['start_date'], 'Europe/Rome')->utc();
            $endUtc = \Carbon\Carbon::parse($validated['end_date'], 'Europe/Rome')->utc();

            // Check for overlapping holidays
            $overlap = $oppla->table('holidays')
                ->where('restaurant_id', $restaurantId)
                ->where(function ($q) use ($startUtc, $endUtc) {
                    $q->where(function ($q2) use ($startUtc, $endUtc) {
                        $q2->where('start', '<=', $startUtc)
                            ->where('end', '>=', $endUtc);
                    })->orWhere(function ($q2) use ($startUtc, $endUtc) {
                        $q2->where('start', '>=', $startUtc)
                            ->where('start', '<', $endUtc);
                    })->orWhere(function ($q2) use ($startUtc, $endUtc) {
                        $q2->where('end', '>', $startUtc)
                            ->where('end', '<=', $endUtc);
                    });
                })
                ->exists();

            if ($overlap) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esiste già una chiusura sovrapposta per questo ristorante nel periodo indicato',
                ], 409);
            }

            // Insert the holiday
            $holidayId = (string) Str::uuid();
            $now = now()->utc();

            $oppla->table('holidays')->insert([
                'id' => $holidayId,
                'restaurant_id' => $restaurantId,
                'start' => $startUtc,
                'end' => $endUtc,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            Log::info('[RestaurantClosure] Chiusura singola creata', [
                'restaurant_id' => $restaurantId,
                'restaurant_name' => $restaurant->name,
                'holiday_id' => $holidayId,
                'start' => $validated['start_date'],
                'end' => $validated['end_date'],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Chiusura creata per {$restaurant->name}",
                'data' => [
                    'holiday_id' => $holidayId,
                    'restaurant_id' => $restaurantId,
                    'restaurant_name' => $restaurant->name,
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[RestaurantClosure] Errore chiusura singola', [
                'restaurant_id' => $restaurantId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la chiusura: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Riapre un singolo ristorante eliminando un holiday dal DB Oppla
     *
     * DELETE /api/restaurants/holidays/{holidayId}
     */
    public function reopenSingle(string $holidayId)
    {
        try {
            $oppla = DB::connection('oppla');

            $holiday = $oppla->table('holidays')->where('id', $holidayId)->first();

            if (!$holiday) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chiusura non trovata',
                ], 404);
            }

            $oppla->table('holidays')->where('id', $holidayId)->delete();

            Log::info('[RestaurantClosure] Chiusura singola eliminata', [
                'holiday_id' => $holidayId,
                'restaurant_id' => $holiday->restaurant_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chiusura eliminata',
                'data' => [
                    'holiday_id' => $holidayId,
                    'restaurant_id' => $holiday->restaurant_id,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[RestaurantClosure] Errore riapertura singola', [
                'holiday_id' => $holidayId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la riapertura: ' . $e->getMessage(),
            ], 500);
        }
    }
}
