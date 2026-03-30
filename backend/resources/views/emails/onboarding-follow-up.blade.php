<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Follow-up Onboarding OPPLA</title>
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
        .help {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
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
            <div class="emoji">🚀</div>
            <h1>Come sta andando?</h1>
        </div>

        <div class="content">
            <p>Ciao <strong>{{ $clientName }}</strong>,</p>

            <p>È passato un giorno dal completamento del tuo onboarding con OPPLA e volevamo sapere come sta andando!</p>

            <p>Speriamo che tu stia trovando utile la piattaforma e che tutto funzioni al meglio per le tue esigenze di delivery.</p>

            <div class="help">
                <strong>💡 Hai bisogno di aiuto?</strong><br>
                Il nostro team è sempre disponibile per supportarti. Non esitare a contattarci per:
                <ul>
                    <li>Domande sulla piattaforma</li>
                    <li>Configurazione avanzata</li>
                    <li>Ottimizzazione dei processi</li>
                    <li>Assistenza tecnica</li>
                </ul>
            </div>

            <p>Alcuni consigli per sfruttare al massimo OPPLA:</p>
            <ul>
                <li>📊 Controlla regolarmente il <strong>Dashboard</strong> per monitorare le performance</li>
                <li>📧 Configura le <strong>notifiche email</strong> per rimanere sempre aggiornato</li>
                <li>🚚 Sfrutta il <strong>tracking in tempo reale</strong> per le consegne</li>
                <li>💰 Rivedi periodicamente i <strong>report mensili</strong> per ottimizzare i costi</li>
            </ul>
        </div>

        <div class="cta">
            <a href="{{ config('app.frontend_url') }}/dashboard">Accedi alla Dashboard</a>
        </div>

        <div class="content">
            <p>Se hai <strong>domande, suggerimenti o feedback</strong>, rispondi pure a questa email - saremo felici di aiutarti!</p>

            <p>Un caro saluto,<br>
            <strong>Il Team OPPLA</strong> 👋</p>
        </div>

        <div class="footer">
            <p>Questo è un messaggio automatico inviato dopo il completamento dell'onboarding.</p>
            <p>OPPLA One Manager - Sistema di Gestione Partner</p>
        </div>
    </div>
</body>
</html>
