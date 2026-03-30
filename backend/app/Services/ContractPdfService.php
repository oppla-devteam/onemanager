<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;

class ContractPdfService
{
    private Dompdf $pdf;

    public function __construct()
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', public_path());
        
        $this->pdf = new Dompdf($options);
    }

    /**
     * Genera PDF da contratto
     */
    public function generatePdf(Contract $contract): string
    {
        // Usa sempre il template Blade Oppla
        $template = ContractTemplate::where('code', 'oppla-subscription-cover')->first();
        $html = $this->generateFromTemplate($contract, $template);
        
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
        
        // Salva PDF
        $filename = $this->generateFilename($contract);
        $path = "contracts/{$contract->id}/{$filename}";
        
        Storage::put($path, $this->pdf->output());
        
        // Aggiorna contratto
        $contract->update(['pdf_path' => $path]);
        
        return $path;
    }

    /**
     * Genera HTML da template Oppla (usa Blade view)
     */
    private function generateFromTemplate(Contract $contract, ContractTemplate $template): string
    {
        $data = $this->prepareTemplateData($contract);
        
        // Usa Blade template invece di placeholders manuali
        return view('contracts.oppla-piano-abbonamento', $data)->render();
    }

    /**
     * Prepara i dati del contratto per il template
     */
    private function prepareTemplateData(Contract $contract): array
    {
        $client = $contract->client;
        $terms = $contract->terms ?? [];
        
        // Estrai i servizi dall'oggetto terms
        $servizio_ritiro = $terms['servizio_ritiro'] ?? '12.00';
        $servizio_principale = $terms['servizio_principale'] ?? '2.98';
        $ordine_rifiutato = $terms['ordine_rifiutato'] ?? '1.49';
        $inserimento_manuale = $terms['inserimento_manuale'] ?? '1.49';
        $abbonamento_consegne = $terms['abbonamento_consegne'] ?? '0.00';
        
        // Estrai sito
        $site_name = $terms['site_name'] ?? ($client?->ragione_sociale ?? '');
        $site_address = $terms['site_address'] ?? ($client?->indirizzo ?? '');
        
        // MPG
        $mpg = $contract->miglior_prezzo_garantito ?? false;
        $mpg_text = $mpg ? 'aderisce' : 'NON aderisce';
        
        // Attrezzatura
        $attrezzatura = $terms['attrezzatura_fornita'] ?? false;
        $attrezzatura_text = $attrezzatura 
            ? 'Opplà fornisce attrezzatura (tablet, stampante, etc.) in comodato d\'uso.'
            : 'Il Partner utilizza propria attrezzatura.';
        
        // Firma (se presente)
        $signature = $contract->signatures()->where('status', 'signed')->first();
        $signature_image = '';
        if ($signature && $signature->signature_data) {
            $signature_image = '<img src="' . $signature->signature_data . '" style="max-width:250px;max-height:80px;border-bottom:2px solid #000;"/>';
        }
        
        return [
            'partner_ragione_sociale' => $contract->partner_ragione_sociale ?? $client?->ragione_sociale ?? '',
            'partner_piva' => $contract->partner_piva ?? $client?->piva ?? '',
            'partner_sede_legale' => $contract->partner_sede_legale ?? $client?->indirizzo ?? '',
            'partner_iban' => $contract->partner_iban ?? $client?->iban ?? '',
            'partner_legale_rappresentante' => $contract->partner_legale_rappresentante ?? '',
            'partner_email' => $contract->partner_email ?? $client?->email ?? '',
            'start_date' => $contract->start_date ? date('d/m/Y', strtotime($contract->start_date)) : '',
            'periodo_mesi' => $contract->periodo_mesi ?? '12',
            'territorio' => $contract->territorio ?? 'Italia',
            'site_name' => $site_name,
            'site_address' => $site_address,
            'costo_attivazione' => number_format($contract->costo_attivazione ?? 150, 2, ',', '.'),
            'servizio_ritiro' => number_format((float)$servizio_ritiro, 2, ',', '.'),
            'servizio_principale' => number_format((float)$servizio_principale, 2, ',', '.'),
            'ordine_rifiutato' => number_format((float)$ordine_rifiutato, 2, ',', '.'),
            'inserimento_manuale' => number_format((float)$inserimento_manuale, 2, ',', '.'),
            'abbonamento_consegne' => number_format((float)$abbonamento_consegne, 2, ',', '.'),
            'miglior_prezzo_garantito_text' => $mpg_text,
            'attrezzatura_fornita_text' => $attrezzatura_text,
            'signature_image' => $signature_image,
            'signature_date' => $signature ? $signature->signed_at->format('d/m/Y') : '',
        ];
    }

    /**
     * Genera PDF firmato con signature overlay
     */
    public function generateSignedPdf(Contract $contract): string
    {
        if (!$contract->isFullySigned()) {
            throw new \Exception('Il contratto non è completamente firmato');
        }

        $html = $this->buildSignedHtml($contract);
        
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
        
        // Salva PDF firmato
        $filename = $this->generateFilename($contract, true);
        $path = "contracts/{$contract->id}/signed/{$filename}";
        
        Storage::put($path, $this->pdf->output());
        
        // Aggiorna contratto
        $contract->update([
            'signed_pdf_path' => $path,
            'signed_at' => now(),
        ]);
        
        return $path;
    }

    /**
     * Costruisce HTML del contratto
     */
    private function buildHtml(Contract $contract): string
    {
        $template = $contract->template;
        
        // Compila template con i dati
        $compiledHtml = $template->compile($contract->contract_data);
        
        // Aggiungi header e footer
        return $this->wrapWithLayout($compiledHtml, $contract);
    }

    /**
     * Costruisce HTML del contratto firmato
     */
    private function buildSignedHtml(Contract $contract): string
    {
        $html = $this->buildHtml($contract);
        
        // Aggiungi sezione firme
        $signaturesHtml = $this->buildSignaturesSection($contract);
        
        // Inserisci firme prima del footer
        $html = str_replace('</body>', $signaturesHtml . '</body>', $html);
        
        return $html;
    }

    /**
     * Costruisce sezione firme
     */
    private function buildSignaturesSection(Contract $contract): string
    {
        $signatures = $contract->signatures()->where('status', 'signed')->get();
        
        $html = '<div class="signatures-section" style="margin-top: 40px; page-break-inside: avoid;">';
        $html .= '<h3 style="border-bottom: 2px solid #333; padding-bottom: 10px;">Firme</h3>';
        
        foreach ($signatures as $signature) {
            $html .= '<div class="signature-block" style="margin: 20px 0; border: 1px solid #ddd; padding: 15px;">';
            $html .= '<div style="margin-bottom: 10px;"><strong>Firmatario:</strong> ' . e($signature->signer_name) . '</div>';
            $html .= '<div style="margin-bottom: 10px;"><strong>Ruolo:</strong> ' . e($signature->signer_role) . '</div>';
            $html .= '<div style="margin-bottom: 10px;"><strong>Data firma:</strong> ' . $signature->signed_at->format('d/m/Y H:i') . '</div>';
            
            // Se c'è una firma grafica, inseriscila
            if ($signature->signature_type === 'drawn' && $signature->signature_data) {
                $html .= '<div style="margin-top: 15px;">';
                $html .= '<div><strong>Firma:</strong></div>';
                $html .= '<img src="' . $signature->signature_data . '" style="max-width: 200px; border-bottom: 1px solid #000;" />';
                $html .= '</div>';
            }
            
            $html .= '<div style="margin-top: 10px; font-size: 10px; color: #666;">';
            $html .= 'IP: ' . e($signature->ip_address) . ' | ';
            $html .= 'Verificato: ' . ($signature->otp_sent_at ? 'Sì (OTP)' : 'No');
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Wrappa contenuto con layout
     */
    private function wrapWithLayout(string $content, Contract $contract): string
    {
        $logoPath = public_path('images/oppla-logo.svg');
        $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
        
        $phone = $contract->client_phone ? "<br><strong>Telefono:</strong> {$contract->client_phone}" : "";
        $vat = $contract->client_vat_number ? "<br><strong>P.IVA:</strong> {$contract->client_vat_number}" : "";
        $fiscalCode = $contract->client_fiscal_code ? "<br><strong>Codice Fiscale:</strong> {$contract->client_fiscal_code}" : "";
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Contratto {$contract->contract_number}</title>
            <style>
                @page {
                    margin: 2cm;
                }
                body {
                    font-family: 'DejaVu Sans', sans-serif;
                    font-size: 11pt;
                    line-height: 1.6;
                    color: #333;
                }
                h1, h2, h3 {
                    color: #3d3c82;
                }
                h1 {
                    font-size: 18pt;
                    border-bottom: 3px solid #3d3c82;
                    padding-bottom: 10px;
                    margin-bottom: 20px;
                }
                h2 {
                    font-size: 14pt;
                    margin-top: 20px;
                    margin-bottom: 10px;
                }
                h3 {
                    font-size: 12pt;
                }
                .header {
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #3d3c82;
                }
                .header-content {
                    display: table;
                    width: 100%;
                }
                .logo-cell {
                    display: table-cell;
                    width: 200px;
                    vertical-align: middle;
                }
                .header-text {
                    display: table-cell;
                    text-align: right;
                    vertical-align: middle;
                }
                .logo {
                    max-width: 180px;
                    height: auto;
                }
                .footer {
                    margin-top: 50px;
                    padding-top: 20px;
                    border-top: 1px solid #ccc;
                    font-size: 9pt;
                    color: #666;
                    text-align: center;
                }
                .contract-info {
                    background: #f8f9fa;
                    padding: 15px;
                    margin: 20px 0;
                    border-left: 4px solid #f0c318;
                    border-radius: 4px;
                }
                .contract-info strong {
                    color: #3d3c82;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #3d3c82;
                    color: white;
                    font-weight: bold;
                }
                .signature-line {
                    margin-top: 60px;
                    border-top: 1px solid #000;
                    padding-top: 5px;
                    width: 200px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="header-content">
                    <div class="logo-cell">
                        <img src="data:image/svg+xml;base64,{$logoData}" alt="Opplà Logo" class="logo" />
                    </div>
                    <div class="header-text">
                        <h1 style="margin: 0;">CONTRATTO</h1>
                        <div style="font-size: 10pt; margin-top: 10px;">
                            <strong>N° {$contract->contract_number}</strong><br>
                            Data: {$contract->created_at->format('d/m/Y')}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="contract-info">
                <strong>Oggetto:</strong> {$contract->subject}<br>
                <strong>Cliente:</strong> {$contract->client_name}<br>
                <strong>Email:</strong> {$contract->client_email}
                {$phone}
                {$vat}
                {$fiscalCode}
            </div>
            
            <div class="content">
                {$content}
            </div>
            
            <div class="footer">
                <div style="margin-bottom: 10px;">
                    <strong style="color: #3d3c82;">Opplà SRL</strong><br>
                    Via Example 123, 00100 Roma (RM)<br>
                    P.IVA: XXXXXXXXXX | Tel: +39 XX XXXX XXXX<br>
                    Email: info@oppla.it | Web: www.oppla.it
                </div>
                <div style="font-size: 8pt; color: #999;">
                    Documento generato elettronicamente il {$contract->created_at->format('d/m/Y H:i')}
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Genera nome file
     */
    private function generateFilename(Contract $contract, bool $signed = false): string
    {
        $prefix = $signed ? 'signed_' : '';
        $timestamp = now()->format('YmdHis');
        
        return "{$prefix}contract_{$contract->contract_number}_{$timestamp}.pdf";
    }

    /**
     * Download PDF
     */
    public function downloadPdf(string $path): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if (!Storage::exists($path)) {
            throw new \Exception('File non trovato');
        }
        
        return Storage::download($path);
    }

    /**
     * Visualizza PDF inline nel browser
     */
    public function streamPdf(string $path): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!Storage::exists($path)) {
            throw new \Exception('File non trovato');
        }
        
        return Storage::response($path, null, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
        ]);
    }
}
