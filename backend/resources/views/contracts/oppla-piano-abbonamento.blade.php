<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>Piano di Abbonamento – Copertina</title>
<style>
  @page { size: A4; margin: 12mm 10mm 14mm 10mm; }
  body{font-family:Arial, Helvetica, sans-serif; color:#111; font-size:9pt;}
  .container{max-width:800px;margin:0 auto;}
  .logo{ text-align:center; margin:10px 0 6px;}
  .logo img{ max-height:60px; max-width:220px;}
  .title{ text-align:center; font-size:20pt; font-weight:bold; color:#3d3c82;
          padding:8px 0 14px; border-bottom:1px solid #ddd; margin-bottom:12px;}
  .section{ margin:16px 0 8px; font-size:11pt; font-weight:bold; 
            border-left:4px solid #3d3c82; padding-left:8px; }
  .grid{ display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
  .box{ border:1px solid #ddd; padding:10px; background:#fafafa; }
  .lbl{ font-weight:bold; margin:0 0 2px; }
  table{ width:100%; border-collapse:collapse; margin:10px 0 0; font-size:8.5pt;}
  th,td{ border:1px solid #ddd; padding:7px; text-align:left; }
  th{ background:#f1f3f4; font-weight:bold; }
  .note{ border:1px solid #ddd; background:#fafafa; padding:10px; margin-top:8px; line-height:1.45;}
  .sig table{ width:100%; border-collapse:collapse; margin-top:10px; }
  .sig th,.sig td{ border:1px solid #000; padding:8px; }
  .sig .space{ height:80px; }
</style>
</head>
<body>
<div class="container">

  <div class="logo">
    <img src="https://info.oppla.delivery/logo/Oppla_logo_nuovo.png" alt="Opplà">
  </div>
  <div class="title">Piano di Abbonamento – Copertina</div>

  <!-- Informazioni generali -->
  <div class="section">Informazioni generali</div>
  <div class="note">
    Il presente contratto è stipulato tra (che rappresentano le "Parti", ciascuna una "Parte" del contratto):
  </div>

  <div class="grid">
    <div class="box">
      <div class="lbl">Oppla SRL</div>
      <div>(di seguito "Opplà")</div>
      <div style="margin-top:6px">
        Società a responsabilità limitata di diritto italiano con capitale sociale pari a 10.000 euro,
        iscritta al Registro delle Imprese di Livorno nella figura del suo delegato legale,
        provvisto dei poteri necessari.
      </div>
      <div class="lbl" style="margin-top:6px">P. IVA</div>
      <div>IT02027300496</div>
      <div class="lbl" style="margin-top:6px">Sede legale</div>
      <div>Via Cairoli, 21 – 57123 Livorno (LI)</div>
    </div>

    <div class="box">
      <div class="lbl">Ragione sociale</div>
      <div>{{ $partner_ragione_sociale }}<br>(di seguito "Partner")</div>

      <div style="margin-top:6px">
        Società di diritto italiano iscritta al Registro delle Imprese, nella
        persona del suo rappresentante autorizzato munito dei necessari poteri.
      </div>

      <div class="lbl" style="margin-top:6px">P. IVA</div>
      <div>{{ $partner_piva }}</div>

      <div class="lbl" style="margin-top:6px">Sede legale</div>
      <div>{{ $partner_sede_legale }}</div>

      <div class="lbl" style="margin-top:6px">IBAN</div>
      <div>{{ $partner_iban }}</div>
    </div>
  </div>

  <!-- Durata e zona -->
  <div class="section">Durata e zona</div>
  <div class="grid" style="grid-template-columns:1fr 1fr 1fr">
    <div class="box">
      <div class="lbl">Data di inizio</div>
      <div>{{ $start_date }}</div>
    </div>
    <div class="box">
      <div class="lbl">Periodo (mesi)</div>
      <div>{{ $periodo_mesi }}</div>
    </div>
    <div class="box">
      <div class="lbl">Territorio</div>
      <div>{{ $territorio }}</div>
    </div>
  </div>

  <!-- Delegati -->
  <div class="section">Delegati legali</div>
  <div class="grid">
    <div class="box">
      <div class="lbl">Opplà</div>
      <div>Titolare: Lorenzo Moschella</div>
      <div class="lbl" style="margin-top:6px">Email</div>
      <div>lorenzo.moschella@oppla.delivery</div>
    </div>
    <div class="box">
      <div class="lbl">Partner</div>
      <div>Legale rappresentante: {{ $partner_legale_rappresentante }}</div>
      <div class="lbl" style="margin-top:6px">Email</div>
      <div>{{ $partner_email }}</div>
    </div>
  </div>

  <!-- Siti del Partner -->
  <div class="section">Siti del Partner</div>
  <div class="note">Il Partner elaborerà gli ordini dai seguenti "siti".</div>
  <table>
    <tr>
      <th style="width:50%">Nome del sito</th>
      <th style="width:50%">Indirizzo del sito</th>
    </tr>
    <tr>
      <td>{{ $site_name }}</td>
      <td>{{ $site_address }}</td>
    </tr>
  </table>

  <!-- Costo di attivazione -->
  <div class="section">Costo di attivazione</div>
  <div class="note">Il costo di attivazione non è comprensivo di IVA ed è indicato di seguito.</div>
  <table>
    <tr>
      <th style="width:50%">Sito</th>
      <th style="width:50%">Tariffa attivazione</th>
    </tr>
    <tr>
      <td>{{ $site_name }}</td>
      <td>€ {{ $costo_attivazione }} + IVA</td>
    </tr>
  </table>

  <!-- Servizi e tariffe -->
  <div class="section">Servizi e Tariffe dei servizi</div>
  <div class="note">Le Tariffe dei servizi non sono comprensive di IVA e sono indicate di seguito.</div>
  <table>
    <tr>
      <th style="width:20%">Sito</th>
      <th style="width:30%">Servizio</th>
      <th style="width:15%">Tariffa</th>
      <th style="width:20%">Condizione</th>
      <th style="width:15%">Data di inizio</th>
    </tr>
    <tr>
      <td>{{ $site_name }}</td>
      <td>Servizi di Ritiro</td>
      <td>€ {{ $servizio_ritiro }}</td>
      <td>Tariffa ad ordine</td>
      <td>{{ $start_date }}</td>
    </tr>
    <tr>
      <td>{{ $site_name }}</td>
      <td>Servizi Principali</td>
      <td>€ {{ $servizio_principale }} / al mese</td>
      <td>Tariffa mensile</td>
      <td>{{ $start_date }}</td>
    </tr>
    <tr>
      <td>{{ $site_name }}</td>
      <td>Ordine rifiutato</td>
      <td>€ {{ $ordine_rifiutato }}</td>
      <td>Tariffa ad ordine</td>
      <td>{{ $start_date }}</td>
    </tr>
    <tr>
      <td>{{ $site_name }}</td>
      <td>Abbonamento Consegne</td>
      <td>€ {{ $abbonamento_consegne }} / mese</td>
      <td>Tariffa mensile</td>
      <td>{{ $start_date }}</td>
    </tr>
    <tr>
      <td>{{ $site_name }}</td>
      <td>Inserimento ordine Manuale</td>
      <td>€ {{ $inserimento_manuale }}</td>
      <td>Tariffa ad ordine</td>
      <td>{{ $start_date }}</td>
    </tr>
  </table>

  <!-- MPG -->
  <div class="note" style="margin-top:10px">
    <span class="lbl">Miglior Prezzo Garantito</span><br>
    Il Partner {{ $miglior_prezzo_garantito_text }} al programma "Miglior Prezzo Garantito".
  </div>

  <!-- Attrezzatura -->
  <div class="note" style="margin-top:10px">
    <span class="lbl">Attrezzatura fornita da Opplà</span><br>
    {{ $attrezzatura_fornita_text }}
  </div>

  <!-- Sottoscrizione -->
  <div class="section">Sottoscrizione</div>
  <div class="note">
    Il presente Contratto è composto da: questa Copertina, dai Termini e condizioni generali per il Partner,
    dal Piano di Abbonamento. Ogni parte conferma di aver letto tutte le parti del Contratto e accetta di vincolarvisi.
  </div>

  <div class="sig">
    <table>
      <tr>
        <th style="width:50%">In nome e per conto di Opplà</th>
        <th style="width:50%">In nome e per conto del Partner</th>
      </tr>
      <tr>
        <td class="space">
          <div style="margin-top:10px">
            <strong>Firma Digitale Opplà</strong><br>
            Documento firmato digitalmente in data {{ $signature_date }}
          </div>
        </td>
        <td class="space">
          {{ $signature_image }}
        </td>
      </tr>
      <tr>
        <td><strong>Nome</strong><br>Titolare: Lorenzo Moschella</td>
        <td><strong>Nome</strong><br>{{ $partner_legale_rappresentante }}</td>
      </tr>
      <tr>
        <td><strong>Ruolo</strong><br>Delegato legale</td>
        <td><strong>Ruolo</strong><br>Delegato legale</td>
      </tr>
      <tr>
        <td><strong>Data</strong><br>{{ $start_date }}</td>
        <td><strong>Data</strong><br>{{ $signature_date }}</td>
      </tr>
    </table>
  </div>

</div>
</body>
</html>
