<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CancellationService
{
    public function __construct(
        protected TookanService $tookanService,
        protected OpplaWriteService $opplaWriteService,
    ) {}

    /**
     * Generate cancellation preview with confirmation token
     */
    public function preview(string $type, int $id): array
    {
        $entity = $this->loadEntity($type, $id);

        $preview = [
            'type' => $type,
            'id' => $id,
            'local' => $this->previewLocal($type, $entity),
            'oppla' => $this->previewOppla($type, $entity),
            'tookan' => $this->previewTookan($type, $entity),
        ];

        $warnings = $this->buildWarnings($type, $entity, $preview);

        // Generate confirmation token (5 min expiry)
        $token = Str::random(64);
        Cache::put("cancellation:{$token}", [
            'type' => $type,
            'id' => $id,
            'preview' => $preview,
            'tookan_job_id' => $preview['tookan']['job_id'] ?? null,
            'created_at' => now(),
        ], now()->addMinutes(5));

        return [
            'preview' => $preview,
            'warnings' => $warnings,
            'confirmation_token' => $token,
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
        ];
    }

    /**
     * Execute cancellation with confirmation token
     * Order: Tookan first (rider!), then Oppla, then local
     */
    public function execute(string $token): array
    {
        $cached = Cache::get("cancellation:{$token}");

        if (!$cached) {
            throw new \Exception('Token di conferma non valido o scaduto');
        }

        // Invalidate token immediately (one-time use)
        Cache::forget("cancellation:{$token}");

        $type = $cached['type'];
        $id = $cached['id'];
        $tookanJobId = $cached['tookan_job_id'];

        $entity = $this->loadEntity($type, $id);

        $results = [
            'tookan' => ['success' => false, 'skipped' => true, 'message' => 'Nessun job_id Tookan'],
            'oppla' => ['success' => false, 'skipped' => true, 'message' => 'Nessun ID Oppla'],
            'local' => ['success' => false, 'skipped' => false],
        ];

        // 1. TOOKAN - Cancel rider task first (most urgent)
        $results['tookan'] = $this->cancelTookan($tookanJobId);

        // 2. OPPLA - Cancel on platform
        $results['oppla'] = $this->cancelOppla($type, $entity);

        // 3. LOCAL - Delete locally with invoice cleanup
        $results['local'] = $this->cancelLocal($type, $entity);

        $overallSuccess = $results['local']['success'];

        Log::info('[Cancellation] Executed', [
            'type' => $type,
            'id' => $id,
            'results' => $results,
            'user' => auth()->user()?->email ?? 'system',
        ]);

        return [
            'results' => $results,
            'overall_success' => $overallSuccess,
        ];
    }

    protected function loadEntity(string $type, int $id): Order|Delivery
    {
        return match ($type) {
            'order' => Order::findOrFail($id),
            'delivery' => Delivery::findOrFail($id),
            default => throw new \Exception("Tipo non valido: {$type}"),
        };
    }

    // --- Preview helpers ---

    protected function previewLocal(string $type, Order|Delivery $entity): array
    {
        $hasInvoice = (bool) $entity->invoice_id;
        $invoice = $hasInvoice ? Invoice::find($entity->invoice_id) : null;

        return [
            'will_delete' => true,
            'has_invoice' => $hasInvoice,
            'invoice_id' => $entity->invoice_id,
            'invoice_number' => $invoice?->number,
        ];
    }

    protected function previewOppla(string $type, Order|Delivery $entity): array
    {
        if ($type === 'order') {
            $opplaId = $entity->oppla_order_id;
            $canCancel = !empty($opplaId);
            return [
                'can_cancel' => $canCancel,
                'oppla_id' => $opplaId,
                'table' => 'orders',
            ];
        }

        // delivery
        $opplaId = $entity->oppla_id;
        $canCancel = !empty($opplaId);
        return [
            'can_cancel' => $canCancel,
            'oppla_id' => $opplaId,
            'table' => 'managed_deliveries',
        ];
    }

    protected function previewTookan(string $type, Order|Delivery $entity): array
    {
        $jobId = $entity->tookan_job_id;

        // Try to find the job_id if not stored
        if (!$jobId) {
            $jobId = $this->resolveTookanJobId($type, $entity);
            if ($jobId) {
                // Save for future use
                $entity->update(['tookan_job_id' => $jobId]);
            }
        }

        if (!$jobId) {
            return [
                'can_cancel' => false,
                'job_id' => null,
                'reason' => 'Job ID Tookan non trovato - cancellazione manuale necessaria',
            ];
        }

        // Try to get task details from Tookan
        $details = $this->tookanService->getTaskDetails([(int) $jobId]);
        $taskInfo = [];
        if ($details['success'] && !empty($details['data'])) {
            $task = $details['data'][0] ?? [];
            $taskInfo = [
                'rider' => trim(($task['fleet_name'] ?? '') ?: ($task['agent_name'] ?? 'N/A')),
                'status' => $task['job_status'] ?? null,
                'address' => $task['job_address'] ?? null,
            ];
        }

        return array_merge([
            'can_cancel' => true,
            'job_id' => $jobId,
        ], $taskInfo);
    }

    protected function resolveTookanJobId(string $type, Order|Delivery $entity): ?int
    {
        $date = null;
        $fleetId = null;
        $address = null;

        if ($type === 'delivery') {
            $date = $entity->order_date?->format('Y-m-d') ?? $entity->delivery_scheduled_at?->format('Y-m-d');
            $address = $entity->delivery_address ?? $entity->shipping_address;
            // Try to get fleet_id from rider
            if ($entity->rider_id) {
                $rider = \App\Models\Rider::find($entity->rider_id);
                $fleetId = $rider?->fleet_id;
            }
        } else {
            $date = $entity->order_date?->format('Y-m-d');
            $address = $entity->shipping_address;
        }

        if (!$date) {
            return null;
        }

        return $this->tookanService->findTaskByDetails($date, $fleetId, $address);
    }

    protected function buildWarnings(string $type, Order|Delivery $entity, array $preview): array
    {
        $warnings = [];

        if ($preview['local']['has_invoice']) {
            $invoiceNum = $preview['local']['invoice_number'] ?? $preview['local']['invoice_id'];
            $warnings[] = "L'ordine e' collegato alla fattura #{$invoiceNum} - verra' rimosso dalla fattura";
        }

        if (!$preview['oppla']['can_cancel']) {
            $warnings[] = "Nessun ID Oppla trovato - la cancellazione su Oppla non sara' possibile";
        }

        if (!$preview['tookan']['can_cancel']) {
            $warnings[] = "Job ID Tookan non trovato - il task del rider dovra' essere cancellato manualmente su Tookan";
        }

        return $warnings;
    }

    // --- Execution helpers ---

    protected function cancelTookan(?string $jobId): array
    {
        if (!$jobId) {
            return ['success' => false, 'skipped' => true, 'message' => 'Nessun job_id Tookan'];
        }

        try {
            $result = $this->tookanService->cancelTask((int) $jobId);
            return [
                'success' => $result['success'],
                'skipped' => false,
                'message' => $result['success']
                    ? 'Task rider cancellato su Tookan'
                    : ($result['error'] ?? 'Errore cancellazione Tookan'),
            ];
        } catch (\Exception $e) {
            Log::error('[Cancellation] Tookan error', ['job_id' => $jobId, 'error' => $e->getMessage()]);
            return ['success' => false, 'skipped' => false, 'message' => 'Errore: ' . $e->getMessage()];
        }
    }

    protected function cancelOppla(string $type, Order|Delivery $entity): array
    {
        $table = $type === 'order' ? 'orders' : 'managed_deliveries';
        $opplaId = $type === 'order' ? $entity->oppla_order_id : $entity->oppla_id;

        if (!$opplaId) {
            return ['success' => false, 'skipped' => true, 'message' => 'Nessun ID Oppla'];
        }

        try {
            // Use the column that identifies the record in Oppla's DB
            $conditionColumn = $type === 'order' ? 'id' : 'id';
            $result = $this->opplaWriteService->requestConfirmation(
                'DELETE',
                $table,
                [],
                [$conditionColumn => $opplaId]
            );

            // Auto-execute since user already confirmed via our own token
            if (isset($result['token'])) {
                $execResult = $this->opplaWriteService->executeWithConfirmation($result['token']);
                return [
                    'success' => $execResult['success'] ?? true,
                    'skipped' => false,
                    'message' => 'Record eliminato da Oppla',
                    'affected_rows' => $execResult['affected_rows'] ?? 0,
                ];
            }

            return ['success' => false, 'skipped' => false, 'message' => 'Errore nel token Oppla'];
        } catch (\Exception $e) {
            Log::error('[Cancellation] Oppla error', ['type' => $type, 'oppla_id' => $opplaId, 'error' => $e->getMessage()]);
            return ['success' => false, 'skipped' => false, 'message' => 'Errore Oppla: ' . $e->getMessage()];
        }
    }

    protected function cancelLocal(string $type, Order|Delivery $entity): array
    {
        try {
            DB::beginTransaction();

            // Remove from invoice if linked
            if ($entity->invoice_id) {
                $invoice = Invoice::find($entity->invoice_id);
                if ($invoice) {
                    $column = $type === 'order' ? 'order_id' : 'delivery_id';
                    InvoiceItem::where('invoice_id', $invoice->id)
                        ->where($column, $entity->id)
                        ->delete();
                    $invoice->recalculateTotals();
                }
            }

            $entity->delete();

            DB::commit();

            return ['success' => true, 'skipped' => false, 'message' => 'Record eliminato localmente'];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[Cancellation] Local error', ['type' => $type, 'id' => $entity->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'skipped' => false, 'message' => 'Errore locale: ' . $e->getMessage()];
        }
    }
}
