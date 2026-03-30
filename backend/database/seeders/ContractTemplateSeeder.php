<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Piano di Abbonamento - Delivery',
                'code' => 'delivery_partnership',
                'description' => 'Piano di abbonamento per ristoranti partner con servizi delivery',
                'category' => 'partnership',
                'required_fields' => [
                    'partner_name',
                    'partner_vat',
                    'partner_address',
                    'partner_iban',
                    'partner_legal_rep',
                    'partner_email',
                    'site_name',
                    'site_address',
                    'start_date',
                    'duration_months',
                ],
                'html_template' => $this->getDeliveryPartnershipTemplate(),
            ],
        ];

        foreach ($templates as $template) {
            ContractTemplate::updateOrCreate(
                ['code' => $template['code']],
                $template
            );
        }
    }

    private function getDeliveryPartnershipTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: Arial, sans-serif; font-size: 11pt; }
    .header { text-align: center; margin-bottom: 30px; }
    .header img { max-width: 200px; }
    .header h1 { color: #4A4A8A; margin: 10px 0; }
    .section-title { background-color: #4A4A8A; color: white; padding: 8px; margin-top: 20px; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    table td, table th { border: 1px solid #ccc; padding: 8px; }
    .label { font-weight: bold; background-color: #f5f5f5; }
    .value { background-color: white; }
</style>
</head>
<body>

<div class="header">
    <h1>Piano di Abbonamento - Copertina</h1>
</div>

<div class="section-title">Informazioni generali</div>
<p>Il presente contratto è stipulato tra (che rappresentano le "Parti", ciascuna una "Parte" del contratto):</p>

<table>
<tr>
    <td class="label">Oppla SRL</td>
    <td class="value" colspan="2">(di seguito "Opplà")</td>
    <td class="label">Ragione sociale</td>
    <td class="value" colspan="2">{{partner_name}}</td>
</tr>
<tr>
    <td colspan="3" style="font-size: 9pt;">
        società a responsabilità limitata di diritto italiana con<br>
        capitale sociale pari a 10.000 euro, iscritta al Registro delle Imprese di Livorno<br>
        nella figura del suo delegato legale, provvisto dei poteri necessari.
    </td>
    <td colspan="3" style="font-size: 9pt;">
        (di seguito "Partner")<br>
        una società di diritto italiano e iscritta al Registro delle Imprese di Italy,<br>
        nella persona del suo rappresentante autorizzato, munito dei necessari poteri.
    </td>
</tr>
<tr>
    <td class="label">P.Iva</td>
    <td class="value" colspan="2">IT02027300496</td>
    <td class="label">P.iva</td>
    <td class="value" colspan="2">{{partner_vat}}</td>
</tr>
<tr>
    <td class="label">Con sede legale presso:</td>
    <td class="value" colspan="2">Via Cairoli, n. 21, 57123, Livorno, Italia</td>
    <td class="label">Con sede legale presso:</td>
    <td class="value" colspan="2">{{partner_address}}</td>
</tr>
<tr>
    <td colspan="3"></td>
    <td class="label">Coordinate bancarie IBAN</td>
    <td class="value" colspan="2">{{partner_iban}}</td>
</tr>
</table>

<div class="section-title">Durata e zona</div>
<table>
<tr>
    <td class="label">Data di Inizio</td>
    <td class="value">{{start_date}}</td>
</tr>
<tr>
    <td class="label">Periodo (mesi)</td>
    <td class="value">{{duration_months}}</td>
</tr>
<tr>
    <td class="label">Territorio</td>
    <td class="value">Italia</td>
</tr>
</table>

<div class="section-title">Delegati legali</div>
<table>
<tr>
    <td colspan="3" class="label" style="text-align: center;">Opplà</td>
    <td colspan="3" class="label" style="text-align: center;">Partner</td>
</tr>
<tr>
    <td class="label">Titolare:</td>
    <td class="value" colspan="2">Lorenzo Moschella</td>
    <td class="label">Legale rappresentante</td>
    <td class="value" colspan="2">{{partner_legal_rep}}</td>
</tr>
<tr>
    <td class="label">indirizzo email:</td>
    <td class="value" colspan="2">lorenzo.moschella@oppla.delivery</td>
    <td class="label">indirizzo email:</td>
    <td class="value" colspan="2">{{partner_email}}</td>
</tr>
</table>

<div class="section-title">Siti del Partner</div>
<p>Il Partner elaborerà gli ordini dai seguenti "siti"</p>
<table>
<tr>
    <td class="label">Nome del sito</td>
    <td class="label">Indirizzo del sito</td>
</tr>
<tr>
    <td class="value">{{site_name}}</td>
    <td class="value">{{site_address}}</td>
</tr>
</table>

<div class="section-title">Costo di attivazione</div>
<p>Il costo di attivazione non è comprensivo di IVA ed è indicato di seguito</p>
<table>
<tr>
    <td class="label">Sito</td>
    <td class="label">Tariffa attivazione</td>
</tr>
<tr>
    <td class="value">{{site_name}}</td>
    <td class="value">€150</td>
</tr>
<tr>
    <td colspan="1"></td>
    <td class="label">Tot. Iva inclusa: <strong>€ 183,00</strong></td>
</tr>
</table>

<div class="section-title">Servizi e Tariffe dei servizi</div>
<p>Le Tariffe dei servizi non sono comprensivi di IVA e sono indicate di seguito</p>
<table>
<tr>
    <td class="label">Sito</td>
    <td class="label">Servizi</td>
    <td class="label">Tariffa applicabile</td>
    <td class="label">Condizioni Tariffa</td>
    <td class="label">Data di inizio</td>
</tr>
<tr>
    <td class="value">{{site_name}}</td>
    <td class="value">Servizi di Ritiro</td>
    <td class="value">€ 12,00</td>
    <td class="value">Tariffa ad ordine</td>
    <td class="value">{{start_date}}</td>
</tr>
<tr>
    <td class="value">{{site_name}}</td>
    <td class="value">Servizi Principali</td>
    <td class="value">€ 2,98</td>
    <td class="value">Tariffa ad ordine</td>
    <td class="value">{{start_date}}</td>
</tr>
<tr>
    <td class="value">{{site_name}}</td>
    <td class="value">Ordine rifiutato</td>
    <td class="value">€ 1,49</td>
    <td class="value">Tariffa ad ordine</td>
    <td class="value">{{start_date}}</td>
</tr>
<tr>
    <td class="value">{{site_name}}</td>
    <td class="value">Abbonamento consegne</td>
    <td class="value">€ 24,00</td>
    <td class="value">Tariffa mensile</td>
    <td class="value">{{start_date}}</td>
</tr>
<tr>
    <td class="value">{{site_name}}</td>
    <td class="value">Inserimento ordine Manuale</td>
    <td class="value">€ 1,49</td>
    <td class="value">Tariffa ad ordine</td>
    <td class="value">{{start_date}}</td>
</tr>
</table>

<p style="font-size: 9pt; margin-top: 15px;">
<strong>Note:</strong><br>
Le Tariffe dei servizi sono valide a partire dalla Data di Inizio fino per tutta la durata, altrimenti verrà indicato nella sezione Data di inizio. 
Opplà si riserva il diritto di modificare le tariffe previa comunicazione per iscritto al Partner entro 20 gg dalla notifica.<br>
Il Partner ha diritto di negoziare tali modifiche entro 5 gg dal ricevimento della comunicazione da parte di Opplà, 
periodo dopo il quale le modifiche alle tariffe si considereranno effettive. Il Servizio di Ritiro si riferisce ad ogni ordine di Ritiro, 
il Servizio Principale si riferisce ad ogni ordine di Consegna (come indicato nei Termini e Condizioni).<br>
Ove presente la condizione "Tariffa mensile" il Partner è tenuto a pagare la cifra il primo giorno di ogni mese per mezzo di 
addebito diretto sul conto Stripe o similari generato al momento dell'attivazione.<br>
"Tariffa ad ordine" il Partner è tenuto a pagare la tariffa ogni ordine ricevuto contestualmente alla ricezione di quest'ultimo 
per mezzo del conto Stripe o similari tramite la procedura di split payment.<br>
Il Partner sarà tenuto a pagare un contributo mensile di €12,00 esclusivamente nel caso in cui affidi le consegne a un corriere locale 
convenzionato con Oppla SRL. In tutti gli altri casi, tale contributo non sarà dovuto.
</p>

<div class="section-title">Attrezzature</div>
<table>
<tr>
    <td class="label">Sito</td>
    <td class="label">Attrezzatura fornita</td>
</tr>
<tr>
    <td class="value">{{site_name}}</td>
    <td class="value">L'attrezzatura è fornita da Opplà</td>
</tr>
</table>

<div style="margin-top: 50px;">
<p><strong>Luogo e data:</strong> ________________________</p>

<table style="margin-top: 50px; border: none;">
<tr>
    <td style="border: none; width: 50%; text-align: center;">
        <div style="border-top: 1px solid black; padding-top: 5px; margin: 0 50px;">
            Firma Partner
        </div>
    </td>
    <td style="border: none; width: 50%; text-align: center;">
        <div style="border-top: 1px solid black; padding-top: 5px; margin: 0 50px;">
            Firma Opplà S.R.L.
        </div>
    </td>
</tr>
</table>
</div>

</body>
</html>
HTML;
    }

    private function getUpsellingServiceTemplate(): string
    {
        return '';
    }

    private function getNdaTemplate(): string
    {
        return '';
    }
}
