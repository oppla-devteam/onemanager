<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #059669; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .otp-code { background: white; border: 2px dashed #059669; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 8px; margin: 20px 0; font-family: 'Courier New', monospace; }
        .footer { text-align: center; color: #6b7280; font-size: 12px; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Codice Verifica Firma</h1>
        </div>
        <div class="content">
            <p>Gentile <strong>{{ $signer_name }}</strong>,</p>
            
            <p>È stato richiesto un codice OTP per firmare il contratto <strong>{{ $contract_number }}</strong>.</p>
            
            <p>Il tuo codice di verifica è:</p>
            
            <div class="otp-code">
                {{ $otp }}
            </div>
            
            <p style="text-align: center; color: #dc2626; font-weight: bold;">
                ⏱️ Questo codice scade il {{ $expires_at->format('d/m/Y H:i') }}
            </p>
            
            <p><strong>Come utilizzare il codice:</strong></p>
            <ol>
                <li>Torna alla pagina di firma del contratto</li>
                <li>Inserisci questo codice nell'apposito campo</li>
                <li>Completa la firma</li>
            </ol>
            
            <p style="color: #dc2626; font-size: 14px;">
                ⚠️ <strong>Sicurezza:</strong> Non condividere questo codice con nessuno. Se non hai richiesto questo codice, ignora questa email.
            </p>
        </div>
        <div class="footer">
            <p>Opplà SRL - Sistema Gestione Contratti<br>
            Questa è un'email automatica, si prega di non rispondere.</p>
        </div>
    </div>
</body>
</html>
