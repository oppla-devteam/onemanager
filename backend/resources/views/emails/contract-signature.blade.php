<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contratto Opplà - Richiesta Firma</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #3d3c82;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 200px;
            height: auto;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .contract-info {
            background: white;
            padding: 20px;
            border-left: 4px solid #f0c318;
            margin: 20px 0;
        }
        .contract-info h3 {
            margin-top: 0;
            color: #3d3c82;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .cta-button {
            display: inline-block;
            background: #3d3c82;
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .cta-button:hover {
            background: #2d2c62;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #f0c318;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="https://info.oppla.delivery/logo/Oppla_logo_nuovo.png" alt="Opplà Logo">
        <h1 style="color: #3d3c82; margin: 10px 0;">Contratto da Firmare</h1>
    </div>

    <div class="content">
        <p>Gentile <strong>{{ $contract->partner_legale_rappresentante ?? $contract->client->ragione_sociale }}</strong>,</p>
        
        <p>Ti inviamo il contratto Opplà per la revisione e la firma digitale.</p>

        <div class="contract-info">
            <h3>📄 Dettagli Contratto</h3>
            <div class="info-row">
                <span class="info-label">Numero:</span>
                <span>{{ $contract->contract_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Titolo:</span>
                <span>{{ $contract->title }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Partner:</span>
                <span>{{ $contract->partner_ragione_sociale }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Data Inizio:</span>
                <span>{{ date('d/m/Y', strtotime($contract->start_date)) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Data Fine:</span>
                <span>{{ date('d/m/Y', strtotime($contract->end_date)) }}</span>
            </div>
            @if($contract->value)
            <div class="info-row">
                <span class="info-label">Valore:</span>
                <span>€ {{ number_format($contract->value, 2, ',', '.') }}</span>
            </div>
            @endif
        </div>

        <div style="text-align: center;">
            <a href="{{ $signatureUrl }}" class="cta-button">
                ✍️ Firma il Contratto
            </a>
        </div>

        <div class="warning">
            <strong>⏰ Importante:</strong> Il link per la firma è valido per 30 giorni. 
            Ti preghiamo di rivedere il documento allegato e procedere con la firma digitale.
        </div>

        <p>Il documento PDF completo è allegato a questa email. Puoi scaricarlo, leggerlo attentamente e poi cliccare sul pulsante sopra per procedere con la firma digitale.</p>

        <p><strong>Cosa fare:</strong></p>
        <ol>
            <li>Scarica e leggi attentamente il contratto allegato</li>
            <li>Clicca sul pulsante "Firma il Contratto"</li>
            <li>Completa il processo di firma digitale</li>
        </ol>

        <p>Se hai domande o necessiti di chiarimenti sul contratto, non esitare a contattarci.</p>

        <p>Cordiali saluti,<br>
        <strong>Il Team Opplà</strong></p>
    </div>

    <div class="footer">
        <p>
            <strong>Opplà SRL</strong><br>
            Via Cairoli, 21 – 57123 Livorno (LI)<br>
            P.IVA: IT02027300496<br>
            Email: info@oppla.delivery | Tel: +39 0586 123456
        </p>
        <p style="color: #999; font-size: 11px;">
            Questa email è stata inviata automaticamente dal sistema Opplà Manager.<br>
            Per favore non rispondere direttamente a questa email.
        </p>
    </div>
</body>
</html>
