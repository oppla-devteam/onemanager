<?php

namespace Database\Seeders;

use App\Models\AccountingCategory;
use Illuminate\Database\Seeder;

class FinancialCategorySeeder extends Seeder
{
    public function run(): void
    {
        $hierarchicalCategories = [
            [
                'parent' => ['name' => 'Ufficio', 'slug' => 'fin-ufficio', 'type' => 'uscita', 'color' => '#dc2626', 'icon' => 'building', 'description' => 'Costi fissi ufficio'],
                'children' => [
                    ['name' => 'Affitto', 'slug' => 'fin-ufficio-affitto', 'keywords' => ['affitto', 'locazione']],
                    ['name' => 'Utenze', 'slug' => 'fin-ufficio-utenze', 'keywords' => ['enel', 'fastweb', 'gas', 'acqua']],
                    ['name' => 'Condominio', 'slug' => 'fin-ufficio-condominio', 'keywords' => ['condominio', 'supercondominio']],
                ],
            ],
            [
                'parent' => ['name' => 'HR', 'slug' => 'fin-hr', 'type' => 'uscita', 'color' => '#ef4444', 'icon' => 'users', 'description' => 'Costi risorse umane'],
                'children' => [
                    ['name' => 'Stipendi', 'slug' => 'fin-hr-stipendi', 'keywords' => ['stipendio', 'compenso', 'tfr']],
                    ['name' => 'Contributi dipendenti', 'slug' => 'fin-hr-contributi', 'keywords' => ['contributi', 'inps dipendenti']],
                ],
            ],
            [
                'parent' => ['name' => 'Costi bancari e pagamenti', 'slug' => 'fin-costi-bancari', 'type' => 'uscita', 'color' => '#64748b', 'icon' => 'landmark', 'description' => 'Costi bancari, carte e commissioni'],
                'children' => [
                    ['name' => 'Canone conto corrente', 'slug' => 'fin-bancari-canone-conto', 'keywords' => ['canone conto']],
                    ['name' => 'Carta di credito', 'slug' => 'fin-bancari-carta-credito', 'keywords' => ['carta credito', 'nexi']],
                    ['name' => 'Commissioni Stripe', 'slug' => 'fin-bancari-comm-stripe', 'keywords' => ['stripe', 'commissione stripe']],
                    ['name' => 'Costo bonifico', 'slug' => 'fin-bancari-costo-bonifico', 'keywords' => ['costo bonifico']],
                    ['name' => 'Imposta di bollo', 'slug' => 'fin-bancari-imposta-bollo', 'keywords' => ['imposta di bollo', 'bollo']],
                ],
            ],
            [
                'parent' => ['name' => 'Licenze Software', 'slug' => 'fin-licenze-software', 'type' => 'uscita', 'color' => '#7c3aed', 'icon' => 'key', 'description' => 'Abbonamenti e licenze software'],
                'children' => [
                    ['name' => 'AI / ChatGPT', 'slug' => 'fin-licenze-ai-chatgpt', 'keywords' => ['openai', 'chatgpt', 'claude', 'anthropic']],
                    ['name' => 'Chatbot', 'slug' => 'fin-licenze-chatbot', 'keywords' => ['manychat', 'chatbot']],
                    ['name' => 'Password Manager', 'slug' => 'fin-licenze-password-mgr', 'keywords' => ['bitwarden', '1password', 'lastpass']],
                    ['name' => 'Software automazioni', 'slug' => 'fin-licenze-automazioni', 'keywords' => ['make', 'zapier', 'n8n']],
                    ['name' => 'Software fatturazione', 'slug' => 'fin-licenze-fatturazione', 'keywords' => ['fatture in cloud', 'fic']],
                    ['name' => 'Software logistica', 'slug' => 'fin-licenze-logistica', 'keywords' => ['tookan', 'logistica software']],
                    ['name' => 'VPN', 'slug' => 'fin-licenze-vpn', 'keywords' => ['vpn', 'nordvpn']],
                    ['name' => 'Mail Server', 'slug' => 'fin-licenze-mail-server', 'keywords' => ['postmark', 'sendgrid', 'mailgun']],
                    ['name' => 'Firma contratti', 'slug' => 'fin-licenze-firma-contratti', 'keywords' => ['yousign', 'docusign', 'firma digitale']],
                ],
            ],
            [
                'parent' => ['name' => 'Marketing', 'slug' => 'fin-marketing', 'type' => 'uscita', 'color' => '#ec4899', 'icon' => 'megaphone', 'description' => 'Spese marketing e pubblicità'],
                'children' => [
                    ['name' => 'Adwords / Google Ads', 'slug' => 'fin-mkt-adwords', 'keywords' => ['google ads', 'adwords']],
                    ['name' => 'Meta Ads', 'slug' => 'fin-mkt-meta-ads', 'keywords' => ['meta', 'facebook ads', 'instagram ads']],
                    ['name' => 'TikTok Ads', 'slug' => 'fin-mkt-tiktok-ads', 'keywords' => ['tiktok', 'tik tok ads']],
                    ['name' => 'Offline Ads', 'slug' => 'fin-mkt-offline-ads', 'keywords' => ['volantini', 'manifesti', 'offline']],
                    ['name' => 'Sponsorizzazioni', 'slug' => 'fin-mkt-sponsorizzazioni', 'keywords' => ['sponsorizzazione']],
                    ['name' => 'Produzione contenuti', 'slug' => 'fin-mkt-contenuti', 'keywords' => ['produzione contenuti', 'video', 'foto']],
                    ['name' => 'Grafica', 'slug' => 'fin-mkt-grafica', 'keywords' => ['grafica', 'canva']],
                    ['name' => 'Graphic design', 'slug' => 'fin-mkt-graphic-design', 'keywords' => ['graphic design', 'designer']],
                    ['name' => 'Stampe', 'slug' => 'fin-mkt-stampe', 'keywords' => ['stampa', 'tipografia']],
                    ['name' => 'App Store Connect', 'slug' => 'fin-mkt-app-store', 'keywords' => ['app store connect', 'apple developer']],
                    ['name' => 'Google Play Store', 'slug' => 'fin-mkt-google-play', 'keywords' => ['google play', 'play store']],
                ],
            ],
            [
                'parent' => ['name' => 'Debiti passati', 'slug' => 'fin-debiti-passati', 'type' => 'uscita', 'color' => '#b91c1c', 'icon' => 'alert-triangle', 'description' => 'Debiti pregressi'],
                'children' => [],
            ],
            [
                'parent' => ['name' => 'Consulenze e professionisti', 'slug' => 'fin-consulenze', 'type' => 'uscita', 'color' => '#f59e0b', 'icon' => 'briefcase', 'description' => 'Consulenze professionali'],
                'children' => [
                    ['name' => 'Commercialista', 'slug' => 'fin-consulenze-commercialista', 'keywords' => ['commercialista', 'studio']],
                    ['name' => 'Consulenza legale', 'slug' => 'fin-consulenze-legale', 'keywords' => ['avvocato', 'legale']],
                    ['name' => 'Consulenza strategica / marketing', 'slug' => 'fin-consulenze-strategica', 'keywords' => ['consulenza strategica', 'consulenza marketing']],
                    ['name' => 'Rimborsi spese', 'slug' => 'fin-consulenze-rimborsi', 'keywords' => ['rimborso spese']],
                ],
            ],
            [
                'parent' => ['name' => 'Partner logistici', 'slug' => 'fin-partner-logistici', 'type' => 'uscita', 'color' => '#0ea5e9', 'icon' => 'truck', 'description' => 'Supporto alla consegna'],
                'children' => [
                    ['name' => 'Supporto alla consegna', 'slug' => 'fin-logistici-supporto', 'keywords' => ['partner logistico', 'supporto consegna']],
                ],
            ],
            [
                'parent' => ['name' => 'Imposte e contributi', 'slug' => 'fin-imposte', 'type' => 'uscita', 'color' => '#64748b', 'icon' => 'receipt', 'description' => 'Tasse, contributi e F24'],
                'children' => [
                    ['name' => 'F24 INPS / INAIL', 'slug' => 'fin-imposte-f24-inps', 'keywords' => ['f24 inps', 'f24 inail', 'inps', 'inail']],
                    ['name' => 'F24 R/A (Ritenuta d\'acconto)', 'slug' => 'fin-imposte-f24-ra', 'keywords' => ['ritenuta', 'r/a']],
                    ['name' => 'Tributi vari', 'slug' => 'fin-imposte-tributi', 'keywords' => ['tributo', 'iva', 'irap', 'ires']],
                ],
            ],
            [
                'parent' => ['name' => 'Varie e occasionali', 'slug' => 'fin-varie', 'type' => 'uscita', 'color' => '#6b7280', 'icon' => 'more-horizontal', 'description' => 'Spese varie e occasionali'],
                'children' => [],
            ],
            [
                'parent' => ['name' => 'Telefonia', 'slug' => 'fin-telefonia', 'type' => 'uscita', 'color' => '#0891b2', 'icon' => 'phone', 'description' => 'Costi telefonia'],
                'children' => [
                    ['name' => 'SIM per chatbot', 'slug' => 'fin-telefonia-sim-chatbot', 'keywords' => ['sim', 'chatbot sim']],
                ],
            ],
            [
                'parent' => ['name' => 'Sviluppo software', 'slug' => 'fin-sviluppo', 'type' => 'uscita', 'color' => '#7c3aed', 'icon' => 'code', 'description' => 'Costi sviluppo e infrastruttura tecnica'],
                'children' => [
                    ['name' => 'Hosting', 'slug' => 'fin-sviluppo-hosting', 'keywords' => ['siteground', 'render', 'hosting']],
                    ['name' => 'Hosting Opplà', 'slug' => 'fin-sviluppo-hosting-oppla', 'keywords' => ['hosting oppla']],
                    ['name' => 'Dominio acquisto', 'slug' => 'fin-sviluppo-dominio-acquisto', 'keywords' => ['dominio acquisto', 'registrazione dominio']],
                    ['name' => 'Dominio rinnovo', 'slug' => 'fin-sviluppo-dominio-rinnovo', 'keywords' => ['dominio rinnovo', 'rinnovo dominio']],
                    ['name' => 'Licenza sviluppo app iOS', 'slug' => 'fin-sviluppo-licenza-ios', 'keywords' => ['apple developer', 'licenza ios']],
                    ['name' => 'Mappe', 'slug' => 'fin-sviluppo-mappe', 'keywords' => ['google maps', 'mapbox']],
                    ['name' => 'Visualizzazione mappe', 'slug' => 'fin-sviluppo-vis-mappe', 'keywords' => ['leaflet', 'visualizzazione mappe']],
                    ['name' => 'Servizi Google', 'slug' => 'fin-sviluppo-servizi-google', 'keywords' => ['google cloud', 'firebase']],
                    ['name' => 'Raccolta / analisi dati', 'slug' => 'fin-sviluppo-analisi-dati', 'keywords' => ['analytics', 'raccolta dati']],
                    ['name' => 'Aggiornamenti real-time', 'slug' => 'fin-sviluppo-realtime', 'keywords' => ['pusher', 'websocket', 'ably']],
                    ['name' => 'Auto completamento indirizzi', 'slug' => 'fin-sviluppo-autocomplete', 'keywords' => ['places api', 'autocomplete']],
                    ['name' => 'Automazioni', 'slug' => 'fin-sviluppo-automazioni', 'keywords' => ['make.com', 'zapier']],
                    ['name' => 'Invio email', 'slug' => 'fin-sviluppo-invio-email', 'keywords' => ['postmark', 'sendgrid']],
                    ['name' => 'Notifiche push', 'slug' => 'fin-sviluppo-notifiche-push', 'keywords' => ['onesignal', 'push notification']],
                ],
            ],
            [
                'parent' => ['name' => 'Spedizioni / Logistica', 'slug' => 'fin-spedizioni', 'type' => 'uscita', 'color' => '#059669', 'icon' => 'package', 'description' => 'Costi spedizioni e corrieri'],
                'children' => [
                    ['name' => 'UPS', 'slug' => 'fin-spedizioni-ups', 'keywords' => ['ups', 'corriere ups']],
                    ['name' => 'Altri corrieri', 'slug' => 'fin-spedizioni-altri', 'keywords' => ['dhl', 'gls', 'bartolini', 'sda']],
                ],
            ],
            [
                'parent' => ['name' => 'Dispositivi POS', 'slug' => 'fin-dispositivi-pos', 'type' => 'uscita', 'color' => '#d97706', 'icon' => 'credit-card', 'description' => 'Costi dispositivi POS'],
                'children' => [
                    ['name' => 'Acquisto / noleggio POS', 'slug' => 'fin-pos-acquisto', 'keywords' => ['pos', 'terminale', 'sumup', 'vivawallet']],
                ],
            ],
            // ENTRATE
            [
                'parent' => ['name' => 'Entrate operative', 'slug' => 'fin-entrate-operative', 'type' => 'entrata', 'color' => '#10b981', 'icon' => 'trending-up', 'description' => 'Entrate da attività operativa'],
                'children' => [
                    ['name' => 'Commissioni consegne', 'slug' => 'fin-entrate-commissioni', 'keywords' => ['commissione', 'consegna']],
                    ['name' => 'Abbonamenti clienti', 'slug' => 'fin-entrate-abbonamenti', 'keywords' => ['abbonamento', 'canone']],
                    ['name' => 'Fatturazione servizi', 'slug' => 'fin-entrate-fatturazione', 'keywords' => ['fattura', 'servizio']],
                ],
            ],
            [
                'parent' => ['name' => 'Altre entrate', 'slug' => 'fin-altre-entrate', 'type' => 'entrata', 'color' => '#06b6d4', 'icon' => 'arrow-down', 'description' => 'Altre fonti di entrata'],
                'children' => [
                    ['name' => 'Versamenti soci', 'slug' => 'fin-entrate-versamenti-soci', 'keywords' => ['versamento', 'socio']],
                    ['name' => 'Rimborsi ricevuti', 'slug' => 'fin-entrate-rimborsi', 'keywords' => ['rimborso ricevuto']],
                ],
            ],
        ];

        foreach ($hierarchicalCategories as $group) {
            $parentData = array_merge($group['parent'], [
                'is_active' => true,
                'keywords' => [],
            ]);

            $parent = AccountingCategory::updateOrCreate(
                ['slug' => $parentData['slug']],
                $parentData
            );

            foreach ($group['children'] as $child) {
                AccountingCategory::updateOrCreate(
                    ['slug' => $child['slug']],
                    [
                        'name' => $child['name'],
                        'slug' => $child['slug'],
                        'type' => $group['parent']['type'],
                        'parent_id' => $parent->id,
                        'color' => $group['parent']['color'],
                        'keywords' => $child['keywords'] ?? [],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
