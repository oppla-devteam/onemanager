<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contratto {{ $contract->contract_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #3d3c82 0%, #5856a8 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            color: #3d3c82;
            margin-bottom: 20px;
        }
        .contract-info {
            background: #f8f9fa;
            border-left: 4px solid #f0c318;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .contract-info h3 {
            margin: 0 0 10px 0;
            color: #3d3c82;
        }
        .contract-info p {
            margin: 5px 0;
        }
        .contract-info strong {
            color: #3d3c82;
        }
        .message-box {
            background: #fff9e6;
            border: 1px solid #f0c318;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #f0c318 0%, #f5d449 100%);
            color: #3d3c82;
            padding: 15px 35px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(240, 195, 24, 0.4);
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        .instructions {
            background: #e8f4f8;
            border-left: 4px solid #3d3c82;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .instructions h4 {
            margin: 0 0 10px 0;
            color: #3d3c82;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .instructions li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 Nuovo Contratto</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Opplà - Flat Delivery Platform</p>
        </div>

        <div class="content">
            <p class="greeting">Gentile {{ $contract->client_name }},</p>

            <p>Le inviamo in allegato il contratto per la vostra approvazione e firma.</p>

            @if($customMessage)
            <div class="message-box">
                <strong>📌 Messaggio:</strong>
                <p style="margin: 10px 0 0 0;">{{ $customMessage }}</p>
            </div>
            @endif

            <div class="contract-info">
                <h3>📋 Dettagli Contratto</h3>
                <p><strong>Numero:</strong> {{ $contract->contract_number }}</p>
                <p><strong>Oggetto:</strong> {{ $contract->subject }}</p>
                <p><strong>Data creazione:</strong> {{ $contract->created_at->format('d/m/Y') }}</p>
                @if($contract->start_date)
                <p><strong>Data inizio:</strong> {{ $contract->start_date->format('d/m/Y') }}</p>
                @endif
                @if($contract->end_date)
                <p><strong>Data fine:</strong> {{ $contract->end_date->format('d/m/Y') }}</p>
                @endif
            </div>

            @if($signatureUrl)
            <div class="instructions">
                <h4>✍️ Come firmare il contratto:</h4>
                <ol>
                    <li>Clicca sul pulsante "Firma il Contratto" qui sotto</li>
                    <li>Leggi attentamente il documento</li>
                    <li>Richiedi il codice OTP che verrà inviato alla tua email</li>
                    <li>Inserisci il codice OTP per completare la firma</li>
                </ol>
            </div>

            <div style="text-align: center;">
                <a href="{{ $signatureUrl }}" class="button">
                    ✍️ Firma il Contratto
                </a>
            </div>
            @else
            <p><strong>ℹ️ Nota:</strong> Il contratto è allegato in formato PDF. Per qualsiasi domanda, non esitate a contattarci.</p>
            @endif

            <p style="margin-top: 30px;">Cordiali saluti,<br><strong>Il Team Opplà</strong></p>
        </div>

        <div class="footer">
            <p><strong>Opplà SRL</strong></p>
            <p>Via Example 123, 00100 Roma (RM)</p>
            <p>P.IVA: XXXXXXXXXX | Tel: +39 XX XXXX XXXX</p>
            <p>Email: info@oppla.it | Web: <a href="https://www.oppla.it">www.oppla.it</a></p>
            <p style="margin-top: 15px; font-size: 11px; color: #999;">
                Questa è una email automatica, si prega di non rispondere a questo messaggio.
            </p>
        </div>
    </div>
</body>
</html>
