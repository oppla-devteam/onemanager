<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #10b981; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .success-icon { font-size: 64px; text-align: center; margin: 20px 0; }
        .info-box { background: white; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; color: #6b7280; font-size: 12px; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Contratto Completamente Firmato</h1>
        </div>
        <div class="content">
            <div class="success-icon">🎉</div>
            
            <p>Buone notizie!</p>
            
            <p>Il contratto è stato firmato da tutte le parti e è ora <strong>ufficialmente valido</strong>.</p>
            
            <div class="info-box">
                <strong>Numero Contratto:</strong> {{ $contract_number }}<br>
                <strong>Oggetto:</strong> {{ $contract_subject }}<br>
                <strong>Data firma completamento:</strong> {{ $signed_at->format('d/m/Y H:i') }}
            </div>
            
            <p><strong>Prossimi passi:</strong></p>
            <ul>
                <li>Il contratto firmato è stato archiviato nel sistema</li>
                <li>Tutti i firmatari riceveranno una copia del documento firmato</li>
                <li>Il contratto è ora attivo e vincolante</li>
            </ul>
            
            <p>Puoi scaricare una copia del contratto firmato accedendo al sistema di gestione contratti.</p>
            
            <p>Grazie per aver utilizzato il nostro sistema di firma elettronica!</p>
        </div>
        <div class="footer">
            <p>Opplà SRL - Sistema Gestione Contratti<br>
            Questa è un'email automatica, si prega di non rispondere.</p>
        </div>
    </div>
</body>
</html>
