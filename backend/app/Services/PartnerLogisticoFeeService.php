<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Delivery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service per calcolo fee consegne partner logistici
 * Chiedi dettagli a Lorenzo Moschella
 */
class PartnerLogisticoFeeService
{
    /**
     * Calcola le fee per un partner logistico in un periodo
     * 
     * @param Client $partner Partner logistico
     * @param Carbon $startDate Data inizio periodo
     * @param Carbon $endDate Data fine periodo
     * @return array Dettagli calcolo fee
     */
    public function calculateFees(Client $partner, Carbon $startDate, Carbon $endDate): array
    {
        // Verifica che sia un partner logistico
        if (!$partner->is_partner_logistico) {
            throw new \Exception("Il cliente {$partner->ragione_sociale} non è un partner logistico");
        }

        // Carica tutte le consegne del partner nel periodo
        $deliveries = Delivery::where('client_id', $partner->id)
            ->where('is_partner_logistico', true)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->get();

        // Raggruppa per tipo di servizio
        $stats = [
            'total_deliveries' => $deliveries->count(),
            'total_distance_km' => $deliveries->sum('distance_km'),
            'total_fees' => 0,
            'breakdown' => [],
        ];

        // Calcolo fee per distanza (esempio - da confermare con Lorenzo)
        // Fee base + fee per km
        $feeBase = 2.50; // €2.50 fee base
        $feePerKm = 0.80; // €0.80 per km

        foreach ($deliveries as $delivery) {
            $fee = $feeBase + ($delivery->distance_km * $feePerKm);
            
            $stats['breakdown'][] = [
                'delivery_id' => $delivery->id,
                'order_id' => $delivery->order_id,
                'date' => $delivery->order_date,
                'distance_km' => $delivery->distance_km,
                'fee_base' => $feeBase,
                'fee_distance' => $delivery->distance_km * $feePerKm,
                'total_fee' => $fee,
            ];

            $stats['total_fees'] += $fee;
        }

        $stats['average_fee'] = $stats['total_deliveries'] > 0 
            ? $stats['total_fees'] / $stats['total_deliveries'] 
            : 0;

        $stats['average_distance'] = $stats['total_deliveries'] > 0 
            ? $stats['total_distance_km'] / $stats['total_deliveries'] 
            : 0;

        return [
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->ragione_sociale,
            ],
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'stats' => $stats,
            'note' => 'Calcolo fee partner logistico - Verificare tariffe con Lorenzo Moschella',
        ];
    }

    /**
     * Calcola fee per tutti i partner logistici attivi
     */
    public function calculateAllPartnerFees(Carbon $startDate, Carbon $endDate): array
    {
        $partners = Client::where('is_partner_logistico', true)
            ->where('is_active', true)
            ->get();

        $results = [];
        foreach ($partners as $partner) {
            try {
                $results[] = $this->calculateFees($partner, $startDate, $endDate);
            } catch (\Exception $e) {
                \Log::error("Errore calcolo fee partner logistico", [
                    'partner_id' => $partner->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Genera fattura per consegne partner logistico
     */
    public function generateInvoiceForPeriod(Client $partner, Carbon $startDate, Carbon $endDate)
    {
        $calculation = $this->calculateFees($partner, $startDate, $endDate);

        // TODO: Implementare creazione fattura con InvoicingService
        // Per ora ritorna solo il calcolo
        
        \Log::info('Generazione fattura partner logistico', [
            'partner_id' => $partner->id,
            'period' => "{$startDate->format('Y-m')} - {$endDate->format('Y-m')}",
            'total_fees' => $calculation['stats']['total_fees'],
            'deliveries_count' => $calculation['stats']['total_deliveries'],
        ]);

        return $calculation;
    }
}
