<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .button { display: inline-block; background: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .info-box { background: white; border-left: 4px solid #4F46E5; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; color: #6b7280; font-size: 12px; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Richiesta Firma Contratto</h1>
        </div>
        <div class="content">
            <p>Gentile <strong>{{ $signer_name }}</strong>,</p>
            
            <p>È stato richiesto di firmare il seguente contratto:</p>
            
            <div class="info-box">
                <strong>Numero Contratto:</strong> {{ $contract_number }}<br>
                <strong>Oggetto:</strong> {{ $contract_subject }}<br>
                <strong>Cliente:</strong> {{ $client_name }}
            </div>
            
            <p>Per visualizzare e firmare il contratto, cliccare sul pulsante sottostante:</p>
            
            <div style="text-align: center;">
                <a href="{{ $signature_url }}" class="button">VISUALIZZA E FIRMA CONTRATTO</a>
            </div>
            
            <p><strong>Processo di firma:</strong></p>
            <ol>
                <li>Clicca sul link sopra per accedere al contratto</li>
                <li>Leggi attentamente il documento</li>
                <li>Richiedi il codice OTP che verrà inviato via email</li>
                <li>Inserisci il codice OTP e apponi la firma</li>
            </ol>
            
            <p style="color: #dc2626; font-size: 14px;">
                ⚠️ <strong>Attenzione:</strong> Questo link scade il {{ $expires_at->format('d/m/Y H:i') }}.
            </p>
            
            <p>Se non hai richiesto questa firma, ignora questa email.</p>
        </div>
        <div class="footer">
            <p>Opplà SRL - Sistema Gestione Contratti<br>
            Questa è un'email automatica, si prega di non rispondere.</p>
        </div>
    </div>
</body>
</html>
