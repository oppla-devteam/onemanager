<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #3b82f6; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .info-box { background: white; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; color: #6b7280; font-size: 12px; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Nuova Firma Apposta</h1>
        </div>
        <div class="content">
            <p>Ti informiamo che una nuova firma è stata apposta al contratto.</p>
            
            <div class="info-box">
                <strong>Numero Contratto:</strong> {{ $contract_number }}<br>
                <strong>Oggetto:</strong> {{ $contract_subject }}<br>
                <strong>Firmatario:</strong> {{ $signer_name }} ({{ $signer_role }})<br>
                <strong>Data firma:</strong> {{ $signed_at->format('d/m/Y H:i') }}
            </div>
            
            <p>Il processo di firma prosegue secondo il flusso stabilito.</p>
            
            <p>Puoi monitorare lo stato del contratto accedendo al sistema di gestione.</p>
        </div>
        <div class="footer">
            <p>Opplà SRL - Sistema Gestione Contratti<br>
            Questa è un'email automatica, si prega di non rispondere.</p>
        </div>
    </div>
</body>
</html>
