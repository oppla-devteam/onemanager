<?php

namespace App\Console\Commands;

use App\Models\ApplicationFee;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportApplicationFeesFromCsv extends Command
{
    protected $signature = 'stripe:import-fees-csv {file : Path to CSV file}';
    protected $description = 'Import Application Fees from Stripe CSV export';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File non trovato: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("📄 Importazione Application Fees da CSV: {$filePath}");
        $this->newLine();

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        DB::beginTransaction();

        try {
            $handle = fopen($filePath, 'r');
            
            // Salta header
            $header = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                try {
                    // Parsing CSV: id,Created (UTC),Amount,Amount Refunded,Currency,User ID,User Email,Application ID,Transaction ID
                    $feeId = $row[0];
                    $createdAt = $row[1];
                    $amount = $row[2];
                    $currency = $row[4] ?? 'eur';
                    $accountId = $row[5];
                    $email = $row[6];
                    $transactionId = $row[8] ?? null;

                    // Converti amount da formato europeo (virgola) a float
                    $amount = str_replace(',', '.', $amount);
                    $amount = (float) $amount;

                    // Parse data
                    $createdAtCarbon = Carbon::createFromFormat('Y-m-d H:i', $createdAt);

                    // Cerca cliente per email
                    $client = null;
                    if ($email) {
                        $client = Client::where('email', $email)->first();
                    }

                    // Verifica se esiste già
                    $existing = ApplicationFee::where('stripe_fee_id', $feeId)->first();

                    if ($existing) {
                        // Aggiorna se necessario
                        if ($existing->amount != $amount || $existing->partner_email != $email) {
                            $existing->update([
                                'amount' => $amount,
                                'partner_email' => $email,
                                'partner_name' => $client?->ragione_sociale ?? 'Partner',
                                'client_id' => $client?->id,
                            ]);
                            $updated++;
                            $this->line("🔄 Aggiornata: {$feeId} - {$email} - €{$amount}");
                        } else {
                            $skipped++;
                        }
                    } else {
                        // Crea nuova
                        ApplicationFee::create([
                            'stripe_fee_id' => $feeId,
                            'amount' => $amount,
                            'currency' => strtoupper($currency),
                            'created_at_stripe' => $createdAtCarbon,
                            'stripe_account_id' => $accountId,
                            'partner_email' => $email,
                            'partner_name' => $client?->ragione_sociale ?? 'Partner',
                            'client_id' => $client?->id,
                            'charge_id' => $transactionId,
                            'description' => "{$email} - {$accountId}",
                            'period_month' => $createdAtCarbon->format('Y-m'),
                            'raw_data' => [
                                'fee_id' => $feeId,
                                'created' => $createdAt,
                                'account_id' => $accountId,
                                'email' => $email,
                                'transaction_id' => $transactionId,
                            ],
                        ]);
                        $imported++;
                        $this->line("Importata: {$feeId} - {$email} - €{$amount}");
                    }

                } catch (\Exception $e) {
                    $errors++;
                    $this->error("❌ Errore riga: " . $e->getMessage());
                    Log::error('[ImportFeesCSV] Errore importazione riga', [
                        'row' => $row,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            fclose($handle);

            DB::commit();

            $this->newLine();
            $this->info("Importazione completata!");
            $this->info("📊 Statistiche:");
            $this->info("  • Importate: {$imported}");
            if ($updated > 0) {
                $this->info("  • Aggiornate: {$updated}");
            }
            if ($skipped > 0) {
                $this->info("  • Saltate (già esistenti): {$skipped}");
            }
            if ($errors > 0) {
                $this->warn("  • Errori: {$errors}");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Errore durante l'importazione: " . $e->getMessage());
            Log::error('[ImportFeesCSV] Errore generale', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}
