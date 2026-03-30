<?php

namespace App\Services;

use App\Models\BankTransaction;
use App\Models\BankAccount;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentImportService
{
    /**
     * Auto-rileva la sorgente del CSV dalla struttura
     */
    public function detectCSVSource(string $filePath): ?string
    {
        $file = fopen($filePath, 'r');
        if (!$file) {
            return null;
        }

        // Leggi tutte le prime righe per Nexi (che ha header multi-riga)
        $lines = [];
        for ($i = 0; $i < 10; $i++) {
            $line = fgetcsv($file);
            if ($line) {
                $lines[] = $line;
            }
        }
        fclose($file);

        if (empty($lines)) {
            return null;
        }

        // Converti tutte le righe in stringa lowercase per il confronto
        $allText = strtolower(implode(' ', array_merge(...$lines)));

        Log::info('[CSV Detection] Analisi file:', [
            'first_line' => $lines[0] ?? null,
            'all_text_preview' => substr($allText, 0, 500)
        ]);

        // CRV (Banca): "Data contabile,Valuta,Dare,Avere,Divisa,Causale,Descrizione"
        if (str_contains($allText, 'data contabile') && str_contains($allText, 'dare') && str_contains($allText, 'avere')) {
            Log::info('[CSV Detection] Formato rilevato: BANK/CRV');
            return 'bank';
        }

        // Nexi: Controlla se contiene parole chiave tipiche di Nexi
        // Nexi ha un formato con header multi-riga che include parole come:
        // "estratto conto", "carta", "periodo", "mese", "data", "riferimento"
        $nexiKeywords = ['carta', 'estratto', 'riferimento', 'commissione'];
        $nexiMatches = 0;
        foreach ($nexiKeywords as $keyword) {
            if (str_contains($allText, $keyword)) {
                $nexiMatches++;
            }
        }
        
        // Controlla anche la struttura delle colonne nella riga con "Data"
        foreach ($lines as $line) {
            $lineStr = strtolower(implode(',', $line));
            if (str_contains($lineStr, 'data') && 
                str_contains($lineStr, 'riferimento') && 
                (str_contains($lineStr, 'categoria') || str_contains($lineStr, 'categorie'))) {
                Log::info('[CSV Detection] Formato rilevato: NEXI (da struttura colonne)');
                return 'nexi';
            }
        }

        // Se ha almeno 2 keyword tipiche di Nexi
        if ($nexiMatches >= 2) {
            Log::info('[CSV Detection] Formato rilevato: NEXI (da keywords, matches: ' . $nexiMatches . ')');
            return 'nexi';
        }

        // PayPal: "Data,Orario,Fuso orario,Nome,Tipo,Stato,Valuta,Lordo,Tariffa,Netto"
        if (str_contains($allText, 'fuso orario') && str_contains($allText, 'tariffa') && str_contains($allText, 'netto')) {
            Log::info('[CSV Detection] Formato rilevato: PAYPAL');
            return 'paypal';
        }

        // Vivawallet: "Data di transazione,Data valuta,Descrizione,Importo,Saldo"
        if (str_contains($allText, 'data di transazione') && str_contains($allText, 'data valuta')) {
            Log::info('[CSV Detection] Formato rilevato: VIVAWALLET');
            return 'vivawallet';
        }

        Log::warning('[CSV Detection] Formato NON rilevato. Primi dati:', [
            'lines_count' => count($lines),
            'sample' => array_slice($lines, 0, 3)
        ]);

        return null;
    }

    /**
     * Importa pagamenti da CSV CRV (Banca)
     */
    public function importCRV(string $filePath, int $bankAccountId): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            $file = fopen($filePath, 'r');
            
            // Salta header
            fgetcsv($file);
            
            while (($row = fgetcsv($file)) !== false) {
                try {
                    // Parsing CSV CRV
                    $dataContabile = $this->parseDate($row[0]);
                    $valuta = $this->parseDate($row[1]);
                    $dare = $this->parseAmount($row[2]);
                    $avere = $this->parseAmount($row[3]);
                    $divisa = $row[4] ?? 'EUR';
                    $causale = $row[5] ?? '';
                    $descrizione = $row[6] ?? '';
                    $categoria = $row[7] ?? '';
                    
                    // Determina tipo e importo
                    $amount = 0;
                    $type = 'altro';
                    
                    if ($avere > 0) {
                        $amount = $avere;
                        $type = 'entrata';
                    } elseif ($dare > 0) {
                        $amount = -$dare;
                        $type = 'uscita';
                    }
                    
                    if ($amount == 0) continue;
                    
                    // Genera ID univoco
                    $sourceTransactionId = md5($dataContabile->format('Y-m-d') . $descrizione . $amount);
                    
                    // Estrai beneficiario specifico per CRV
                    $normalizedBeneficiary = $this->extractBeneficiaryFromCRV($descrizione);
                    
                    // Cerca cliente
                    $clientId = $this->findClientByBeneficiary($normalizedBeneficiary);
                    
                    // Inserisci solo se non esiste
                    $existing = BankTransaction::where('source', 'bank')
                        ->where('source_transaction_id', $sourceTransactionId)
                        ->first();
                    
                    if ($existing) {
                        $skipped++;
                        continue;
                    }
                    
                    BankTransaction::create([
                        'bank_account_id' => $bankAccountId,
                        'source' => 'bank',
                        'source_transaction_id' => $sourceTransactionId,
                        'transaction_date' => $dataContabile,
                        'value_date' => $valuta,
                        'type' => $type,
                        'amount' => $amount,
                        'currency' => $divisa,
                        'descrizione' => $descrizione,
                        'causale' => $causale,
                        'beneficiario' => $normalizedBeneficiary,
                        'normalized_beneficiary' => $normalizedBeneficiary,
                        'client_id' => $clientId,
                        'category' => $categoria,
                        'source_data' => json_encode([
                            'data_contabile' => $dataContabile->format('d/m/Y'),
                            'valuta' => $valuta->format('d/m/Y'),
                            'causale' => $causale,
                        ]),
                    ]);
                    
                    $imported++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Riga " . ($imported + $skipped + 1) . ": " . $e->getMessage();
                    Log::error('[ImportCRV] Errore riga: ' . $e->getMessage(), ['row' => $row]);
                }
            }
            
            fclose($file);
            
        } catch (\Exception $e) {
            Log::error('[ImportCRV] Errore generale: ' . $e->getMessage());
            throw $e;
        }
        
        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Importa pagamenti da CSV Vivawallet
     */
    public function importVivawallet(string $filePath, int $bankAccountId): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            $file = fopen($filePath, 'r');
            
            // Salta header e saldo iniziale
            fgetcsv($file);
            fgetcsv($file);
            
            while (($row = fgetcsv($file)) !== false) {
                try {
                    // Skip saldo riportato
                    if (strpos($row[0], 'Saldo') !== false) continue;
                    
                    $dataTransazione = $this->parseDate($row[0]);
                    $dataValuta = $this->parseDate($row[1]);
                    $descrizione = $row[2] ?? '';
                    $importo = $this->parseAmount($row[3]);
                    
                    if ($importo == 0) continue;
                    
                    // Determina tipo
                    $type = $importo > 0 ? 'entrata' : 'uscita';
                    
                    // Estrai beneficiario dalla descrizione
                    $beneficiario = $this->extractBeneficiaryFromVivawallet($descrizione);
                    $normalizedBeneficiary = $this->normalizeBeneficiary($beneficiario);
                    
                    // ID univoco
                    $sourceTransactionId = md5($dataTransazione->format('Y-m-d') . $descrizione . $importo);
                    
                    $clientId = $this->findClientByBeneficiary($normalizedBeneficiary);
                    
                    $existing = BankTransaction::where('source', 'vivawallet')
                        ->where('source_transaction_id', $sourceTransactionId)
                        ->first();
                    
                    if ($existing) {
                        $skipped++;
                        continue;
                    }
                    
                    BankTransaction::create([
                        'bank_account_id' => $bankAccountId,
                        'source' => 'vivawallet',
                        'source_transaction_id' => $sourceTransactionId,
                        'transaction_date' => $dataTransazione,
                        'value_date' => $dataValuta,
                        'type' => $type,
                        'amount' => $importo,
                        'currency' => 'EUR',
                        'descrizione' => $descrizione,
                        'beneficiario' => $beneficiario,
                        'normalized_beneficiary' => $normalizedBeneficiary,
                        'client_id' => $clientId,
                        'source_data' => json_encode([
                            'raw_description' => $descrizione,
                        ]),
                    ]);
                    
                    $imported++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Riga " . ($imported + $skipped + 1) . ": " . $e->getMessage();
                }
            }
            
            fclose($file);
            
        } catch (\Exception $e) {
            Log::error('[ImportVivawallet] Errore: ' . $e->getMessage());
            throw $e;
        }
        
        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Importa pagamenti da CSV Nexi
     */
    public function importNexi(string $filePath, int $bankAccountId): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            $file = fopen($filePath, 'r');
            
            // Salta headers (prime 7 righe)
            for ($i = 0; $i < 7; $i++) {
                fgetcsv($file);
            }
            
            while (($row = fgetcsv($file)) !== false) {
                try {
                    // Skip se riga vuota o header ripetuto
                    if (empty($row[2]) || $row[2] == 'Data') continue;
                    
                    $data = $this->parseDate($row[2]);
                    $riferimento = $row[3] ?? '';
                    $categoria = $row[4] ?? '';
                    $descrizione = $row[5] ?? '';
                    $importoOriginale = $this->parseAmount($row[7] ?? '0');
                    $divisa = $row[8] ?? 'EUR';
                    $importoEur = $this->parseAmount($row[9] ?? '0');
                    $cambio = $this->parseAmount($row[10] ?? '0');
                    $commissioneNexi = $this->parseAmount($row[11] ?? '0');
                    $commissioneCircuito = $this->parseAmount($row[12] ?? '0');
                    
                    $amount = -abs($importoEur); // Nexi sono tutte uscite
                    $totalFee = $commissioneNexi + $commissioneCircuito;
                    
                    if ($amount == 0) continue;
                    
                    $sourceTransactionId = $riferimento;
                    
                    $normalizedBeneficiary = $this->normalizeBeneficiary($descrizione);
                    $clientId = $this->findClientByBeneficiary($normalizedBeneficiary);
                    
                    $existing = BankTransaction::where('source', 'nexi')
                        ->where('source_transaction_id', $sourceTransactionId)
                        ->first();
                    
                    if ($existing) {
                        $skipped++;
                        continue;
                    }
                    
                    BankTransaction::create([
                        'bank_account_id' => $bankAccountId,
                        'source' => 'nexi',
                        'source_transaction_id' => $sourceTransactionId,
                        'transaction_date' => $data,
                        'type' => 'uscita',
                        'amount' => $amount,
                        'fee' => $totalFee,
                        'net_amount' => $amount - $totalFee,
                        'currency' => 'EUR',
                        'descrizione' => $descrizione,
                        'beneficiario' => $descrizione,
                        'normalized_beneficiary' => $normalizedBeneficiary,
                        'client_id' => $clientId,
                        'category' => $categoria,
                        'source_data' => json_encode([
                            'riferimento' => $riferimento,
                            'categoria' => $categoria,
                            'importo_originale' => $importoOriginale,
                            'divisa_originale' => $divisa,
                            'cambio' => $cambio,
                            'commissione_nexi' => $commissioneNexi,
                            'commissione_circuito' => $commissioneCircuito,
                        ]),
                    ]);
                    
                    $imported++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Riga " . ($imported + $skipped + 1) . ": " . $e->getMessage();
                }
            }
            
            fclose($file);
            
        } catch (\Exception $e) {
            Log::error('[ImportNexi] Errore: ' . $e->getMessage());
            throw $e;
        }
        
        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Importa pagamenti da CSV PayPal
     */
    public function importPayPal(string $filePath, int $bankAccountId): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            $file = fopen($filePath, 'r');
            
            // Salta header
            fgetcsv($file);
            
            while (($row = fgetcsv($file)) !== false) {
                try {
                    $data = $this->parseDate($row[0]);
                    $nome = $row[3] ?? '';
                    $tipo = $row[4] ?? '';
                    $stato = $row[5] ?? '';
                    $valuta = $row[6] ?? 'EUR';
                    $lordo = $this->parseAmount($row[7]);
                    $tariffa = $this->parseAmount($row[8]);
                    $netto = $this->parseAmount($row[9]);
                    $codiceTransazione = $row[12] ?? '';
                    
                    // Solo transazioni completate
                    if ($stato !== 'Completata') continue;
                    
                    // Skip transazioni stornate
                    if ($tipo == 'Pagamento generico' && $stato == 'Stornata') continue;
                    
                    if ($netto == 0) continue;
                    
                    // Determina tipo
                    $transactionType = $netto > 0 ? 'entrata' : 'uscita';
                    
                    $sourceTransactionId = $codiceTransazione;
                    
                    $normalizedBeneficiary = $this->normalizeBeneficiary($nome);
                    $clientId = $this->findClientByBeneficiary($normalizedBeneficiary);
                    
                    $existing = BankTransaction::where('source', 'paypal')
                        ->where('source_transaction_id', $sourceTransactionId)
                        ->first();
                    
                    if ($existing) {
                        $skipped++;
                        continue;
                    }
                    
                    BankTransaction::create([
                        'bank_account_id' => $bankAccountId,
                        'source' => 'paypal',
                        'source_transaction_id' => $sourceTransactionId,
                        'transaction_date' => $data,
                        'type' => $transactionType,
                        'amount' => $netto,
                        'fee' => abs($tariffa),
                        'net_amount' => $netto,
                        'currency' => $valuta,
                        'descrizione' => $tipo,
                        'beneficiario' => $nome,
                        'normalized_beneficiary' => $normalizedBeneficiary,
                        'client_id' => $clientId,
                        'source_data' => json_encode([
                            'tipo' => $tipo,
                            'stato' => $stato,
                            'lordo' => $lordo,
                            'tariffa' => $tariffa,
                            'codice_transazione' => $codiceTransazione,
                        ]),
                    ]);
                    
                    $imported++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Riga " . ($imported + $skipped + 1) . ": " . $e->getMessage();
                }
            }
            
            fclose($file);
            
        } catch (\Exception $e) {
            Log::error('[ImportPayPal] Errore: ' . $e->getMessage());
            throw $e;
        }
        
        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Aggrega pagamenti per cliente/ragione sociale
     */
    public function aggregateByClient(array $filters = []): array
    {
        $query = BankTransaction::query()
            ->whereNotNull('normalized_beneficiary')
            ->select([
                'normalized_beneficiary',
                'client_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_income'),
                DB::raw('SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_expenses'),
                DB::raw('SUM(amount) as net_total'),
                DB::raw('MIN(transaction_date) as first_transaction'),
                DB::raw('MAX(transaction_date) as last_transaction'),
            ])
            ->groupBy('normalized_beneficiary', 'client_id');
        
        // Applica filtri
        if (!empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }
        
        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        // Filtro per destinazione trasferimento (solo pagamenti Stripe)
        if (!empty($filters['transfer_destination'])) {
            $query->where('source', 'stripe')
                  ->where('source_data', 'LIKE', '%"transfer_destination":"' . $filters['transfer_destination'] . '%');
        }
        
        $aggregated = $query->get();
        
        // Aggiungi info cliente
        $result = $aggregated->map(function ($item) {
            $client = null;
            if ($item->client_id) {
                $client = Client::find($item->client_id);
            }
            
            return [
                'beneficiary' => $item->normalized_beneficiary,
                'client_id' => $item->client_id,
                'client_name' => $client ? $client->ragione_sociale : null,
                'client_email' => $client ? $client->email : null,
                'transaction_count' => $item->transaction_count,
                'total_income' => round($item->total_income, 2),
                'total_expenses' => round($item->total_expenses, 2),
                'net_total' => round($item->net_total, 2),
                'first_transaction' => $item->first_transaction,
                'last_transaction' => $item->last_transaction,
                'can_generate_invoice' => $item->total_income > 0 && $item->client_id !== null,
            ];
        });
        
        return $result->toArray();
    }

    /**
     * Aggrega i pagamenti per destinazione trasferimento Stripe
     */
    public function aggregateByDestination(array $filters = []): array
    {
        $query = BankTransaction::query()
            ->where('source', 'stripe')
            ->whereNotNull('source_data')
            ->select(['source_data', 'amount', 'transaction_date']);
        
        // Applica filtri di data
        if (!empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }
        
        $transactions = $query->get();
        
        // Raggruppa per destinazione
        $grouped = [];
        
        foreach ($transactions as $transaction) {
            $sourceData = json_decode($transaction->source_data, true);
            
            if (!empty($sourceData['transfer_destination'])) {
                $destinationName = $sourceData['transfer_destination'];
                $destinationId = $sourceData['transfer_destination_id'] ?? null;
                
                if (!isset($grouped[$destinationName])) {
                    $grouped[$destinationName] = [
                        'destination_name' => $destinationName,
                        'destination_id' => $destinationId,
                        'transaction_count' => 0,
                        'total_amount' => 0,
                        'first_transaction' => $transaction->transaction_date,
                        'last_transaction' => $transaction->transaction_date,
                    ];
                }
                
                $grouped[$destinationName]['transaction_count']++;
                $grouped[$destinationName]['total_amount'] += $transaction->amount;
                
                // Aggiorna date
                if ($transaction->transaction_date < $grouped[$destinationName]['first_transaction']) {
                    $grouped[$destinationName]['first_transaction'] = $transaction->transaction_date;
                }
                if ($transaction->transaction_date > $grouped[$destinationName]['last_transaction']) {
                    $grouped[$destinationName]['last_transaction'] = $transaction->transaction_date;
                }
            }
        }
        
        // Converti in array e arrotonda importi
        $result = array_values($grouped);
        foreach ($result as &$item) {
            $item['total_amount'] = round($item['total_amount'], 2);
        }
        
        // Ordina per importo totale decrescente
        usort($result, function($a, $b) {
            return $b['total_amount'] <=> $a['total_amount'];
        });
        
        return $result;
    }

    /**
     * Helper: Parse date in formato italiano
     */
    private function parseDate(string $date): Carbon
    {
        // Prova formato DD/MM/YYYY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
            return Carbon::createFromFormat('d/m/Y', $date);
        }
        
        // Fallback formato standard
        return Carbon::parse($date);
    }

    /**
     * Helper: Parse amount (gestisce formato italiano con virgola)
     */
    private function parseAmount(string $amount): float
    {
        if (empty($amount)) return 0.0;
        
        // Rimuovi spazi e quotes
        $amount = trim($amount, " \t\n\r\0\x0B\"'");
        
        // Rimuovi separatori migliaia (punto)
        $amount = str_replace('.', '', $amount);
        
        // Sostituisci virgola con punto
        $amount = str_replace(',', '.', $amount);
        
        return (float) $amount;
    }

    /**
     * Helper: Normalizza beneficiario
     */
    /**
     * Helper: Estrai beneficiario da descrizione CRV/Banca
     */
    private function extractBeneficiaryFromCRV(string $description): string
    {
        // Pattern 1: "BONIF. DALL'ESTERO ... Nome Beneficiario" (cerca dopo N.LOG o simili)
        if (preg_match('/N\.LOG:\s*\d+\s+(.+?)(?:\s+\d{1,2}[A-Z]\d|$)/i', $description, $matches)) {
            $beneficiary = trim($matches[1]);
            // Rimuovi codici alla fine tipo "1 D01H104 STRIPE P4S1T3"
            $beneficiary = preg_replace('/\s+\d+\s+[A-Z0-9]+\s+[A-Z0-9\s]+$/i', '', $beneficiary);
            if (!empty($beneficiary)) {
                return $beneficiary;
            }
        }
        
        // Pattern 2: "Bonifico ... a vs favore NOME BENEFICIARIO Data Regolamento"
        if (preg_match('/a\s+vs\s+favore\s+([^D]+?)\s+Data\s+Regolamento/i', $description, $matches)) {
            return trim($matches[1]);
        }
        
        // Pattern 3: "VS DISPOSIZIONE DI BONIFICO Nome Beneficiario Bonifico"
        if (preg_match('/VS\s+DISPOSIZIONE\s+DI\s+BONIFICO\s+([^B]+?)(?:\s+Bonifico|$)/i', $description, $matches)) {
            return trim($matches[1]);
        }
        
        // Pattern 4: "ADDEBITO BONIFICO DA HOME BANKING Nome Beneficiario Bonifico Disposto"
        if (preg_match('/HOME\s+BANKING\s+([^B]+?)\s+Bonifico\s+Disposto/i', $description, $matches)) {
            return trim($matches[1]);
        }
        
        // Pattern 5: Cerca "Coord.Ordinante:" per bonifici in entrata
        if (preg_match('/Coord\.Ordinante:\s*[A-Z0-9\s]+\s+Banca\s+Ordinante:\s*[A-Z0-9]+\s+(?:Cro|Note):\s*([^V]+?)(?:\s+Valuta|$)/i', $description, $matches)) {
            $beneficiary = trim($matches[1]);
            // Pulisci codici tipo "REVITR25102780146348903"
            $beneficiary = preg_replace('/[A-Z]{3,}\d{10,}/', '', $beneficiary);
            $beneficiary = trim($beneficiary);
            if (!empty($beneficiary) && strlen($beneficiary) > 3) {
                return $beneficiary;
            }
        }
        
        // Fallback: usa la funzione generica
        return $this->normalizeBeneficiary($description);
    }

    private function normalizeBeneficiary(string $text): string
    {
        // Rimuovi prefissi comuni
        $text = preg_replace('/^(BONIF\.|Bonifico|Rimborso a IBAN|Aggiungi al saldo|ADDEBITO)/i', '', $text);
        
        // Pulisci
        $text = trim($text);
        
        // Estrai nome società se possibile
        if (preg_match('/([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*(?:\s+(?:SRL|SPA|SNC|SAS|S\.R\.L\.|S\.P\.A\.))?)/i', $text, $matches)) {
            return trim($matches[0]);
        }
        
        return Str::limit($text, 100);
    }

    /**
     * Helper: Estrai beneficiario da descrizione Vivawallet
     */
    private function extractBeneficiaryFromVivawallet(string $description): string
    {
        // Pattern: "Rimborso a IBAN - descrizione - IBAN - BENEFICIARIO"
        if (preg_match('/-\s*([^-]+)\s*$/', $description, $matches)) {
            return trim($matches[1]);
        }
        
        return $this->normalizeBeneficiary($description);
    }

    /**
     * Helper: Trova cliente da beneficiario
     */
    private function findClientByBeneficiary(?string $beneficiary): ?int
    {
        if (empty($beneficiary)) return null;
        
        $client = Client::where('ragione_sociale', 'like', "%{$beneficiary}%")
            ->orWhere('piva', 'like', "%{$beneficiary}%")
            ->orWhere('email', 'like', "%{$beneficiary}%")
            ->first();
        
        return $client ? $client->id : null;
    }
}
