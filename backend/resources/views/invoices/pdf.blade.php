<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Fattura {{ $invoice->numero_fattura }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; }
        .container { padding: 40px; }
        
        /* Watermark Copia Cortesia */
        @if($isCopyCortesia ?? false)
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80pt;
            color: rgba(200, 200, 200, 0.3);
            font-weight: bold;
            z-index: -1;
            white-space: nowrap;
        }
        @endif
        
        /* Header */
        .header { margin-bottom: 40px; border-bottom: 2px solid #0ea5e9; padding-bottom: 20px; }
        .header-left { float: left; width: 50%; }
        .header-right { float: right; width: 45%; text-align: right; }
        .clearfix::after { content: ""; display: table; clear: both; }
        .company-name { font-size: 18pt; font-weight: bold; color: #0ea5e9; margin-bottom: 5px; }
        .company-info { font-size: 9pt; line-height: 1.5; }
        
        /* Invoice Info */
        .invoice-info { margin-bottom: 30px; background: #f8fafc; padding: 15px; border-radius: 5px; }
        .invoice-number { font-size: 20pt; font-weight: bold; color: #0ea5e9; }
        .invoice-date { font-size: 10pt; color: #666; }
        
        /* Client Info */
        .client-box { margin-bottom: 30px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 5px; }
        .client-title { font-weight: bold; margin-bottom: 10px; color: #0ea5e9; }
        .client-info { font-size: 10pt; line-height: 1.6; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #0ea5e9; color: white; padding: 12px 8px; text-align: left; font-weight: bold; }
        td { padding: 10px 8px; border-bottom: 1px solid #e2e8f0; }
        tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        /* Totals */
        .totals { float: right; width: 300px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .total-row.grand { font-size: 14pt; font-weight: bold; background: #f1f5f9; padding: 12px; margin-top: 10px; border-radius: 5px; }
        
        /* Footer */
        .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 9pt; color: #666; text-align: center; }
    </style>
</head>
<body>
    @if($isCopyCortesia ?? false)
    <div class="watermark">COPIA CORTESIA</div>
    @endif
    
    <div class="container">
        <!-- Header -->
        <div class="header clearfix">
            <div class="header-left">
                <div class="company-name">{{ $companyName }}</div>
                <div class="company-info">
                    {{ $companyAddress }}<br>
                    P.IVA: {{ $companyVat }}
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-info">
                    <div class="invoice-number">Fattura {{ $invoice->numero_fattura }}</div>
                    <div class="invoice-date">Data: {{ \Carbon\Carbon::parse($invoice->data_emissione)->format('d/m/Y') }}</div>
                    @if($invoice->type === 'differita')
                    <div class="invoice-date" style="color: #f59e0b; font-weight: bold;">FATTURA DIFFERITA</div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Client Info -->
        <div class="client-box">
            <div class="client-title">DESTINATARIO</div>
            <div class="client-info">
                <strong>{{ $client->ragione_sociale ?? 'Cliente' }}</strong><br>
                @if($client->indirizzo){{ $client->indirizzo }}<br>@endif
                @if($client->citta){{ $client->cap ?? '' }} {{ $client->citta }} ({{ $client->provincia ?? '' }})<br>@endif
                @if($client->partita_iva)P.IVA: {{ $client->partita_iva }}<br>@endif
                @if($client->codice_fiscale)C.F.: {{ $client->codice_fiscale }}<br>@endif
                @if($client->email)Email: {{ $client->email }}@endif
            </div>
        </div>
        
        <!-- Invoice Items -->
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Descrizione</th>
                    <th class="text-center" style="width: 10%;">Qta</th>
                    <th class="text-right" style="width: 15%;">Prezzo Unitario</th>
                    <th class="text-right" style="width: 10%;">IVA</th>
                    <th class="text-right" style="width: 15%;">Totale</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items ?? [] as $item)
                <tr>
                    <td>{{ $item->descrizione ?? 'Servizio' }}</td>
                    <td class="text-center">{{ $item->quantita ?? 1 }}</td>
                    <td class="text-right">€ {{ number_format($item->prezzo_unitario ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">{{ $item->iva_percentuale ?? 22 }}%</td>
                    <td class="text-right">€ {{ number_format($item->importo_totale ?? 0, 2, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td>Servizi</td>
                    <td class="text-center">1</td>
                    <td class="text-right">€ {{ number_format($invoice->importo_imponibile, 2, ',', '.') }}</td>
                    <td class="text-right">22%</td>
                    <td class="text-right">€ {{ number_format($invoice->importo_totale, 2, ',', '.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="totals">
            <div class="total-row">
                <span>Imponibile:</span>
                <span>€ {{ number_format($invoice->importo_imponibile, 2, ',', '.') }}</span>
            </div>
            <div class="total-row">
                <span>IVA (22%):</span>
                <span>€ {{ number_format($invoice->importo_iva, 2, ',', '.') }}</span>
            </div>
            <div class="total-row grand">
                <span>TOTALE:</span>
                <span>€ {{ number_format($invoice->importo_totale, 2, ',', '.') }}</span>
            </div>
        </div>
        <div class="clearfix"></div>
        
        <!-- Footer -->
        <div class="footer">
            @if($isCopyCortesia ?? false)
            <p><strong>COPIA CORTESIA - NON VALIDA AI FINI FISCALI</strong></p>
            <p>Questo documento non ha valore legale. Per la fattura ufficiale, consultare il Sistema di Interscambio (SDI).</p>
            @endif
            <p>Documento generato il {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
