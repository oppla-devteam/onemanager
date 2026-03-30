<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sollecito Pagamento Fattura</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }
        .invoice-details {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #4F46E5;
            border-radius: 4px;
        }
        .invoice-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-details td {
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .invoice-details td:first-child {
            font-weight: bold;
            color: #6b7280;
            width: 40%;
        }
        .alert {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-warning {
            background-color: #fffbeb;
            border-left-color: #f59e0b;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4F46E5;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sollecito Pagamento Fattura</h1>
    </div>

    <div class="content">
        <p>Gentile <strong>{{ $client->ragione_sociale }}</strong>,</p>

        <div class="alert {{ $daysLate > 30 ? '' : 'alert-warning' }}">
            <strong>⚠️ Fattura Scaduta</strong><br>
            La fattura indicata di seguito risulta <strong>scaduta da {{ $daysLate }} giorni</strong>.
        </div>

        <p>Le ricordiamo che il pagamento della seguente fattura è ancora in sospeso:</p>

        <div class="invoice-details">
            <table>
                <tr>
                    <td>Numero Fattura:</td>
                    <td><strong>{{ $invoice->numero_fattura }}</strong></td>
                </tr>
                <tr>
                    <td>Data Emissione:</td>
                    <td>{{ $invoice->data_emissione->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>Data Scadenza:</td>
                    <td><strong style="color: #ef4444;">{{ $dueDate }}</strong></td>
                </tr>
                <tr>
                    <td>Importo Totale:</td>
                    <td><strong style="font-size: 1.2em; color: #4F46E5;">€ {{ $totalAmount }}</strong></td>
                </tr>
            </table>
        </div>

        <p>La invitiamo a procedere quanto prima al saldo della fattura.</p>

        @if($invoice->payment_method)
        <p><strong>Metodo di Pagamento Previsto:</strong> {{ $invoice->payment_method }}</p>
        @endif

        <p>In caso di pagamento già effettuato, La preghiamo di inviarci la relativa conferma.</p>

        <p>Per qualsiasi chiarimento rimaniamo a Sua disposizione.</p>

        <div class="footer">
            <p><strong>OPPLA</strong></p>
            <p>Questa è una email automatica, si prega di non rispondere.</p>
            <p>Per informazioni contattare: amministrazione@oppla.it</p>
        </div>
    </div>
</body>
</html>
