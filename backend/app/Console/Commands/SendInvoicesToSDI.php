<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\InvoicingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendInvoicesToSDI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:send-to-sdi
                            {--invoice_id= : ID specifico della fattura da inviare}
                            {--date-from= : Data inizio periodo (Y-m-d)}
                            {--date-to= : Data fine periodo (Y-m-d)}
                            {--client_id= : ID cliente specifico}
                            {--limit=100 : Numero massimo di fatture da processare}
                            {--dry-run : Simula l\'operazione senza inviare realmente}
                            {--force : Invia anche fatture già inviate (re-invio)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invia fatture elettroniche al Sistema di Interscambio (SDI) tramite Fatture in Cloud';

    /**
     * Execute the console command.
     */
    public function handle(InvoicingService $invoicingService): int
    {
        $this->info('🚀 Avvio invio fatture al SDI tramite Fatture in Cloud');
        $this->newLine();

        // Costruisci query
        $query = Invoice::with(['client', 'items']);

        // Filtro per ID specifico
        if ($invoiceId = $this->option('invoice_id')) {
            $query->where('id', $invoiceId);
            $this->info("📄 Filtrando per fattura ID: {$invoiceId}");
        }

        // Filtro per periodo
        if ($dateFrom = $this->option('date-from')) {
            $query->where('data_emissione', '>=', $dateFrom);
            $this->info("📅 Data inizio: {$dateFrom}");
        }

        if ($dateTo = $this->option('date-to')) {
            $query->where('data_emissione', '<=', $dateTo);
            $this->info("📅 Data fine: {$dateTo}");
        }

        // Filtro per cliente
        if ($clientId = $this->option('client_id')) {
            $query->where('client_id', $clientId);
            $this->info("👤 Filtrando per cliente ID: {$clientId}");
        }

        // Escludi fatture già inviate (a meno che non sia --force)
        if (!$this->option('force')) {
            $query->whereNull('sdi_sent_at');
            $this->info("Elaborando solo fatture NON ancora inviate al SDI");
        } else {
            $this->warn("⚠️  Modalità FORCE attiva: verranno processate anche fatture già inviate");
        }

        // Solo fatture attive (emesse, non bozze)
        $query->whereIn('payment_status', ['emessa', 'pagata', 'scaduta']);

        // Applica limite
        $limit = (int) $this->option('limit');
        $query->limit($limit);

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            $this->warn('⚠️  Nessuna fattura trovata con i criteri specificati.');
            return Command::SUCCESS;
        }

        $this->info("📊 Trovate {$invoices->count()} fatture da processare");
        $this->newLine();

        // Conferma se non in dry-run
        if (!$this->option('dry-run')) {
            if (!$this->confirm('Procedere con l\'invio al SDI?', true)) {
                $this->warn('❌ Operazione annullata dall\'utente');
                return Command::SUCCESS;
            }
        } else {
            $this->warn('🔍 Modalità DRY-RUN: nessuna fattura verrà realmente inviata');
        }

        $this->newLine();

        // Statistiche
        $stats = [
            'total' => $invoices->count(),
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        // Progress bar
        $progressBar = $this->output->createProgressBar($invoices->count());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Inizializzazione...');
        $progressBar->start();

        foreach ($invoices as $invoice) {
            $progressBar->setMessage("Fattura {$invoice->numero_fattura}");
            $progressBar->advance();

            // Verifica prerequisiti
            if (!$invoice->client) {
                $this->newLine();
                $this->error("❌ Fattura {$invoice->numero_fattura}: Cliente mancante");
                $stats['skipped']++;
                continue;
            }

            if (!$invoice->client->codice_sdi && !$invoice->client->pec) {
                $this->newLine();
                $this->error("❌ Fattura {$invoice->numero_fattura}: Cliente {$invoice->client->ragione_sociale} senza Codice SDI o PEC");
                $stats['skipped']++;
                continue;
            }

            // Dry run
            if ($this->option('dry-run')) {
                $this->newLine();
                $this->info("🔍 [DRY-RUN] Fattura {$invoice->numero_fattura} per {$invoice->client->ragione_sociale} - Pronta per invio");
                $stats['success']++;
                continue;
            }

            // Invio reale
            try {
                $invoicingService->sendToSDI($invoice, autoCreate: true);
                
                $this->newLine();
                $this->info("Fattura {$invoice->numero_fattura} inviata con successo");
                $stats['success']++;

                // Pausa per evitare rate limiting
                usleep(500000); // 500ms
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Fattura {$invoice->numero_fattura}: {$e->getMessage()}");
                $stats['failed']++;

                Log::error('[SendInvoicesToSDI] Errore invio fattura', [
                    'invoice_id' => $invoice->id,
                    'numero_fattura' => $invoice->numero_fattura,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Riepilogo finale
        $this->info('📊 RIEPILOGO INVIO FATTURE AL SDI');
        $this->table(
            ['Statistica', 'Valore'],
            [
                ['Totale fatture', $stats['total']],
                ['Inviate con successo', $stats['success']],
                ['❌ Errori', $stats['failed']],
                ['⏭️  Saltate', $stats['skipped']],
            ]
        );

        $this->newLine();

        if ($stats['failed'] > 0) {
            $this->warn("⚠️  {$stats['failed']} fatture NON sono state inviate. Controllare i log per dettagli.");
            return Command::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('🔍 DRY-RUN completato. Esegui senza --dry-run per invio reale.');
        } else {
            $this->info('Invio fatture completato con successo!');
        }

        return Command::SUCCESS;
    }
}
