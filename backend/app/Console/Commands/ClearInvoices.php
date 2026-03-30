<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class ClearInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:clear 
                            {--force : Forza l\'eliminazione senza conferma}
                            {--keep-items : Mantieni gli items (elimina solo fatture)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Svuota la tabella fatture e relativi items';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        $keepItems = $this->option('keep-items');

        // Conta record
        $invoicesCount = Invoice::withTrashed()->count();
        $itemsCount = InvoiceItem::count();

        if ($invoicesCount === 0) {
            $this->info('Nessuna fattura da eliminare.');
            return 0;
        }

        $this->warn("⚠️  ATTENZIONE: Stai per eliminare:");
        $this->line("   • {$invoicesCount} fatture (incluse soft-deleted)");
        if (!$keepItems) {
            $this->line("   • {$itemsCount} items fattura");
        }
        $this->newLine();

        // Conferma
        if (!$force) {
            if (!$this->confirm('Sei sicuro di voler procedere?', false)) {
                $this->info('Operazione annullata.');
                return 0;
            }
        }

        try {
            DB::beginTransaction();

            // 1. Elimina items (se richiesto)
            if (!$keepItems) {
                $this->info('🗑️  Eliminazione items fattura...');
                $deletedItems = DB::table('invoice_items')->delete();
                $this->line("   {$deletedItems} items eliminati");
            }

            // 2. Elimina fatture (force delete per rimuovere anche soft-deleted)
            $this->info('🗑️  Eliminazione fatture...');
            $deletedInvoices = DB::table('invoices')->delete();
            $this->line("   {$deletedInvoices} fatture eliminate");

            // 3. Reset auto-increment
            $this->info('🔄 Reset auto-increment...');
            
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE invoices AUTO_INCREMENT = 1');
                if (!$keepItems) {
                    DB::statement('ALTER TABLE invoice_items AUTO_INCREMENT = 1');
                }
            } elseif ($driver === 'sqlite') {
                DB::statement('DELETE FROM sqlite_sequence WHERE name="invoices"');
                if (!$keepItems) {
                    DB::statement('DELETE FROM sqlite_sequence WHERE name="invoice_items"');
                }
            }
            
            $this->line("   Auto-increment resettati");

            DB::commit();

            $this->newLine();
            $this->info('Tabella fatture svuotata con successo!');
            
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error('❌ Errore durante l\'eliminazione:');
            $this->error('   ' . $e->getMessage());
            
            return 1;
        }
    }
}
