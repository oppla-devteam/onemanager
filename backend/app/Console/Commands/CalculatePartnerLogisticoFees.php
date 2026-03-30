<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PartnerLogisticoFeeService;
use App\Models\Client;
use Carbon\Carbon;

class CalculatePartnerLogisticoFees extends Command
{
    protected $signature = 'partners:calculate-logistico-fees {partner_id?} {--start=} {--end=}';
    protected $description = 'Calcola fee consegne per partner logistici (chiedi dettagli a Lorenzo Moschella)';

    public function handle(PartnerLogisticoFeeService $feeService)
    {
        $partnerId = $this->argument('partner_id');
        $startDate = $this->option('start') 
            ? Carbon::parse($this->option('start'))
            : Carbon::now()->startOfMonth();
        $endDate = $this->option('end') 
            ? Carbon::parse($this->option('end'))
            : Carbon::now()->endOfMonth();

        $this->info("Calcolo fee partner logistici per periodo: {$startDate->format('Y-m-d')} - {$endDate->format('Y-m-d')}");
        $this->newLine();

        if ($partnerId) {
            // Calcola per un singolo partner
            $partner = Client::findOrFail($partnerId);
            
            if (!$partner->is_partner_logistico) {
                $this->error("Il partner {$partner->ragione_sociale} non è un partner logistico!");
                return 1;
            }

            $result = $feeService->calculateFees($partner, $startDate, $endDate);
            $this->displayResult($result);
        } else {
            // Calcola per tutti i partner logistici
            $results = $feeService->calculateAllPartnerFees($startDate, $endDate);
            
            if (empty($results)) {
                $this->warn('Nessun partner logistico attivo trovato');
                return 0;
            }

            foreach ($results as $result) {
                $this->displayResult($result);
                $this->newLine();
            }
        }

        return 0;
    }

    private function displayResult(array $result)
    {
        $this->info("📦 Partner: {$result['partner']['name']}");
        $this->line("Periodo: {$result['period']['start_date']} → {$result['period']['end_date']}");
        $this->newLine();
        
        $stats = $result['stats'];
        $this->table(
            ['Metrica', 'Valore'],
            [
                ['Consegne totali', $stats['total_deliveries']],
                ['Km totali', number_format($stats['total_distance_km'], 2) . ' km'],
                ['Km medi', number_format($stats['average_distance'], 2) . ' km'],
                ['Fee totali', '€' . number_format($stats['total_fees'], 2)],
                ['Fee media', '€' . number_format($stats['average_fee'], 2)],
            ]
        );

        if ($this->option('verbose')) {
            $this->newLine();
            $this->info('Dettaglio consegne:');
            $headers = ['ID', 'Order ID', 'Data', 'Km', 'Fee Base', 'Fee Km', 'Totale'];
            $rows = array_map(function($item) {
                return [
                    $item['delivery_id'],
                    $item['order_id'],
                    Carbon::parse($item['date'])->format('d/m/Y'),
                    number_format($item['distance_km'], 2),
                    '€' . number_format($item['fee_base'], 2),
                    '€' . number_format($item['fee_distance'], 2),
                    '€' . number_format($item['total_fee'], 2),
                ];
            }, $stats['breakdown']);
            $this->table($headers, $rows);
        }

        $this->warn($result['note']);
    }
}
