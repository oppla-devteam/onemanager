<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\FattureInCloudConnection;
use App\Services\FattureInCloudService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSDIStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:check-sdi-status
                            {--invoice_id= : ID specifico della fattura da controllare}
                            {--date-from= : Data inizio periodo (Y-m-d)}
                            {--date-to= : Data fine periodo (Y-m-d)}
                            {--status= : Filtra per stato SDI specifico (sent, accepted, rejected)}
                            {--limit=50 : Numero massimo di fatture da verificare}
                            {--update : Aggiorna automaticamente lo stato locale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Controlla lo stato delle fatture elettroniche inviate al SDI tramite Fatture in Cloud';

    /**
     * Execute the console command.
     */
    public function handle(FattureInCloudService $ficService): int
    {
        $this->info('🔍 Controllo stato fatture inviate al SDI');
        $this->newLine();

        // Verifica connessione FIC attiva
        $ficConnection = FattureInCloudConnection::where('is_active', true)->first();

        if (!$ficConnection) {
            $this->error('❌ Nessuna connessione Fatture in Cloud attiva. Configurare OAuth prima.');
            return Command::FAILURE;
        }

        $this->info("Connessione FIC attiva: {$ficConnection->fic_company_name}");
        $this->newLine();

        // Costruisci query
        $query = Invoice::with('client')
            ->whereNotNull('fic_document_id')
            ->whereNotNull('sdi_sent_at');

        // Filtro per ID specifico
        if ($invoiceId = $this->option('invoice_id')) {
            $query->where('id', $invoiceId);
            $this->info("📄 Filtrando per fattura ID: {$invoiceId}");
        }

        // Filtro per periodo
        if ($dateFrom = $this->option('date-from')) {
            $query->where('sdi_sent_at', '>=', $dateFrom);
            $this->info("📅 Data invio da: {$dateFrom}");
        }

        if ($dateTo = $this->option('date-to')) {
            $query->where('sdi_sent_at', '<=', $dateTo);
            $this->info("📅 Data invio a: {$dateTo}");
        }

        // Filtro per stato
        if ($status = $this->option('status')) {
            $query->where('sdi_status', $status);
            $this->info("🏷️  Filtrando per stato: {$status}");
        }

        // Applica limite
        $limit = (int) $this->option('limit');
        $query->limit($limit);

        // Ordina per data invio (più recenti prima)
        $query->orderBy('sdi_sent_at', 'desc');

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            $this->warn('⚠️  Nessuna fattura trovata con i criteri specificati.');
            return Command::SUCCESS;
        }

        $this->info("📊 Trovate {$invoices->count()} fatture da verificare");
        $this->newLine();

        // Progress bar
        $progressBar = $this->output->createProgressBar($invoices->count());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Inizializzazione...');
        $progressBar->start();

        $results = [];

        foreach ($invoices as $invoice) {
            $progressBar->setMessage("Fattura {$invoice->numero_fattura}");
            $progressBar->advance();

            try {
                // Recupera stato da FIC
                $ficStatus = $ficService->getInvoiceStatus($ficConnection, $invoice->fic_document_id);

                if (!$ficStatus) {
                    $results[] = [
                        'numero_fattura' => $invoice->numero_fattura,
                        'stato_locale' => $invoice->sdi_status ?? 'N/A',
                        'stato_fic' => 'ERRORE',
                        'inviata_il' => $invoice->sdi_sent_at?->format('d/m/Y H:i'),
                    ];
                    continue;
                }

                // Estrai stato SDI da risposta FIC
                $eiStatus = $ficStatus['ei_status'] ?? null;
                $eInvoiceSent = $ficStatus['e_invoice_sent'] ?? false;

                // Determina stato human-readable
                $statusDisplay = $this->parseSDIStatus($eiStatus, $eInvoiceSent);

                $results[] = [
                    'numero_fattura' => $invoice->numero_fattura,
                    'stato_locale' => $invoice->sdi_status ?? 'N/A',
                    'stato_fic' => $statusDisplay,
                    'inviata_il' => $invoice->sdi_sent_at?->format('d/m/Y H:i'),
                ];

                // Aggiorna stato locale se richiesto
                if ($this->option('update') && $eiStatus && $eiStatus !== $invoice->sdi_status) {
                    $invoice->update([
                        'sdi_status' => $eiStatus,
                    ]);

                    Log::info('[CheckSDIStatus] Stato fattura aggiornato', [
                        'invoice_id' => $invoice->id,
                        'old_status' => $invoice->sdi_status,
                        'new_status' => $eiStatus,
                    ]);
                }

                // Pausa per evitare rate limiting
                usleep(200000); // 200ms
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Errore recupero stato fattura {$invoice->numero_fattura}: {$e->getMessage()}");

                $results[] = [
                    'numero_fattura' => $invoice->numero_fattura,
                    'stato_locale' => $invoice->sdi_status ?? 'N/A',
                    'stato_fic' => 'ERRORE: ' . substr($e->getMessage(), 0, 30),
                    'inviata_il' => $invoice->sdi_sent_at?->format('d/m/Y H:i'),
                ];
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Visualizza risultati in tabella
        $this->info('📊 STATO FATTURE ELETTRONICHE');
        $this->table(
            ['Numero Fattura', 'Stato Locale', 'Stato FIC/SDI', 'Inviata il'],
            $results
        );

        $this->newLine();

        if ($this->option('update')) {
            $this->info('Stati locali aggiornati con successo');
        } else {
            $this->info('ℹ️  Usa --update per aggiornare automaticamente gli stati locali');
        }

        return Command::SUCCESS;
    }

    /**
     * Parse SDI status to human-readable format
     */
    private function parseSDIStatus(?string $eiStatus, bool $eInvoiceSent): string
    {
        if (!$eiStatus) {
            return $eInvoiceSent ? '📤 Inviata' : '⏳ Pendente';
        }

        return match ($eiStatus) {
            'sent' => '📤 Inviata al SDI',
            'accepted' => 'Accettata',
            'delivered' => 'Consegnata',
            'accepted_by_recipient' => 'Accettata dal cliente',
            'rejected' => '❌ Rifiutata dal SDI',
            'rejected_by_recipient' => '❌ Rifiutata dal cliente',
            'not_delivered' => '⚠️  Non consegnata',
            'error' => '❌ Errore',
            default => "❓ {$eiStatus}",
        };
    }
}
