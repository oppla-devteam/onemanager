<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            background-color: #1976d2;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .totals {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .totals td:first-child {
            font-weight: bold;
        }
        .totals td:last-child {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #1976d2;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 10px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Report Stripe - {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</h1>
    </div>
    
    <div class="content">
        <h2>Gentile Commercialista,</h2>
        <p>In allegato trova il report Stripe per il mese di <strong>{{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</strong>.</p>
        
        <div class="totals">
            <h3>Riepilogo Totali</h3>
            <table>
                <tr>
                    <td>Totale Commissioni Riscosse:</td>
                    <td>€ {{ number_format($totals['commissioni_riscosse'], 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Totale Charge:</td>
                    <td>€ {{ number_format($totals['total_charge'], 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Totale Transfer:</td>
                    <td>€ {{ number_format($totals['total_transfer'], 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Differenza Charge/Transfer:</td>
                    <td>€ {{ number_format($totals['differenza'], 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Totale Pagamenti Sottoscrizione:</td>
                    <td>€ {{ number_format($totals['total_payment'], 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Totale Commissioni Pagate:</td>
                    <td>€ {{ number_format($totals['commissioni_pagate'], 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Totale Coupon:</td>
                    <td>€ {{ number_format($totals['total_coupon'], 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        @if(abs($totals['differenza']) > 0.01)
        <div class="highlight">
            <strong>Attenzione:</strong> È presente una differenza tra Charge e Transfer di € {{ number_format(abs($totals['differenza']), 2, ',', '.') }}.
            Il sistema ha normalizzato automaticamente le transazioni dove possibile.
        </div>
        @endif

        <p>Il file Excel allegato contiene due fogli:</p>
        <ul>
            <li><strong>Report Generale:</strong> Dettaglio completo di tutte le transazioni del mese</li>
            <li><strong>Fee Ristoranti:</strong> Riepilogo delle commissioni riscosse per ogni ristorante</li>
        </ul>

        <p>Per qualsiasi chiarimento, non esiti a contattarci.</p>
        
        <p>Cordiali saluti,<br>
        <strong>OPPLA One Manager</strong></p>
    </div>

    <div class="footer">
        <p>Questo messaggio è stato generato automaticamente da OPPLA One Manager</p>
        <p>{{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
