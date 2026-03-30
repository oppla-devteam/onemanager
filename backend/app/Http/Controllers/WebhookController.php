<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Client;
use App\Services\FattureInCloudService;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Fatture in Cloud webhook (CloudEvents format)
     * 
     * FIC uses CloudEvents spec v1.0
     * @see https://developers.fattureincloud.it/docs/webhooks
     */
    public function handleFattureInCloudWebhook(Request $request)
    {
        try {
            // CloudEvents headers validation
            $cloudEventType = $request->header('ce-type');
            $cloudEventSource = $request->header('ce-source');
            $cloudEventId = $request->header('ce-id');
            $cloudEventSpecVersion = $request->header('ce-specversion');

            Log::info('FIC Webhook received (CloudEvents)', [
                'type' => $cloudEventType,
                'source' => $cloudEventSource,
                'id' => $cloudEventId,
                'spec_version' => $cloudEventSpecVersion,
            ]);

            // Get payload
            $payload = $request->all();

            // Validate CloudEvents format
            if (!$cloudEventType || !$cloudEventId) {
                Log::warning('Invalid CloudEvents webhook payload', [
                    'headers' => $request->headers->all(),
                    'payload' => $payload,
                ]);
                return response()->json(['message' => 'Invalid CloudEvents format'], 400);
            }

            // Handle different event types based on CloudEvents type
            // Format: it.fattureincloud.webhooks.issued_documents.{action}
            $result = $this->handleEventByType($cloudEventType, $payload);

            if ($result) {
                return response()->json(['message' => 'Webhook processed successfully'], 200);
            }

            return response()->json(['message' => 'Event type not handled'], 200);

        } catch (\Exception $e) {
            Log::error('Error processing FIC webhook: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }

    /**
     * Route event to appropriate handler based on CloudEvents type
     */
    private function handleEventByType(string $eventType, array $payload): bool
    {
        // Event type format: it.fattureincloud.webhooks.{resource}.{action}
        // Examples:
        // - it.fattureincloud.webhooks.issued_documents.create
        // - it.fattureincloud.webhooks.issued_documents.update
        // - it.fattureincloud.webhooks.issued_documents.delete

        Log::info('Processing FIC event', [
            'type' => $eventType,
            'data' => $payload,
        ]);

        // Extract resource and action
        if (str_contains($eventType, 'issued_documents')) {
            return $this->handleIssuedDocumentEvent($eventType, $payload);
        }

        if (str_contains($eventType, 'clients')) {
            return $this->handleClientEvent($eventType, $payload);
        }

        if (str_contains($eventType, 'suppliers')) {
            return $this->handleSupplierEvent($eventType, $payload);
        }

        Log::info('Unhandled event type', ['type' => $eventType]);
        return false;
    }

    /**
     * Handle issued document events (invoices, credit notes, etc.)
     */
    private function handleIssuedDocumentEvent(string $eventType, array $payload): bool
    {
        $documentId = $payload['document_id'] ?? $payload['id'] ?? null;
        $companyId = $payload['company_id'] ?? null;
        $documentData = $payload['data'] ?? $payload;

        if (!$documentId) {
            Log::warning('Missing document_id in issued_document event', ['payload' => $payload]);
            return false;
        }

        // Find invoice by FIC document ID
        $invoice = Invoice::where('fic_document_id', $documentId)->first();

        // Handle different actions
        if (str_contains($eventType, '.create')) {
            return $this->handleDocumentCreated($invoice, $documentId, $documentData);
        }

        if (str_contains($eventType, '.update')) {
            return $this->handleDocumentUpdated($invoice, $documentId, $documentData);
        }

        if (str_contains($eventType, '.delete')) {
            return $this->handleDocumentDeleted($invoice, $documentId);
        }

        // Custom events
        if (str_contains($eventType, 'e_invoice_sent')) {
            return $this->handleEInvoiceSent($invoice, $documentData);
        }

        if (str_contains($eventType, 'e_invoice_status')) {
            return $this->handleEInvoiceStatusUpdate($invoice, $documentData);
        }

        return false;
    }

    /**
     * Handle document created event
     */
    private function handleDocumentCreated(?Invoice $invoice, int $documentId, array $data): bool
    {
        if ($invoice) {
            Log::info("Document {$documentId} already exists locally");
            return true;
        }

        Log::info("New document created on FIC", ['fic_document_id' => $documentId]);
        
        // Trigger sync command to import this document
        \Artisan::queue('fic:sync-invoices', [
            '--from' => now()->subDays(7)->format('Y-m-d'),
            '--update' => true,
        ]);

        return true;
    }

    /**
     * Handle document updated event
     */
    private function handleDocumentUpdated(?Invoice $invoice, int $documentId, array $data): bool
    {
        if (!$invoice) {
            Log::warning("Document {$documentId} not found locally, triggering sync");
            \Artisan::queue('fic:sync-invoices', [
                '--from' => now()->subDays(7)->format('Y-m-d'),
            ]);
            return true;
        }

        Log::info("Document {$documentId} updated on FIC", [
            'invoice_id' => $invoice->id,
            'data' => $data,
        ]);

        // Update relevant fields if provided
        $updates = [];

        if (isset($data['amount_net'])) {
            $updates['imponibile'] = $data['amount_net'];
        }

        if (isset($data['amount_vat'])) {
            $updates['iva'] = $data['amount_vat'];
        }

        if (isset($data['amount_gross'])) {
            $updates['totale'] = $data['amount_gross'];
        }

        if (isset($data['status'])) {
            $updates['status'] = $this->mapFicStatus($data['status']);
        }

        if (!empty($updates)) {
            $invoice->update($updates);
        }

        return true;
    }

    /**
     * Handle document deleted event
     */
    private function handleDocumentDeleted(?Invoice $invoice, int $documentId): bool
    {
        if (!$invoice) {
            Log::warning("Document {$documentId} not found locally for deletion");
            return true;
        }

        Log::info("Document {$documentId} deleted on FIC", ['invoice_id' => $invoice->id]);

        $invoice->update([
            'cancelled_at' => now(),
            'status' => 'annullata',
        ]);

        return true;
    }

    /**
     * Handle e-invoice sent event
     */
    private function handleEInvoiceSent(?Invoice $invoice, array $data): bool
    {
        if (!$invoice) {
            Log::warning('E-invoice sent event for unknown invoice', ['data' => $data]);
            return false;
        }

        $invoice->update([
            'status' => 'inviata',
            'sdi_sent_at' => now(),
            'sdi_status' => 'sent',
        ]);

        Log::info("E-invoice {$invoice->id} marked as sent via webhook");
        return true;
    }

    /**
     * Handle e-invoice status update (accepted/rejected by SDI)
     */
    private function handleEInvoiceStatusUpdate(?Invoice $invoice, array $data): bool
    {
        if (!$invoice) {
            Log::warning('E-invoice status update for unknown invoice', ['data' => $data]);
            return false;
        }

        $sdiStatus = $data['e_invoice_status'] ?? $data['status'] ?? null;

        if (!$sdiStatus) {
            return false;
        }

        $updates = [];

        switch (strtolower($sdiStatus)) {
            case 'accepted':
            case 'delivered':
                $updates['sdi_status'] = 'accepted';
                $updates['sdi_accepted_at'] = now();
                $updates['status'] = 'accettata';
                
                if (isset($data['protocol_number'])) {
                    $updates['sdi_protocol_number'] = $data['protocol_number'];
                }
                
                if (isset($data['receipt_date'])) {
                    $updates['sdi_receipt_date'] = $data['receipt_date'];
                }
                
                Log::info("Invoice {$invoice->id} accepted by SDI", ['data' => $data]);
                break;

            case 'rejected':
            case 'failed':
                $updates['sdi_status'] = 'rejected';
                $updates['sdi_rejected_at'] = now();
                $updates['status'] = 'rifiutata';
                $updates['sdi_rejection_reason'] = $data['rejection_reason'] ?? $data['error_message'] ?? 'Unknown';
                
                Log::error("Invoice {$invoice->id} rejected by SDI", [
                    'reason' => $updates['sdi_rejection_reason'],
                ]);
                break;

            case 'pending':
                $updates['sdi_status'] = 'pending';
                break;
        }

        if (!empty($updates)) {
            $invoice->update($updates);
        }

        return true;
    }

    /**
     * Handle client events
     */
    private function handleClientEvent(string $eventType, array $payload): bool
    {
        $clientId = $payload['id'] ?? null;

        if (!$clientId) {
            return false;
        }

        Log::info('Client event received', [
            'type' => $eventType,
            'fic_client_id' => $clientId,
        ]);

        // Find local client
        $client = Client::where('fic_client_id', $clientId)->first();

        if (str_contains($eventType, '.update') && $client) {
            // Update client data
            Log::info("Updating client {$client->id} from FIC webhook");
            // Trigger sync if needed
        }

        return true;
    }

    /**
     * Handle supplier events
     */
    private function handleSupplierEvent(string $eventType, array $payload): bool
    {
        Log::info('Supplier event received', [
            'type' => $eventType,
            'data' => $payload,
        ]);

        // Implement supplier sync if needed
        return true;
    }

    /**
     * Map FIC status to local status
     */
    private function mapFicStatus(string $ficStatus): string
    {
        return match (strtolower($ficStatus)) {
            'draft' => 'bozza',
            'sent' => 'inviata',
            'paid' => 'pagata',
            'partially_paid' => 'parzialmente_pagata',
            'not_paid' => 'non_pagata',
            'overdue' => 'scaduta',
            'reversed' => 'stornata',
            default => $ficStatus,
        };
    }
}
