<?php

namespace App\Console\Commands;

use App\Services\AccountingReconciliationService;
use Illuminate\Console\Command;

class AutoReconcileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:auto-reconcile 
                            {--dry-run : Mostra solo i match senza applicarli}
                            {--min-score=90 : Score minimo per riconciliazione automatica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Riconcilia automaticamente le transazioni bancarie con le fatture';

    public function __construct(
        private AccountingReconciliationService $reconciliationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $minScore = (int) $this->option('min-score');

        $this->info("🔄 Avvio riconciliazione automatica...");
        
        if ($dryRun) {
            $this->warn("⚠️ Modalità DRY-RUN: nessuna modifica sarà applicata");
        }

        $this->info("📊 Score minimo per auto-riconciliazione: {$minScore}");
        $this->newLine();

        $result = $this->reconciliationService->autoReconcileWithScoring();

        // Mostra riepilogo
        $this->table(
            ['Livello Confidenza', 'Quantità'],
            [
                ['🟢 Alta (≥90)', $result['high_confidence']],
                ['🟡 Media (70-89)', $result['medium_confidence']],
                ['🟠 Bassa (50-69)', $result['low_confidence']],
                ['⚪ Non matchate', $result['unmatched']],
            ]
        );

        // Mostra dettagli match
        if (!empty($result['matches']) && $this->getOutput()->isVerbose()) {
            $this->newLine();
            $this->info("📋 Dettaglio match:");

            foreach ($result['matches'] as $match) {
                $bestMatch = $match['best_match'];
                $scoreEmoji = $bestMatch['score'] >= 90 ? '🟢' : ($bestMatch['score'] >= 70 ? '🟡' : '🟠');
                
                $this->line(sprintf(
                    "  %s [%d%%] Transazione #%d (€%.2f) → %s #%s (€%.2f)",
                    $scoreEmoji,
                    $bestMatch['score'],
                    $match['transaction_id'],
                    $match['amount'],
                    $bestMatch['type'] === 'invoice' ? 'Fattura' : 'Fattura Fornitore',
                    $bestMatch['invoice_number'],
                    $bestMatch['invoice_amount']
                ));

                if (!empty($bestMatch['reasons'])) {
                    $this->line("     Motivi: " . implode(', ', $bestMatch['reasons']));
                }
            }
        }

        $this->newLine();
        
        if ($dryRun) {
            $this->info("✅ Analisi completata (dry-run)");
            $this->line("   Esegui senza --dry-run per applicare le riconciliazioni");
        } else {
            $this->info("✅ Riconciliazione completata!");
            $this->line("   {$result['high_confidence']} transazioni riconciliate automaticamente");
        }

        return self::SUCCESS;
    }
}
