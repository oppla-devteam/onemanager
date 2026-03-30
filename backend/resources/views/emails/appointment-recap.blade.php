<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riepilogo Appuntamento</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px 16px;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .header {
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
            color: #ffffff;
            padding: 32px 28px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 24px;
            font-weight: 700;
        }
        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 15px;
        }
        .content {
            padding: 28px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 24px 0 12px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }
        .fee-table {
            width: 100%;
            border-collapse: collapse;
        }
        .fee-table tr {
            border-bottom: 1px solid #f1f5f9;
        }
        .fee-table tr:last-child {
            border-bottom: none;
        }
        .fee-table td {
            padding: 12px 0;
            font-size: 14px;
        }
        .fee-label {
            color: #475569;
        }
        .fee-value {
            text-align: right;
            font-weight: 700;
            color: #0f172a;
            font-size: 16px;
        }
        .services-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .services-list li {
            padding: 8px 0;
            font-size: 14px;
            color: #475569;
            border-bottom: 1px solid #f1f5f9;
        }
        .services-list li:last-child {
            border-bottom: none;
        }
        .check {
            color: #22c55e;
            font-weight: bold;
            margin-right: 8px;
        }
        .cross {
            color: #cbd5e1;
            font-weight: bold;
            margin-right: 8px;
        }
        .notes-box {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 16px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
            font-size: 14px;
            color: #334155;
        }
        .next-steps {
            background: #f0fdf4;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .next-steps h3 {
            color: #16a34a;
            margin: 0 0 12px 0;
            font-size: 15px;
        }
        .next-steps ol {
            margin: 0;
            padding-left: 20px;
            color: #475569;
            font-size: 14px;
        }
        .next-steps li {
            margin-bottom: 8px;
        }
        .contact-box {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
            font-size: 14px;
        }
        .contact-box a {
            color: #0ea5e9;
            text-decoration: none;
        }
        .footer {
            text-align: center;
            padding: 20px 28px;
            color: #94a3b8;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>Riepilogo Appuntamento</h1>
                <p>Condizioni di servizio concordate</p>
            </div>

            <div class="content">
                <div class="greeting">
                    <p>Gentile <strong>{{ $clientName }}</strong>,</p>
                    <p>grazie per l'incontro di oggi. Di seguito il riepilogo delle condizioni contrattuali discusse durante il nostro appuntamento.</p>
                </div>

                {{-- CONDIZIONI ECONOMICHE --}}
                <div class="section-title">Condizioni Economiche</div>
                <table class="fee-table">
                    @if($feeMensile)
                    <tr>
                        <td class="fee-label">Fee mensile</td>
                        <td class="fee-value">&euro;{{ number_format($feeMensile, 2, ',', '.') }}/mese</td>
                    </tr>
                    @endif
                    @if($feeOrdine)
                    <tr>
                        <td class="fee-label">Fee per ordine</td>
                        <td class="fee-value">&euro;{{ number_format($feeOrdine, 2, ',', '.') }}/ordine</td>
                    </tr>
                    @endif
                    @if($feeConsegnaBase)
                    <tr>
                        <td class="fee-label">Fee consegna (base)</td>
                        <td class="fee-value">&euro;{{ number_format($feeConsegnaBase, 2, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($feeConsegnaKm)
                    <tr>
                        <td class="fee-label">Fee consegna (al km)</td>
                        <td class="fee-value">&euro;{{ number_format($feeConsegnaKm, 2, ',', '.') }}/km</td>
                    </tr>
                    @endif
                    @if($abbonamentoMensile)
                    <tr>
                        <td class="fee-label">Abbonamento mensile</td>
                        <td class="fee-value">&euro;{{ number_format($abbonamentoMensile, 2, ',', '.') }}/mese</td>
                    </tr>
                    @endif
                </table>

                {{-- SERVIZI INCLUSI --}}
                <div class="section-title">Servizi Inclusi</div>
                <ul class="services-list">
                    <li>
                        <span class="{{ $hasDelivery ? 'check' : 'cross' }}">{{ $hasDelivery ? '&#10003;' : '&#10005;' }}</span>
                        Servizio di delivery gestito
                    </li>
                    <li>
                        <span class="{{ $hasPos ? 'check' : 'cross' }}">{{ $hasPos ? '&#10003;' : '&#10005;' }}</span>
                        Sistema POS integrato
                    </li>
                    <li>
                        <span class="{{ $hasDomain ? 'check' : 'cross' }}">{{ $hasDomain ? '&#10003;' : '&#10005;' }}</span>
                        Dominio e sito web dedicato
                    </li>
                </ul>

                {{-- NOTE AGGIUNTIVE --}}
                @if($notes)
                <div class="notes-box">
                    <strong>Note aggiuntive:</strong><br>
                    {!! nl2br(e($notes)) !!}
                </div>
                @endif

                {{-- PROSSIMI PASSI --}}
                <div class="next-steps">
                    <h3>Prossimi Passi</h3>
                    <ol>
                        <li>Revisione e firma del contratto di servizio</li>
                        <li>Configurazione del profilo ristorante sulla piattaforma</li>
                        <li>Caricamento del menu e delle foto</li>
                        <li>Sessione di formazione e attivazione del servizio</li>
                    </ol>
                </div>

                {{-- CONTATTI --}}
                <div class="contact-box">
                    <strong>Hai domande?</strong>
                    <p style="margin: 8px 0 0 0;">
                        Email: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a><br>
                        Telefono: {{ $supportPhone }}
                    </p>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Questa email e' stata inviata da OPPLA One Manager.</p>
            <p>&copy; {{ date('Y') }} OPPLA - Tutti i diritti riservati</p>
        </div>
    </div>
</body>
</html>
