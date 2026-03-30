<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appuntamento Confermato</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #0ea5e9;
            margin: 0;
            font-size: 28px;
        }
        .emoji {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 30px;
        }
        .appointment-box {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            color: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            margin: 20px 0;
        }
        .appointment-box .date {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .appointment-box .time {
            font-size: 32px;
            font-weight: bold;
        }
        .appointment-box .label {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        .notes {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 15px;
            margin: 20px 0;
        }
        .checklist {
            background: #f0fdf4;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .checklist h3 {
            color: #22c55e;
            margin-top: 0;
        }
        .checklist ul {
            margin: 0;
            padding-left: 20px;
        }
        .checklist li {
            margin-bottom: 8px;
        }
        .cta {
            text-align: center;
            margin: 30px 0;
        }
        .cta a {
            display: inline-block;
            background: #6b7280;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 0 5px;
        }
        .cta a.primary {
            background: #0ea5e9;
        }
        .cta a:hover {
            opacity: 0.9;
        }
        .contact {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="emoji">📅</div>
            <h1>Appuntamento Confermato!</h1>
        </div>

        <div class="content">
            <p>Ciao <strong>{{ $clientName }}</strong>,</p>

            <p>Il tuo appuntamento con il team OPPLA è stato confermato. Ecco i dettagli:</p>

            <div class="appointment-box">
                <div class="label">Data</div>
                <div class="date">{{ $appointmentDate }}</div>
                <div class="label">Orario</div>
                <div class="time">{{ $appointmentTime }}</div>
            </div>

            @if($notes)
            <div class="notes">
                <strong>📝 Note aggiuntive:</strong>
                <p>{{ $notes }}</p>
            </div>
            @endif

            <div class="checklist">
                <h3>✅ Cosa preparare per l'appuntamento</h3>
                <ul>
                    <li>Documenti aziendali (P.IVA, Codice Fiscale)</li>
                    <li>Dati bancari (IBAN per i pagamenti)</li>
                    <li>Logo del ristorante in alta qualità</li>
                    <li>Foto di copertina del locale</li>
                    <li>Menu aggiornato (se disponibile in formato digitale)</li>
                </ul>
            </div>

            <div class="cta">
                <a href="{{ $rescheduleUrl }}" class="secondary">Riprogramma</a>
            </div>

            <div class="contact">
                <strong>⚠️ Hai bisogno di riprogrammare?</strong>
                <p>Se non riesci a partecipare all'appuntamento, contattaci il prima possibile:</p>
                <p>
                    Email: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a><br>
                    Telefono: {{ $supportPhone }}
                </p>
            </div>
        </div>

        <div class="footer">
            <p>Questo messaggio è stato inviato automaticamente da OPPLA One Manager.</p>
            <p>© {{ date('Y') }} OPPLA - Tutti i diritti riservati</p>
        </div>
    </div>
</body>
</html>
