<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

class OpplaContractTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $htmlTemplate = file_get_contents(__DIR__ . '/oppla-contract-template.html');

        ContractTemplate::updateOrCreate(
            ['code' => 'oppla-subscription-cover'],
            [
                'name' => 'Contratto Opplà - Piano di Abbonamento',
                'description' => 'Template contratto standard Opplà per partnership con ristoranti',
                'category' => 'partnership',
                'html_template' => $htmlTemplate,
                'required_fields' => json_encode([
                    'partner_ragione_sociale',
                    'partner_piva',
                    'partner_email',
                    'partner_legale_rappresentante',
                    'partner_sede_legale',
                    'partner_iban',
                    'start_date',
                    'periodo_mesi',
                    'siti',
                    'costo_attivazione',
                    'servizi'
                ]),
                'is_active' => true
            ]
        );

        $this->command->info('Template contratto Opplà creato con successo!');
    }
}
