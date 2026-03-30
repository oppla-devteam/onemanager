<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FattureInCloudConnection;
use App\Models\Invoice;
use App\Models\Client;
use App\Services\FattureInCloudService;
use Carbon\Carbon;

class SyncFattureInCloudInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fic:sync-invoices 
                            {--from= : Date from (Y-m-d)}
                            {--to= : Date to (Y-m-d)}
                            {--type=invoice : Document type (invoice, credit_note, etc.)}
                            {--update : Update existing invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync invoices from Fatture in Cloud to local database';

    protected FattureInCloudService $ficService;

    public function __construct(FattureInCloudService $ficService)
    {
        parent::__construct();
        $this->ficService = $ficService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Fatture in Cloud invoices sync...');

        // Get active FIC connection
        $ficConnection = FattureInCloudConnection::where('is_active', true)->first();

        if (!$ficConnection) {
            $this->error('No active Fatture in Cloud connection found');
            return 1;
        }

        // Build filters
        $filters = [
            'type' => $this->option('type'),
        ];

        if ($this->option('from')) {
            $filters['date_from'] = $this->option('from');
        }

        if ($this->option('to')) {
            $filters['date_to'] = $this->option('to');
        }

        // Get invoices from FIC
        $this->info('Fetching invoices from Fatture in Cloud...');
        $ficInvoices = $this->ficService->getIssuedInvoices($ficConnection, $filters);

        if (!$ficInvoices) {
            $this->error('Failed to fetch invoices from Fatture in Cloud');
            return 1;
        }

        $this->info('Found ' . count($ficInvoices) . ' invoices');

        $created = 0;
        $updated = 0;
        $skipped = 0;

        $progressBar = $this->output->createProgressBar(count($ficInvoices));
        $progressBar->start();

        foreach ($ficInvoices as $ficInvoice) {
            try {
                $result = $this->syncInvoice($ficInvoice, $this->option('update'));
                
                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }

                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("\nError syncing invoice {$ficInvoice['id']}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Sync completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Skipped', $skipped],
            ]
        );

        return 0;
    }

    /**
     * Sync a single invoice
     */
    private function syncInvoice(array $ficInvoice, bool $allowUpdate = false): string
    {
        // Check if invoice already exists
        $existingInvoice = Invoice::where('fic_document_id', $ficInvoice['id'])->first();

        if ($existingInvoice && !$allowUpdate) {
            return 'skipped';
        }

        // Find or create client
        $client = $this->findOrCreateClient($ficInvoice['entity']);

        if (!$client) {
            $this->warn("Could not find/create client for invoice {$ficInvoice['id']}");
            return 'skipped';
        }

        // Parse invoice data
        $invoiceData = [
            'client_id' => $client->id,
            'fic_document_id' => $ficInvoice['id'],
            'type' => 'attiva',
            'invoice_type' => $this->mapInvoiceType($ficInvoice['type']),
            'numero_fattura' => $this->buildFullInvoiceNumber($ficInvoice),
            'anno' => Carbon::parse($ficInvoice['date'])->year,
            'numero_progressivo' => $this->extractProgressiveNumber($ficInvoice['number']),
            'data_emissione' => $ficInvoice['date'],
            'data_scadenza' => $ficInvoice['due_date'] ?? Carbon::parse($ficInvoice['date'])->addDays(30),
            'imponibile' => $ficInvoice['amount_net'] ?? 0,
            'iva' => $ficInvoice['amount_vat'] ?? 0,
            'totale' => $ficInvoice['amount_gross'] ?? 0,
            'payment_status' => $this->mapPaymentStatus($ficInvoice),
            'status' => $ficInvoice['e_invoice_sent'] ? 'inviata' : 'bozza',
            'sdi_status' => $ficInvoice['e_invoice_sent'] ? 'sent' : null,
            'payment_method' => $ficInvoice['payment_method']['name'] ?? null,
        ];

        if ($existingInvoice) {
            $existingInvoice->update($invoiceData);
            $this->syncInvoiceItems($existingInvoice, $ficInvoice['items_list'] ?? []);
            return 'updated';
        } else {
            $invoice = Invoice::create($invoiceData);
            $this->syncInvoiceItems($invoice, $ficInvoice['items_list'] ?? []);
            return 'created';
        }
    }

    /**
     * Sync invoice items
     */
    private function syncInvoiceItems(Invoice $invoice, array $ficItems): void
    {
        // Clear existing items if updating
        $invoice->items()->delete();

        foreach ($ficItems as $item) {
            $invoice->items()->create([
                'description' => $item['description'] ?? $item['name'],
                'quantity' => $item['qty'] ?? 1,
                'unit_price' => $item['net_price'] ?? 0,
                'vat_rate' => $item['vat']['percentage'] ?? 22,
                'subtotal' => ($item['qty'] ?? 1) * ($item['net_price'] ?? 0),
            ]);
        }
    }

    /**
     * Find or create client from FIC entity data
     */
    private function findOrCreateClient(array $entity): ?Client
    {
        // Try to find by FIC ID
        $client = Client::where('fic_client_id', $entity['id'])->first();

        if ($client) {
            return $client;
        }

        // Try to find by VAT or tax code
        if (!empty($entity['vat_number'])) {
            $client = Client::where('piva', $entity['vat_number'])->first();
        }

        if (!$client && !empty($entity['tax_code'])) {
            $client = Client::where('codice_fiscale', $entity['tax_code'])->first();
        }

        if ($client) {
            // Update FIC ID
            $client->update(['fic_client_id' => $entity['id']]);
            return $client;
        }

        // Create new client
        return Client::create([
            'fic_client_id' => $entity['id'],
            'ragione_sociale' => $entity['name'],
            'piva' => $entity['vat_number'] ?? null,
            'codice_fiscale' => $entity['tax_code'] ?? null,
            'email' => $entity['email'] ?? null,
            'pec' => $entity['certified_email'] ?? null,
            'sdi_code' => $entity['ei_code'] ?? null,
            'indirizzo' => $entity['address_street'] ?? null,
            'citta' => $entity['address_city'] ?? null,
            'provincia' => $entity['address_province'] ?? null,
            'cap' => $entity['address_postal_code'] ?? null,
            'type' => 'cliente_extra',
            'status' => 'active',
        ]);
    }

    /**
     * Map FIC invoice type to local type
     */
    private function mapInvoiceType(string $ficType): string
    {
        return match ($ficType) {
            'invoice' => 'ordinaria',
            'credit_note' => 'nota_credito',
            'debit_note' => 'nota_debito',
            'proforma' => 'proforma',
            default => 'ordinaria',
        };
    }

    /**
     * Map FIC payment status
     */
    private function mapPaymentStatus(array $ficInvoice): string
    {
        $payments = $ficInvoice['payments_list'] ?? [];
        
        if (empty($payments)) {
            return 'unpaid';
        }

        foreach ($payments as $payment) {
            if (($payment['status'] ?? null) === 'paid') {
                return 'paid';
            }
        }

        return 'unpaid';
    }

    /**
     * Extract progressive number from invoice number
     */
    private function extractProgressiveNumber(string $invoiceNumber): int
    {
        // Extract number from format like "123/2025" or "123/FE"
        $parts = explode('/', $invoiceNumber);
        return (int) ($parts[0] ?? 0);
    }

    /**
     * Build full invoice number from FIC data (number + numeration)
     * Example: number=1, numeration="/A" => "1/A"
     */
    private function buildFullInvoiceNumber(array $ficInvoice): string
    {
        $number = $ficInvoice['number'] ?? '';
        $numeration = $ficInvoice['numeration'] ?? '';
        
        // Se numeration è già incluso nel number, ritorna number direttamente
        if (empty($numeration) || str_contains((string)$number, '/')) {
            return (string)$number;
        }
        
        // Rimuovi lo slash iniziale da numeration se presente
        $numeration = ltrim($numeration, '/');
        
        // Concatena number + / + numeration
        return "{$number}/{$numeration}";
    }
}
