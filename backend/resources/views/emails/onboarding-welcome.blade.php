<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benvenuto in OPPLA!</title>
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
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        .emoji {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 30px;
        }
        .steps {
            background: #f0f9ff;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .steps h3 {
            color: #0ea5e9;
            margin-top: 0;
        }
        .steps ul {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 10px;
        }
        .cta {
            text-align: center;
            margin: 30px 0;
        }
        .cta a {
            display: inline-block;
            background: #0ea5e9;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }
        .cta a:hover {
            background: #0284c7;
        }
        .help {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
        .contact {
            background: #f0fdf4;
            border-left: 4px solid #22c55e;
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
            <div class="emoji">🎉</div>
            <h1>Benvenuto in OPPLA!</h1>
        </div>

        <div class="content">
            <p>Ciao <strong>{{ $clientName }}</strong>,</p>

            <p>Siamo entusiasti di averti a bordo! Il tuo percorso di onboarding con OPPLA è ufficialmente iniziato.</p>

            <div class="steps">
                <h3>📋 I prossimi passi</h3>
                <ul>
                    <li><strong>Configura il tuo profilo</strong> - Completa i dati del titolare e dell'azienda</li>
                    <li><strong>Aggiungi i tuoi ristoranti</strong> - Inserisci le informazioni dei punti vendita</li>
                    <li><strong>Carica le immagini</strong> - Logo e foto di copertina per i tuoi ristoranti</li>
                    <li><strong>Imposta le zone di consegna</strong> - Definisci le aree che vuoi servire</li>
                    <li><strong>Scegli le tariffe</strong> - Configura i costi di consegna</li>
                </ul>
            </div>

            <div class="cta">
                <a href="{{ config('app.url') }}/onboarding">Continua l'Onboarding</a>
            </div>

            <div class="help">
                <strong>💡 Hai bisogno di aiuto?</strong>
                <p>Il nostro team è qui per supportarti in ogni fase del processo. Non esitare a contattarci!</p>
            </div>

            <div class="contact">
                <strong>📞 Contatti Supporto</strong>
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
