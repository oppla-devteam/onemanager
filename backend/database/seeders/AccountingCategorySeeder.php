<?php

namespace Database\Seeders;

use App\Models\AccountingCategory;
use Illuminate\Database\Seeder;

class AccountingCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // ENTRATE
            [
                'name' => 'Commissioni Stripe',
                'slug' => 'commissioni_stripe',
                'type' => 'entrata',
                'color' => '#10b981',
                'icon' => 'credit-card',
                'keywords' => ['stripe', 'payment', 'commissioni incasso'],
                'is_active' => true,
            ],
            [
                'name' => 'Fatture Clienti',
                'slug' => 'fatture_clienti',
                'type' => 'entrata',
                'color' => '#3b82f6',
                'icon' => 'file-invoice',
                'keywords' => ['fattura', 'cliente', 'ristorante'],
                'is_active' => true,
            ],
            [
                'name' => 'Versamenti Soci',
                'slug' => 'versamenti_soci',
                'type' => 'entrata',
                'color' => '#8b5cf6',
                'icon' => 'users',
                'keywords' => ['moschella', 'socio', 'versamento', 'giroconto oppla'],
                'is_active' => true,
            ],
            [
                'name' => 'Altri Incassi',
                'slug' => 'altri_incassi',
                'type' => 'entrata',
                'color' => '#06b6d4',
                'icon' => 'arrow-down',
                'keywords' => [],
                'is_active' => true,
            ],

            // USCITE - Personale
            [
                'name' => 'Stipendi e Compensi',
                'slug' => 'stipendi',
                'type' => 'uscita',
                'color' => '#ef4444',
                'icon' => 'users',
                'keywords' => ['freschi', 'giachetti', 'moschella', 'superti', 'gargini', 'zucca', 'stipendio', 'compenso', 'tfr'],
                'is_active' => true,
            ],

            // USCITE - Professionisti
            [
                'name' => 'Commercialista',
                'slug' => 'commercialista',
                'type' => 'uscita',
                'color' => '#f59e0b',
                'icon' => 'calculator',
                'keywords' => ['pardini', 'commercialista', 'studio pardini'],
                'is_active' => true,
            ],
            [
                'name' => 'Avvocati',
                'slug' => 'avvocati',
                'type' => 'uscita',
                'color' => '#f59e0b',
                'icon' => 'balance-scale',
                'keywords' => ['avv', 'avvocato', 'scapuzzi', 'morgantini', 'legal'],
                'is_active' => true,
            ],
            [
                'name' => 'Notaio',
                'slug' => 'notaio',
                'type' => 'uscita',
                'color' => '#f59e0b',
                'icon' => 'stamp',
                'keywords' => ['notaio', 'atto', 'costituzione'],
                'is_active' => true,
            ],

            // USCITE - Ufficio
            [
                'name' => 'Affitto e Condominio',
                'slug' => 'affitto',
                'type' => 'uscita',
                'color' => '#dc2626',
                'icon' => 'building',
                'keywords' => ['affitto', 'condominio', 'locazione', 'immobile'],
                'is_active' => true,
            ],
            [
                'name' => 'Utenze',
                'slug' => 'utenze',
                'type' => 'uscita',
                'color' => '#dc2626',
                'icon' => 'bolt',
                'keywords' => ['fastweb', 'enel', 'energia', 'gas', 'acqua', 'internet', 'telefono'],
                'is_active' => true,
            ],

            // USCITE - Software e Tech
            [
                'name' => 'Hosting e Cloud',
                'slug' => 'hosting',
                'type' => 'uscita',
                'color' => '#7c3aed',
                'icon' => 'server',
                'keywords' => ['siteground', 'render', 'aws', 'hosting', 'server', 'cloud'],
                'is_active' => true,
            ],
            [
                'name' => 'Software e SaaS',
                'slug' => 'software',
                'type' => 'uscita',
                'color' => '#7c3aed',
                'icon' => 'laptop-code',
                'keywords' => ['canva', 'manychat', 'openai', 'postmark', 'yousign', 'airtable', 'make', 'bitwarden', 'onesignal'],
                'is_active' => true,
            ],
            [
                'name' => 'Sviluppo Software',
                'slug' => 'sviluppo',
                'type' => 'uscita',
                'color' => '#7c3aed',
                'icon' => 'code',
                'keywords' => ['sviluppo', 'developer', 'programmatore', 'app', 'bug fix'],
                'is_active' => true,
            ],

            // USCITE - Marketing
            [
                'name' => 'Pubblicità e Marketing',
                'slug' => 'marketing',
                'type' => 'uscita',
                'color' => '#ec4899',
                'icon' => 'bullhorn',
                'keywords' => ['meta', 'facebook', 'ads', 'pubblicità', 'marketing', 'campagna', 'sponsorizzazione', 'fortezza basket'],
                'is_active' => true,
            ],

            // USCITE - Banca e Tasse
            [
                'name' => 'Commissioni Bancarie',
                'slug' => 'commissioni_bancarie',
                'type' => 'uscita',
                'color' => '#64748b',
                'icon' => 'university',
                'keywords' => ['comm.', 'commissione', 'spese', 'bonifico', 'nexi', 'carta', 'imposta di bollo'],
                'is_active' => true,
            ],
            [
                'name' => 'Tasse e Imposte',
                'slug' => 'tasse',
                'type' => 'uscita',
                'color' => '#64748b',
                'icon' => 'receipt',
                'keywords' => ['f24', 'iva', 'ritenuta', 'inps', 'inail', 'deleghe ade', 'tasse'],
                'is_active' => true,
            ],

            // USCITE - Altro
            [
                'name' => 'Altre Spese',
                'slug' => 'altre_spese',
                'type' => 'uscita',
                'color' => '#6b7280',
                'icon' => 'ellipsis-h',
                'keywords' => [],
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            AccountingCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
