<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractSignature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractService
{
    public function __construct(
        private ContractPdfService $pdfService,
        private ContractSignatureService $signatureService
    ) {}

    /**
     * Crea nuovo contratto da template
     */
    public function createFromTemplate(
        ContractTemplate $template,
        array $contractData,
        array $signers,
        ?int $clientId = null
    ): Contract {
        // Valida campi richiesti
        $missingFields = $template->validateRequiredFields($contractData);
        if (!empty($missingFields)) {
            throw new \Exception('Campi mancanti: ' . implode(', ', $missingFields));
        }

        DB::beginTransaction();
        try {
            // Crea contratto
            $contract = Contract::create([
                'template_id' => $template->id,
                'client_id' => $clientId,
                'client_name' => $contractData['client_name'] ?? '',
                'client_email' => $contractData['client_email'] ?? '',
                'client_phone' => $contractData['client_phone'] ?? null,
                'client_vat_number' => $contractData['client_vat_number'] ?? null,
                'client_fiscal_code' => $contractData['client_fiscal_code'] ?? null,
                'subject' => $contractData['subject'] ?? $template->name,
                'contract_data' => $contractData,
                'status' => 'draft',
                'start_date' => $contractData['start_date'] ?? null,
                'end_date' => $contractData['end_date'] ?? null,
                'created_by' => auth()->id(),
                'assigned_to' => auth()->id(),
            ]);

            // Aggiungi firmatari
            foreach ($signers as $index => $signer) {
                ContractSignature::create([
                    'contract_id' => $contract->id,
                    'signer_name' => $signer['name'],
                    'signer_email' => $signer['email'],
                    'signer_phone' => $signer['phone'] ?? null,
                    'signer_role' => $signer['role'] ?? 'client',
                    'signing_order' => $signer['order'] ?? ($index + 1),
                    'status' => 'pending',
                ]);
            }

            // Log creazione
            $contract->logHistory('created', null, 'draft', null, 'Contratto creato');

            DB::commit();

            Log::info('Contratto creato', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'template' => $template->name,
            ]);

            return $contract->load('signatures');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore creazione contratto', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Aggiorna contratto in bozza
     */
    public function updateDraft(Contract $contract, array $data): Contract
    {
        if ($contract->status !== 'draft') {
            throw new \Exception('Solo i contratti in bozza possono essere modificati');
        }

        $oldData = $contract->contract_data;
        
        $contract->update([
            'contract_data' => array_merge($oldData, $data),
            'subject' => $data['subject'] ?? $contract->subject,
            'start_date' => $data['start_date'] ?? $contract->start_date,
            'end_date' => $data['end_date'] ?? $contract->end_date,
        ]);

        $contract->logHistory('updated', null, null, ['changes' => $data], 'Contratto aggiornato');

        return $contract;
    }

    /**
     * Prepara contratto per invio
     */
    public function prepareForSending(Contract $contract): Contract
    {
        if (!in_array($contract->status, ['draft', 'pending_review'])) {
            throw new \Exception('Il contratto non può essere preparato per l\'invio');
        }

        // Genera PDF
        $this->pdfService->generatePdf($contract);

        // Aggiorna stato
        $contract->update(['status' => 'ready_to_sign']);
        $contract->logHistory('prepared', $contract->status, 'ready_to_sign', null, 'Contratto pronto per firma');

        return $contract;
    }

    /**
     * Invia contratto per firma
     */
    public function sendForSignature(Contract $contract): Contract
    {
        if ($contract->status !== 'ready_to_sign') {
            $this->prepareForSending($contract);
        }

        // Invia inviti firma
        $this->signatureService->sendSignatureInvitations($contract);

        return $contract->fresh();
    }

    /**
     * Attiva contratto firmato
     */
    public function activateContract(Contract $contract): Contract
    {
        if ($contract->status !== 'signed') {
            throw new \Exception('Solo i contratti firmati possono essere attivati');
        }

        $contract->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        $contract->logHistory('activated', 'signed', 'active', null, 'Contratto attivato');

        return $contract;
    }

    /**
     * Termina contratto
     */
    public function terminateContract(Contract $contract, string $reason): Contract
    {
        if (!in_array($contract->status, ['active', 'signed'])) {
            throw new \Exception('Solo i contratti attivi possono essere terminati');
        }

        $oldStatus = $contract->status;
        
        $contract->update([
            'status' => 'terminated',
            'end_date' => now(),
        ]);

        $contract->logHistory('terminated', $oldStatus, 'terminated', null, "Contratto terminato: {$reason}");

        return $contract;
    }

    /**
     * Annulla contratto
     */
    public function cancelContract(Contract $contract, string $reason): Contract
    {
        if (in_array($contract->status, ['signed', 'active', 'terminated'])) {
            throw new \Exception('Il contratto non può essere annullato in questo stato');
        }

        $oldStatus = $contract->status;
        
        $contract->update(['status' => 'cancelled']);
        $contract->logHistory('cancelled', $oldStatus, 'cancelled', null, "Contratto annullato: {$reason}");

        return $contract;
    }

    /**
     * Rinnova contratto
     */
    public function renewContract(Contract $originalContract, array $newData = []): Contract
    {
        if (!in_array($originalContract->status, ['active', 'expired'])) {
            throw new \Exception('Solo i contratti attivi o scaduti possono essere rinnovati');
        }

        DB::beginTransaction();
        try {
            // Crea nuovo contratto basato sull'originale
            $contractData = array_merge($originalContract->contract_data, $newData);
            
            $signers = $originalContract->signatures->map(function ($signature) {
                return [
                    'name' => $signature->signer_name,
                    'email' => $signature->signer_email,
                    'phone' => $signature->signer_phone,
                    'role' => $signature->signer_role,
                    'order' => $signature->signing_order,
                ];
            })->toArray();

            $newContract = $this->createFromTemplate(
                $originalContract->template,
                $contractData,
                $signers,
                $originalContract->client_id
            );

            // Collega i contratti
            $originalContract->logHistory(
                'renewed',
                null,
                null,
                ['new_contract_id' => $newContract->id],
                "Contratto rinnovato con {$newContract->contract_number}"
            );

            $newContract->logHistory(
                'renewal_of',
                null,
                null,
                ['original_contract_id' => $originalContract->id],
                "Rinnovo del contratto {$originalContract->contract_number}"
            );

            DB::commit();

            return $newContract;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Crea contratto da dati onboarding
     */
    public function createFromOnboarding(
        int $clientId,
        array $restaurantData,
        array $feeData = []
    ): Contract {
        // Carica il template Oppla standard
        $template = ContractTemplate::where('code', 'oppla-subscription-cover')->first();
        
        if (!$template) {
            throw new \Exception('Template contratto Oppla non trovato. Esegui: php artisan db:seed --class=OpplaContractTemplateSeeder');
        }

        $client = \App\Models\Client::findOrFail($clientId);
        
        // Prepara i dati del contratto dal restaurant + client
        $contractData = [
            'partner_ragione_sociale' => $client->ragione_sociale,
            'partner_piva' => $client->piva,
            'partner_sede_legale' => $client->indirizzo,
            'partner_iban' => $client->iban ?? '',
            'partner_legale_rappresentante' => $client->legal_representative ?? '',
            'partner_email' => $client->email,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addYear()->format('Y-m-d'),
            'periodo_mesi' => 12,
            'territorio' => 'Italia',
            'site_name' => $restaurantData['nome'] ?? $client->ragione_sociale,
            'site_address' => $restaurantData['indirizzo'] ?? $client->indirizzo,
            'costo_attivazione' => $feeData['activation_fee'] ?? 150.00,
            'servizio_ritiro' => $feeData['pickup_fee'] ?? 12.00,
            'servizio_principale' => $feeData['main_service_fee'] ?? 2.98,
            'ordine_rifiutato' => $feeData['rejected_order_fee'] ?? 1.49,
            'inserimento_manuale' => $feeData['manual_entry_fee'] ?? 1.49,
            'abbonamento_consegne' => $feeData['delivery_subscription'] ?? 24.00,
            'attrezzatura_fornita' => $feeData['equipment_provided'] ?? true,
            'miglior_prezzo_garantito' => $restaurantData['best_price'] ?? false,
        ];

        // Prepara i firmatari
        $signers = [
            [
                'name' => 'Lorenzo Moschella',
                'email' => 'lorenzo.moschella@oppla.delivery',
                'role' => 'oppla',
                'order' => 1,
            ],
            [
                'name' => $client->legal_representative ?? $client->ragione_sociale,
                'email' => $client->email,
                'phone' => $client->phone,
                'role' => 'partner',
                'order' => 2,
            ]
        ];

        DB::beginTransaction();
        try {
            // Crea il contratto usando il metodo standard
            $contract = $this->createFromTemplate(
                $template,
                $contractData,
                $signers,
                $clientId
            );

            // Genera subito il PDF
            $this->pdfService->generatePdf($contract);

            // Imposta come pronto per firma
            $contract->update(['status' => 'ready_to_sign']);
            $contract->logHistory('onboarding', 'draft', 'ready_to_sign', null, 'Contratto generato da onboarding');

            DB::commit();

            Log::info('Contratto generato da onboarding', [
                'contract_id' => $contract->id,
                'client_id' => $clientId,
                'restaurant' => $restaurantData['nome'] ?? 'N/A',
            ]);

            return $contract->load('signatures');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore generazione contratto da onboarding', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Duplica contratto
     */
    public function duplicateContract(Contract $original): Contract
    {
        DB::beginTransaction();
        try {
            $signers = $original->signatures->map(function ($signature) {
                return [
                    'name' => $signature->signer_name,
                    'email' => $signature->signer_email,
                    'phone' => $signature->signer_phone,
                    'role' => $signature->signer_role,
                    'order' => $signature->signing_order,
                ];
            })->toArray();

            $duplicate = $this->createFromTemplate(
                $original->template,
                $original->contract_data,
                $signers,
                $original->client_id
            );

            $duplicate->logHistory(
                'duplicated_from',
                null,
                null,
                ['original_contract_id' => $original->id],
                "Duplicato da contratto {$original->contract_number}"
            );

            DB::commit();

            return $duplicate;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
