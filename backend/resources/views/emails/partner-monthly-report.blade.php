<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Mensile {{ $data['period']['month_name'] }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            color: #1e293b;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            max-width: 640px;
            margin: 0 auto;
            padding: 20px 16px;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        .hero {
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
            color: #ffffff;
            padding: 32px 28px;
            text-align: center;
        }
        .hero-label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.85;
            margin-bottom: 4px;
        }
        .hero h1 {
            margin: 0 0 6px 0;
            font-size: 26px;
            font-weight: 700;
        }
        .hero-partner {
            font-size: 16px;
            opacity: 0.9;
        }
        .highlight-card {
            padding: 24px 28px;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
        }
        .highlight-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .highlight-value {
            font-size: 36px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }
        .highlight-sub {
            font-size: 14px;
            color: #64748b;
            margin-top: 4px;
        }
        .growth-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 6px;
        }
        .growth-positive {
            background: #dcfce7;
            color: #16a34a;
        }
        .growth-negative {
            background: #fee2e2;
            color: #dc2626;
        }
        .growth-neutral {
            background: #f1f5f9;
            color: #64748b;
        }
        .section {
            padding: 20px 28px;
        }
        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 16px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }
        .metrics-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .metrics-grid td {
            padding: 10px 0;
            vertical-align: top;
            width: 50%;
        }
        .metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 2px;
        }
        .metric-value {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
        }
        .metric-value-sm {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            margin-top: 6px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            border-radius: 3px;
        }
        .progress-green { background: #22c55e; }
        .progress-blue { background: #3b82f6; }
        .divider {
            height: 1px;
            background: #f1f5f9;
            margin: 0 28px;
        }
        .top-days-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .top-days-item:last-child {
            border-bottom: none;
        }
        .top-days-rank {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 12px;
            background: #0ea5e9;
            color: #fff;
            text-align: center;
            line-height: 24px;
            font-size: 12px;
            font-weight: 700;
            margin-right: 10px;
        }
        .revenue-row {
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .revenue-row:last-child {
            border-bottom: none;
        }
        .revenue-row-label {
            font-size: 14px;
            color: #475569;
        }
        .revenue-row-value {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            text-align: right;
        }
        .revenue-total {
            padding: 14px 0;
            border-top: 2px solid #0ea5e9;
            margin-top: 4px;
        }
        .revenue-total .revenue-row-label {
            font-weight: 700;
            color: #0f172a;
            font-size: 15px;
        }
        .revenue-total .revenue-row-value {
            font-size: 20px;
            color: #0ea5e9;
        }
        .footer {
            text-align: center;
            padding: 24px 28px;
            color: #94a3b8;
            font-size: 12px;
        }
        .footer a {
            color: #0ea5e9;
            text-decoration: none;
        }
        @media only screen and (max-width: 480px) {
            .wrapper { padding: 12px 8px; }
            .hero { padding: 24px 20px; }
            .hero h1 { font-size: 22px; }
            .section { padding: 16px 20px; }
            .highlight-card { padding: 20px; }
            .highlight-value { font-size: 30px; }
            .metric-value { font-size: 18px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        {{-- HERO --}}
        <div class="card">
            <div class="hero">
                <div class="hero-label">Report Mensile</div>
                <h1>{{ ucfirst($data['period']['month_name']) }}</h1>
                <div class="hero-partner">{{ $data['partner']['name'] }}@if($data['partner']['city']) &middot; {{ $data['partner']['city'] }}@endif</div>
            </div>

            {{-- HIGHLIGHT: Fatturato --}}
            <div class="highlight-card">
                <div class="highlight-label">Fatturato del mese</div>
                <div class="highlight-value">
                    &euro;{{ number_format($data['revenue']['gross_revenue'], 2, ',', '.') }}
                    @php
                        $growthClass = $data['growth']['orders_amount'] > 0 ? 'growth-positive' : ($data['growth']['orders_amount'] < 0 ? 'growth-negative' : 'growth-neutral');
                        $growthSign = $data['growth']['orders_amount'] > 0 ? '+' : '';
                    @endphp
                    <span class="growth-badge {{ $growthClass }}">{{ $growthSign }}{{ number_format($data['growth']['orders_amount'], 1) }}%</span>
                </div>
                <div class="highlight-sub">vs mese precedente</div>
            </div>
        </div>

        {{-- ORDINI --}}
        <div class="card">
            <div class="section">
                <div class="section-title">Ordini</div>
                <table class="metrics-grid">
                    <tr>
                        <td>
                            <div class="metric-label">Totale ordini</div>
                            <div class="metric-value">{{ number_format($data['orders']['total_count']) }}</div>
                            @php
                                $gc = $data['growth']['orders_count'] > 0 ? 'growth-positive' : ($data['growth']['orders_count'] < 0 ? 'growth-negative' : 'growth-neutral');
                                $gs = $data['growth']['orders_count'] > 0 ? '+' : '';
                            @endphp
                            <span class="growth-badge {{ $gc }}" style="margin-left:0">{{ $gs }}{{ number_format($data['growth']['orders_count'], 1) }}%</span>
                        </td>
                        <td>
                            <div class="metric-label">Valore medio</div>
                            <div class="metric-value">&euro;{{ number_format($data['orders']['average_order_value'], 2, ',', '.') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="metric-label">Completati</div>
                            <div class="metric-value-sm">{{ $data['orders']['completed_count'] }}</div>
                        </td>
                        <td>
                            <div class="metric-label">Cancellati</div>
                            <div class="metric-value-sm">{{ $data['orders']['cancelled_count'] }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div class="metric-label">Tasso completamento</div>
                            <div class="metric-value-sm">{{ $data['orders']['completion_rate'] }}%</div>
                            <div class="progress-bar">
                                <div class="progress-fill progress-green" style="width: {{ $data['orders']['completion_rate'] }}%"></div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            @if(count($data['top_days']) > 0)
            <div class="divider"></div>
            <div class="section">
                <div class="section-title">Giorni migliori</div>
                @foreach($data['top_days'] as $i => $day)
                <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom: 1px solid #f1f5f9; {{ $loop->last ? 'border-bottom:none;' : '' }}">
                    <tr>
                        <td style="padding: 8px 0; width: 34px;">
                            <span class="top-days-rank">{{ $i + 1 }}</span>
                        </td>
                        <td style="padding: 8px 0; font-size: 14px; color: #475569;">{{ $day['date'] }}</td>
                        <td style="padding: 8px 0; text-align: right; font-weight: 700; color: #0f172a;">{{ $day['count'] }} ordini</td>
                    </tr>
                </table>
                @endforeach
            </div>
            @endif
        </div>

        {{-- CONSEGNE --}}
        <div class="card">
            <div class="section">
                <div class="section-title">Consegne</div>
                <table class="metrics-grid">
                    <tr>
                        <td>
                            <div class="metric-label">Totale consegne</div>
                            <div class="metric-value">{{ number_format($data['deliveries']['total_count']) }}</div>
                            @php
                                $dgc = $data['growth']['deliveries_count'] > 0 ? 'growth-positive' : ($data['growth']['deliveries_count'] < 0 ? 'growth-negative' : 'growth-neutral');
                                $dgs = $data['growth']['deliveries_count'] > 0 ? '+' : '';
                            @endphp
                            <span class="growth-badge {{ $dgc }}" style="margin-left:0">{{ $dgs }}{{ number_format($data['growth']['deliveries_count'], 1) }}%</span>
                        </td>
                        <td>
                            <div class="metric-label">Km percorsi</div>
                            <div class="metric-value">{{ number_format($data['deliveries']['total_distance_km'], 1, ',', '.') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="metric-label">Distanza media</div>
                            <div class="metric-value-sm">{{ number_format($data['deliveries']['average_distance_km'], 1, ',', '.') }} km</div>
                        </td>
                        <td>
                            <div class="metric-label">Costi consegna</div>
                            <div class="metric-value-sm">&euro;{{ number_format($data['deliveries']['total_delivery_fees'], 2, ',', '.') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div class="metric-label">Tasso completamento</div>
                            <div class="metric-value-sm">{{ $data['deliveries']['completion_rate'] }}%</div>
                            <div class="progress-bar">
                                <div class="progress-fill progress-blue" style="width: {{ $data['deliveries']['completion_rate'] }}%"></div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- RIEPILOGO ECONOMICO --}}
        <div class="card">
            <div class="section">
                <div class="section-title">Riepilogo Economico</div>

                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr class="revenue-row">
                        <td style="padding: 10px 0; font-size: 14px; color: #475569;">Fatturato ordini</td>
                        <td style="padding: 10px 0; text-align: right; font-size: 16px; font-weight: 700; color: #0f172a;">&euro;{{ number_format($data['orders']['total_amount'], 2, ',', '.') }}</td>
                    </tr>
                    @if($data['pos']['total_count'] > 0)
                    <tr class="revenue-row">
                        <td style="padding: 10px 0; font-size: 14px; color: #475569;">Fatturato POS ({{ $data['pos']['total_count'] }} transazioni)</td>
                        <td style="padding: 10px 0; text-align: right; font-size: 16px; font-weight: 700; color: #0f172a;">&euro;{{ number_format($data['pos']['total_amount'], 2, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($data['upselling']['total_count'] > 0)
                    <tr class="revenue-row">
                        <td style="padding: 10px 0; font-size: 14px; color: #475569;">Upselling ({{ $data['upselling']['total_count'] }} vendite)</td>
                        <td style="padding: 10px 0; text-align: right; font-size: 16px; font-weight: 700; color: #0f172a;">&euro;{{ number_format($data['upselling']['total_amount'], 2, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="revenue-row">
                        <td style="padding: 10px 0; font-size: 14px; color: #475569;">Costi consegna</td>
                        <td style="padding: 10px 0; text-align: right; font-size: 16px; font-weight: 700; color: #64748b;">&euro;{{ number_format($data['revenue']['delivery_fees'], 2, ',', '.') }}</td>
                    </tr>
                    @if($data['revenue']['platform_fees'] > 0)
                    <tr class="revenue-row">
                        <td style="padding: 10px 0; font-size: 14px; color: #475569;">Fee piattaforma</td>
                        <td style="padding: 10px 0; text-align: right; font-size: 16px; font-weight: 700; color: #ef4444;">-&euro;{{ number_format($data['revenue']['platform_fees'], 2, ',', '.') }}</td>
                    </tr>
                    @endif
                </table>

                <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 2px solid #0ea5e9; margin-top: 4px;">
                    <tr>
                        <td style="padding: 14px 0; font-weight: 700; font-size: 15px; color: #0f172a;">Netto partner</td>
                        <td style="padding: 14px 0; text-align: right; font-size: 20px; font-weight: 800; color: #0ea5e9;">&euro;{{ number_format($data['revenue']['net_revenue'], 2, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- FATTURAZIONE --}}
        <div class="card">
            <div class="section">
                <div class="section-title">Fatturazione</div>
                <table class="metrics-grid">
                    <tr>
                        <td>
                            <div class="metric-label">Fatture emesse</div>
                            <div class="metric-value-sm">{{ $data['invoices']['total_count'] }}</div>
                        </td>
                        <td>
                            <div class="metric-label">Importo totale</div>
                            <div class="metric-value-sm">&euro;{{ number_format($data['invoices']['total_amount'], 2, ',', '.') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="metric-label">Incassato</div>
                            <div class="metric-value-sm" style="color: #16a34a;">&euro;{{ number_format($data['invoices']['paid_amount'], 2, ',', '.') }}</div>
                        </td>
                        <td>
                            <div class="metric-label">Da incassare</div>
                            <div class="metric-value-sm" style="color: {{ $data['invoices']['unpaid_amount'] > 0 ? '#dc2626' : '#0f172a' }};">&euro;{{ number_format($data['invoices']['unpaid_amount'], 2, ',', '.') }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="footer">
            <p>Report generato automaticamente il {{ now()->locale('it')->isoFormat('D MMMM YYYY [alle] HH:mm') }}</p>
            <p>OPPLA One Manager</p>
            <p style="margin-top: 8px;">Per qualsiasi domanda: <a href="mailto:supporto@oppla.it">supporto@oppla.it</a></p>
        </div>
    </div>
</body>
</html>
