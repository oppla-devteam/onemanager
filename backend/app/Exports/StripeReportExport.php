<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\DB;

class StripeReportExport implements WithMultipleSheets
{
    protected $transactions;
    protected $totals;
    protected $applicationFees;
    protected $year;
    protected $month;

    public function __construct($transactions, $totals, $applicationFees, $year, $month)
    {
        $this->transactions = $transactions;
        $this->totals = $totals;
        $this->applicationFees = $applicationFees;
        $this->year = $year;
        $this->month = $month;
    }

    public function sheets(): array
    {
        return [
            new ReportSheet($this->transactions, $this->totals, $this->year, $this->month),
            new ApplicationFeesSheet($this->applicationFees, $this->year, $this->month),
        ];
    }
}

class ReportSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected $transactions;
    protected $totals;
    protected $year;
    protected $month;

    public function __construct($transactions, $totals, $year, $month)
    {
        $this->transactions = $transactions;
        $this->totals = $totals;
        $this->year = $year;
        $this->month = $month;
    }

    public function collection()
    {
        $data = collect();

        // Aggiungi riepilogo iniziale
        $data->push(['REPORT STRIPE - ' . date('F Y', mktime(0, 0, 0, $this->month, 1, $this->year))]);
        $data->push(['']);
        $data->push(['RIEPILOGO TOTALI']);
        $data->push(['TOTALE COMMISSIONI RISCOSSE', number_format($this->totals['commissioni_riscosse'], 2, '.', '')]);
        $data->push(['TOTALE CHARGE', number_format($this->totals['total_charge'], 2, '.', '')]);
        $data->push(['TOTALE TRANSFER', number_format($this->totals['total_transfer'], 2, '.', '')]);
        $data->push(['DIFFERENZA TRA CHARGE E TRANSFER', number_format($this->totals['differenza'], 2, '.', '')]);
        $data->push(['TOTALE PAGAMENTI SOTTOSCRIZIONE', number_format($this->totals['total_payment'], 2, '.', '')]);
        $data->push(['TOTALE COMMISSIONI PAGATE', number_format($this->totals['commissioni_pagate'], 2, '.', '')]);
        $data->push(['TOTALE COUPON', number_format($this->totals['total_coupon'], 2, '.', '')]);
        $data->push(['']);
        $data->push(['']);

        // Aggiungi transazioni
        $data->push(['ID', 'Tipo', 'Source', 'Importo', 'Fee', 'Net', 'Data', 'Note']);

        foreach ($this->transactions as $t) {
            $notes = [];
            if (isset($t->auto_corrected) && $t->auto_corrected) {
                $notes[] = 'AUTO: ' . ($t->correction_reason ?? 'Corretto automaticamente');
            }
            if (isset($t->manually_corrected) && $t->manually_corrected) {
                $notes[] = 'MANUALE';
            }

            $data->push([
                $t->transaction_id ?? '',
                strtolower($t->type ?? ''), // NORMALIZZA: sempre minuscolo come negli altri report
                $t->source ?? '',
                (float) ($t->amount ?? 0),
                (float) ($t->fee ?? 0),
                (float) ($t->net ?? 0),
                $t->created_at ?? '',
                implode(' | ', $notes)
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Report Generale';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            3 => [
                'font' => ['bold' => true, 'size' => 14],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD']
                ]
            ],
            13 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'BBDEFB']
                ]
            ]
        ];
    }
}

class ApplicationFeesSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected $applicationFees;
    protected $year;
    protected $month;

    public function __construct($applicationFees, $year, $month)
    {
        $this->applicationFees = $applicationFees;
        $this->year = $year;
        $this->month = $month;
    }

    public function collection()
    {
        $data = collect();

        // Aggiungi intestazione
        $data->push(['COMMISSIONI RISCOSSE (APPLICATION FEES) - ' . date('F Y', mktime(0, 0, 0, $this->month, 1, $this->year))]);
        $data->push(['']);
        $data->push(['Data', 'ID Transazione', 'Importo', 'Ragione Sociale', 'Email Partner', 'Descrizione']);
        
        // Aggiungi dati application fees con ragione sociale
        $totalAmount = 0;
        foreach ($this->applicationFees as $fee) {
            $amount = abs((float) ($fee->amount ?? 0));
            
            // Recupera ragione sociale dalla tabella application_fees
            $partnerInfo = DB::table('application_fees')
                ->where('stripe_fee_id', $fee->transaction_id)
                ->first();
            
            $ragioneSociale = $partnerInfo->partner_name ?? 'N/D';
            $emailPartner = $partnerInfo->partner_email ?? '';
            
            $data->push([
                $fee->created_at ?? '',
                $fee->transaction_id ?? '',
                $amount,
                $ragioneSociale,
                $emailPartner,
                $fee->description ?? ''
            ]);
            
            $totalAmount += $amount;
        }

        // Aggiungi totale
        $data->push(['']);
        $data->push(['', '', 'TOTALE COMMISSIONI RISCOSSE', $totalAmount]);

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Commissioni Riscosse';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            3 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'C8E6C9']
                ]
            ]
        ];
    }
}
