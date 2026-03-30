<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Models\BankTransaction;
use App\Models\AccountingCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BankStatementImportService
{
    /**
     * Importa un estratto conto da file Excel/CSV
     */
    public function importFromFile(BankAccount $bankAccount, string $filePath, int $month, int $year): BankStatement
    {
        try {
            DB::beginTransaction();

            // Crea o recupera l'estratto conto
            $statement = BankStatement::firstOrCreate(
                [
                    'bank_account_id' => $bankAccount->id,
                    'month' => $month,
                    'year' => $year,
                ],
                [
                    'period_start' => Carbon::create($year, $month, 1)->startOfMonth(),
                    'period_end' => Carbon::create($year, $month, 1)->endOfMonth(),
                    'saldo_iniziale' => 0,
                    'saldo_finale' => 0,
                    'status' => 'pending',
                ]
            );

            // Salva il percorso del file
            $statement->excel_file_path = $filePath;
            $statement->save();

            // Leggi il file e importa le transazioni
            $transactions = $this->parseFile($filePath, $bankAccount->type);

            $totalEntrate = 0;
            $totalUscite = 0;

            foreach ($transactions as $transactionData) {
                $transaction = $this->createTransaction($bankAccount, $statement, $transactionData);
                
                if ($transaction->type === 'entrata') {
                    $totalEntrate += abs($transaction->amount);
                } else {
                    $totalUscite += abs($transaction->amount);
                }

                // Auto-categorizza
                $this->autoCategorize($transaction);
            }

            // Aggiorna i totali
            $statement->totale_entrate = $totalEntrate;
            $statement->totale_uscite = $totalUscite;
            $statement->status = 'imported';
            $statement->imported_at = now();
            $statement->save();

            DB::commit();

            return $statement;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore import estratto conto: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Parse del file Excel/CSV
     */
    private function parseFile(string $filePath, string $accountType): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseExcel($filePath, $accountType);
        } elseif ($extension === 'csv') {
            return $this->parseCsv($filePath, $accountType);
        }

        throw new \Exception('Formato file non supportato');
    }

    /**
     * Parse file Excel
     */
    private function parseExcel(string $filePath, string $accountType): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Rimuovi header (prime 2 righe solitamente)
        array_shift($rows);
        array_shift($rows);

        $transactions = [];

        foreach ($rows as $row) {
            if (empty($row[0])) continue; // Skip righe vuote

            try {
                $transaction = $this->mapRowToTransaction($row, $accountType);
                if ($transaction) {
                    $transactions[] = $transaction;
                }
            } catch (\Exception $e) {
                Log::warning('Errore parsing riga: ' . json_encode($row) . ' - ' . $e->getMessage());
                continue;
            }
        }

        return $transactions;
    }

    /**
     * Parse file CSV
     */
    private function parseCsv(string $filePath, string $accountType): array
    {
        $transactions = [];
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Skip header
            fgetcsv($handle);

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                try {
                    $transaction = $this->mapRowToTransaction($row, $accountType);
                    if ($transaction) {
                        $transactions[] = $transaction;
                    }
                } catch (\Exception $e) {
                    Log::warning('Errore parsing riga CSV: ' . json_encode($row) . ' - ' . $e->getMessage());
                    continue;
                }
            }
            fclose($handle);
        }

        return $transactions;
    }

    /**
     * Mappa una riga del file a una transazione
     */
    private function mapRowToTransaction(array $row, string $accountType): ?array
    {
        // Formato generico banca italiana
        // [0] = Data transazione, [1] = Data valuta, [2] = Descrizione, [3] = Importo, [4] = Saldo

        if (count($row) < 4) {
            return null;
        }

        $transactionDate = $this->parseDate($row[0]);
        $valueDate = isset($row[1]) ? $this->parseDate($row[1]) : $transactionDate;
        $descrizione = $row[2] ?? '';
        $amount = $this->parseAmount($row[3] ?? '0');
        $balance = isset($row[4]) ? $this->parseAmount($row[4]) : null;

        if (!$transactionDate || $amount == 0) {
            return null;
        }

        // Determina il tipo (entrata/uscita)
        $type = $amount > 0 ? 'entrata' : 'uscita';
        
        // Estrai beneficiario e causale dalla descrizione
        $parsedData = $this->parseDescription($descrizione);

        return [
            'transaction_date' => $transactionDate,
            'value_date' => $valueDate,
            'type' => $type,
            'amount' => abs($amount),
            'descrizione' => $descrizione,
            'causale' => $parsedData['causale'],
            'beneficiario' => $parsedData['beneficiario'],
            'balance_after' => $balance,
        ];
    }

    /**
     * Parse della descrizione per estrarre causale e beneficiario
     */
    private function parseDescription(string $description): array
    {
        $beneficiario = null;
        $causale = null;

        // Pattern comuni per estratti conto italiani
        if (preg_match('/BONIFICO.*?(?:DA|A)\s+(.*?)(?:\s+(?:CAUSALE|CRO)|$)/i', $description, $matches)) {
            $beneficiario = trim($matches[1]);
        }

        if (preg_match('/CAUSALE[:\s]+(.*?)(?:\s+CRO|$)/i', $description, $matches)) {
            $causale = trim($matches[1]);
        }

        // Stripe
        if (str_contains(strtolower($description), 'stripe')) {
            $beneficiario = 'Stripe Technology Europe Ltd';
            $causale = 'Incasso commissioni';
        }

        // Stipendi
        if (preg_match('/(FRESCHI|GIACHETTI|MOSCHELLA|SUPERTI)/i', $description, $matches)) {
            $beneficiario = $matches[1];
            $causale = 'Stipendio/Compenso';
        }

        return [
            'beneficiario' => $beneficiario,
            'causale' => $causale,
        ];
    }

    /**
     * Crea una transazione
     */
    private function createTransaction(BankAccount $bankAccount, BankStatement $statement, array $data): BankTransaction
    {
        return BankTransaction::create([
            'bank_account_id' => $bankAccount->id,
            'bank_statement_id' => $statement->id,
            'transaction_date' => $data['transaction_date'],
            'value_date' => $data['value_date'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'currency' => 'EUR',
            'descrizione' => $data['descrizione'],
            'causale' => $data['causale'],
            'beneficiario' => $data['beneficiario'],
            'balance_after' => $data['balance_after'],
            'is_reconciled' => false,
        ]);
    }

    /**
     * Auto-categorizza una transazione
     */
    private function autoCategorize(BankTransaction $transaction): void
    {
        $categories = AccountingCategory::active()->get();

        foreach ($categories as $category) {
            if ($category->matchesDescription($transaction->descrizione)) {
                $transaction->category_id = $category->id;
                $transaction->save();
                return;
            }
        }
    }

    /**
     * Parse data in vari formati
     */
    private function parseDate($dateString): ?Carbon
    {
        if (empty($dateString)) {
            return null;
        }

        // Prova vari formati comuni
        $formats = [
            'd/m/Y',
            'd-m-Y',
            'Y-m-d',
            'd/m/y',
            'd.m.Y',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date) {
                    return $date;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Prova parsing automatico
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse importo
     */
    private function parseAmount($amountString): float
    {
        if (empty($amountString)) {
            return 0;
        }

        // Rimuovi spazi e sostituisci virgola con punto
        $amount = str_replace([' ', '€', ','], ['', '', '.'], $amountString);
        
        return (float) $amount;
    }
}
