# OPPLA One Manager

Sistema centralizzato di gestione dei processi aziendali per OPPLA — piattaforma PWA (Progressive Web App) che integra CRM, fatturazione, pagamenti, logistica, contratti e molto altro.

## Tech Stack

| Layer | Tecnologie |
|-------|-----------|
| **Backend** | Laravel 12, PHP 8.2+, SQLite (dev) / MySQL (prod), Laravel Sanctum |
| **Frontend** | React 18, TypeScript 5.3, Vite 5, TailwindCSS 3.4, Zustand |
| **Integrazioni** | Stripe, Fatture in Cloud (SDI), Tookan, OPPLA Admin Panel, VivaWallet |
| **Infrastruttura** | Nginx, Supervisor, Let's Encrypt, PWA con Service Worker |

## Architettura

Monorepo con backend API REST e frontend SPA separati:

```
onemanager/
├── backend/                    # Laravel 12 API
│   ├── app/
│   │   ├── Http/Controllers/   # 49 controller (23 main + 26 API)
│   │   ├── Models/             # 57 modelli Eloquent
│   │   ├── Services/           # 31 servizi di business logic
│   │   ├── Jobs/               # Queue jobs (fatturazione, import)
│   │   └── ...
│   ├── database/
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/
│   ├── routes/api.php          # Tutte le API routes
│   └── tests/                  # PHPUnit test suite
├── frontend/                   # React PWA
│   ├── src/
│   │   ├── components/         # Componenti riutilizzabili
│   │   │   ├── riders/         # Componenti rider (card, map, filters)
│   │   │   └── ui/             # Componenti UI base
│   │   ├── pages/              # 17 pagine principali
│   │   ├── contexts/           # React contexts (Auth)
│   │   ├── hooks/              # Custom hooks
│   │   ├── services/           # API client e utilities
│   │   └── config/             # Configurazione app
│   └── public/                 # PWA manifest, service worker
├── deploy-backend.sh           # Script deploy produzione
├── nginx-production.conf       # Config Nginx
└── supervisor.conf             # Process manager config
```

## Moduli Funzionali

### CRM & Clienti
Gestione completa dei clienti OPPLA con classificazione per tipo (Partner OPPLA, Extra, Consumatori). Include sistema Lead con pipeline di conversione, Opportunita con gestione stadi (won/lost), Attivita CRM schedulabili, e Campagne con enrollment membri.

**Controller**: `ClientController`, `LeadController`, `OpportunityController`, `ActivityController`, `CampaignController`

### Fatturazione Attiva
Due tipologie di fatturazione:
- **Ordinaria**: generata dal 26 del mese precedente al 6 del mese corrente per subscription fee Stripe
- **Differita**: generata a fine mese aggregando consegne, fee POS, fee delivery

Supporta generazione numeri fattura con lock DB, invio a Fatture in Cloud, trasmissione SDI, note di credito, download PDF (da FIC o generazione locale DOMPDF).

**Servizi**: `InvoicingService`, `StripeOrdinaryInvoicingService`, `PaymentInvoicingService`
**Controller**: `InvoiceController`, `StripeOrdinaryInvoiceController`

### Fatturazione Passiva & SDI
Integrazione completa con Fatture in Cloud via OAuth 2.0:
- Sync documenti ricevuti (fatture passive)
- Invio fatture attive al SDI
- Verifica stato SDI (accettata/rifiutata/in attesa)
- Download XML e PDF fatture

**Servizio**: `FattureInCloudService` (OAuth 2.0, ~66KB)
**Controller**: `FattureInCloudController`

### Pagamenti & Riconciliazione
- Import transazioni Stripe (balance transactions + application fees)
- Import CSV da banca
- Aggregazione per cliente e per destinazione
- Fatturazione differita commissioni Stripe per partner
- Report mensili Stripe con classificazione automatica transazioni (charge, payout, refund, fee, application_fee)
- Export Excel per commercialista

**Servizio**: `StripeReportService`, `PaymentImportService`
**Controller**: `PaymentController`, `StripeReportController`

### Ordini & Consegne
Sincronizzazione ordini dalla piattaforma OPPLA con statistiche, filtri per stato/data/ristorante, e gestione consegne con calcolo fee.

**Controller**: `OrderController`, `DeliveryController`

### Gestione Rider (Tookan)
Integrazione con Tookan per fleet management:
- Lista rider con dati real-time (posizione, stato, task attivi)
- Sync automatico con fallback su DB locale se API non disponibile
- Gestione team (creazione, assegnazione rider)
- Assegnazione task e notifiche

**Servizio**: `TookanService` (cache 30s, fallback locale)
**Controller**: `RiderController`

### Contratti & Firma Digitale
Sistema completo di gestione contratti:
- Template contratti personalizzabili con variabili
- Generazione PDF
- Firma digitale con OTP via email
- Workflow: bozza → inviato → firmato → attivo → scaduto
- Rinnovo automatico e manuale
- Pagina pubblica di firma (`/sign/:token`)

**Controller**: `ContractController`, `ContractTemplateController`, `ContractSignatureController`, `ContractRenewalController`

### Onboarding Ristoranti
Wizard in 6 step per onboarding nuovi ristoranti partner:
1. Dati proprietario
2. Dati ristorante
3. Upload copertina
4. Configurazione delivery (zone, orari)
5. Configurazione fee
6. Finalizzazione

**Controller**: `OnboardingFlowController`
**Componente**: `OnboardingModalNew.tsx`

### Partner Protection
Sistema di protezione partner con gestione incidenti:
- **Tipi incidente**: ritardo consegna, articolo dimenticato, pacco ingombrante non segnalato
- **Penalita automatiche**: calcolate in base a regole configurabili per ristorante
- **Time slot** e **zone di consegna** per ristorante
- **Validazione ordini** contro regole di protezione

**Servizi**: `PartnerIncidentService`, `PartnerValidationService`
**Controller**: `PartnerProtectionController`
**Modelli**: `PartnerIncident`, `PartnerPenalty`, `PartnerProtectionSettings`, `RestaurantDeliveryZone`, `RestaurantTimeSlot`

### Zone di Consegna
Gestione zone delivery con integrazione mappa (Mapbox GL):
- CRUD zone con poligoni GeoJSON
- Sync bidirezionale con OPPLA Filament
- Visualizzazione su mappa interattiva

**Controller**: `DeliveryZoneController`

### Menu
Gestione menu ristoranti con import/export CSV, categorie, bulk update, e storico import.

**Controller**: `MenuController`

### Task Management
Board stile Kanban con task assegnabili a utenti, filtri, statistiche, drag & drop.

**Controller**: `TaskController`, `TaskBoardController`

### Dashboard KPI
Dashboard economica unificata con:
- KPI principali (fatturato, margini, flusso cassa)
- Dashboard operativa consegne
- Statistiche rider real-time

**Controller**: `DashboardController`, `DeliveryDashboardController`

### Notifiche Push (PWA)
Sistema notifiche push via VAPID per PWA con subscription management.

**Controller**: `PushNotificationController`

### Fornitori
Gestione fornitori e fatture passive con tracking scadenze, pagamenti in scadenza/scaduti, upload allegati, e sync da Fatture in Cloud.

**Controller**: `SupplierController`, `SupplierInvoiceController`

### Email Automation CRM
Sequenze email automatiche con step configurabili, enrollment lead/clienti, pause/resume, e statistiche invii.

**Controller**: `EmailAutomationController`

## Integrazioni Esterne

### Stripe
- Payment processing e subscriptions (Laravel Cashier)
- Balance transactions sync
- Application fees (commissioni piattaforma)
- Webhooks per dispute e payouts
- Report mensili transazioni

**Variabili env**: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`

### Fatture in Cloud (SDI)
- OAuth 2.0 authentication flow
- Creazione e invio fatture elettroniche
- Trasmissione SDI
- Sync fatture passive
- Download PDF/XML

**Variabili env**: `FIC_CLIENT_ID`, `FIC_CLIENT_SECRET`, `FIC_REDIRECT_URI`, `FIC_BASE_URL`

### Tookan
- Fleet management API
- Rider tracking real-time
- Task assignment
- Team management

**Variabili env**: `TOOKAN_API_KEY`

### OPPLA Admin Panel (Filament)
- Sync dati ristoranti, ordini, utenti
- GraphQL API per query
- Web scraping fallback per operazioni bulk
- Sync automatico 3x/giorno (08:00, 14:00, 20:00)

**Variabili env**: `OPPLA_ADMIN_URL`, `OPPLA_ADMIN_EMAIL`, `OPPLA_ADMIN_PASSWORD`, `OPPLA_GRAPHQL_URL`

### VivaWallet
- Gateway pagamenti alternativo

**Variabili env**: `VIVAWALLET_CLIENT_ID`, `VIVAWALLET_CLIENT_SECRET`, `VIVAWALLET_MERCHANT_ID`

## Setup Ambiente di Sviluppo

### Prerequisiti

- PHP 8.2+
- Composer
- Node.js 18+
- npm
- SQLite (sviluppo) o MySQL (produzione)

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Database SQLite (default per sviluppo)
touch database/database.sqlite
php artisan migrate
php artisan db:seed

# Avviare server
php artisan serve    # http://localhost:8000
```

### Frontend

```bash
cd frontend
npm install
npm run dev          # http://localhost:5173
```

### Sviluppo concorrente (backend + frontend + queue + logs)

```bash
cd backend
composer dev
```

Questo avvia in parallelo: server Laravel, queue listener, Pail (log viewer), e Vite dev server.

### Configurazione .env

Variabili principali da configurare in `backend/.env`:

```env
# Database
DB_CONNECTION=sqlite

# Stripe
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

# Fatture in Cloud (OAuth 2.0)
FIC_CLIENT_ID=xxx
FIC_CLIENT_SECRET=xxx
FIC_REDIRECT_URI=${APP_URL}/api/fatture-in-cloud/callback

# Tookan
TOOKAN_API_KEY=xxx

# OPPLA Admin
OPPLA_ADMIN_URL=https://api.oppla.delivery
OPPLA_ADMIN_EMAIL=xxx
OPPLA_ADMIN_PASSWORD=xxx

# PWA Push Notifications
VAPID_PUBLIC_KEY=xxx
VAPID_PRIVATE_KEY=xxx
```

## Database e Migrazioni

Il progetto usa SQLite in sviluppo e MySQL in produzione.

```bash
# Eseguire migrazioni
cd backend
php artisan migrate

# Rollback ultima migrazione
php artisan migrate:rollback

# Reset completo (ATTENZIONE: cancella tutti i dati)
php artisan migrate:fresh --seed
```

### Modelli principali

`Client`, `Restaurant`, `Invoice`, `InvoiceItem`, `Payment`, `Delivery`, `Order`, `PosOrder`, `Contract`, `Task`, `TaskBoard`, `Rider`, `Lead`, `Opportunity`, `Supplier`, `SupplierInvoice`, `PartnerIncident`, `PartnerPenalty`, `PartnerProtectionSettings`, `RestaurantDeliveryZone`, `RestaurantTimeSlot`, `DeliveryZone`, `FeeClass`, `BankAccount`, `BankTransaction`

## API Reference

Tutte le API sono sotto `/api`. Autenticazione via Bearer token (Laravel Sanctum).

### Pubbliche (no auth)

| Metodo | Endpoint | Descrizione |
|--------|---------|-------------|
| POST | `/login` | Login utente |
| POST | `/register` | Registrazione |
| POST | `/webhooks/stripe` | Webhook Stripe |
| POST | `/webhooks/fatture-in-cloud` | Webhook FIC |
| GET | `/delivery-zones` | Zone delivery (per onboarding) |
| GET | `/partners` | Lista partner (per onboarding) |
| GET/POST | `/contracts/sign/:token/*` | Firma contratti pubblica |
| POST | `/onboarding` | Onboarding ristorante |
| GET | `/fatture-in-cloud/authorize` | OAuth FIC |
| GET | `/fatture-in-cloud/callback` | Callback OAuth FIC |

### Protette (auth:sanctum)

**Auth**: `POST /logout`, `GET /me`, `GET /users`

**Clienti**: `GET|POST|PUT|DELETE /clients`, `GET /clients-stats`, `POST /clients/import/csv`, `POST /clients/import/json`

**Partner**: `GET|PUT|DELETE /partners`, `GET /partners-stats`, `POST /partners/:id/assign-client`

**Ristoranti**: `GET /restaurants/unassigned`, `POST /restaurants/assign`, `POST /restaurants/close-period`

**Fatture**: `GET|POST|PUT|DELETE /invoices`, `POST /invoices/:id/send-sdi`, `POST /invoices/:id/retry-next-day`, `POST /invoices/bulk-update-dates`, `POST /invoices/preview-from-payments`, `POST /invoices/generate-from-payments`, `GET /invoices/:id/pdf`

**Pagamenti**: `GET|POST|PUT|DELETE /payments`, `POST /payments/import-csv`, `POST /payments/import-stripe-commissions`, `GET /payments/aggregate-by-client`, `GET /payments/application-fees`

**Stripe**: `POST /stripe/sync`, `POST /stripe/refund`, `POST /stripe/ordinary-invoices/generate/:year/:month`

**Stripe Report**: `GET /stripe-report/:year/:month`, `GET /stripe-report/:year/:month/export`

**Commissioni**: `POST /payments/commission-invoices/pregenerate/:year/:month`, `POST /payments/commission-invoices/generate/:year/:month`

**Ordini**: `GET /orders`, `GET /orders/stats`, `POST /orders/sync`

**Consegne**: `GET|POST|PUT|DELETE /deliveries`, `GET /deliveries/invoices/pregenerate`

**Rider**: `GET /riders`, `GET /riders/realtime`, `POST /riders/sync-now`, `POST /riders`, `GET|POST /riders/teams`, `POST /riders/notify`, `POST /riders/assign-task`

**Contratti**: `GET|POST|PUT|DELETE /contracts`, `POST /contracts/:id/send-for-signature`, `POST /contracts/:id/activate|terminate|renew`, `GET /contracts/:id/pdf/download`

**Template**: `GET|POST|PUT|DELETE /contract-templates`, `POST /contract-templates/:id/preview`

**Rinnovi**: `GET /contracts/renewals/stats|expiring|expired`, `POST /contracts/:id/manual-renew`

**Onboarding**: `POST /onboarding/step-1-owner` ... `POST /onboarding/finalize`

**Partner Protection**: `GET /partner-protection/incidents`, `POST /partner-protection/incidents/delay|forgotten-item|bulky-unmarked`, `PUT /partner-protection/incidents/:id/resolve`, `GET /partner-protection/penalties`, `POST /partner-protection/penalties/:id/waive`, `POST /partner-protection/validate-order`

**CRM Lead**: `GET|POST|PUT|DELETE /crm/leads`, `POST /crm/leads/:id/convert-to-client`

**CRM Opportunita**: `GET|POST|PUT|DELETE /crm/opportunities`, `POST /crm/opportunities/:id/mark-as-won|lost`

**CRM Attivita**: `GET|POST|PUT|DELETE /crm/activities`, `POST /crm/activities/:id/complete`

**CRM Campagne**: `GET|POST|PUT|DELETE /crm/campaigns`, `POST /crm/campaigns/:id/members`

**Email Automation**: `GET|POST|PUT|DELETE /crm/email-sequences`, `POST /crm/email-sequences/:id/activate|pause|enroll`

**Fatture in Cloud**: `GET /fatture-in-cloud/status`, `GET /fatture-in-cloud/invoices|clients|suppliers`, `POST /fatture-in-cloud/sync-passive-invoices`

**Task**: `GET|POST|PUT|DELETE /tasks`, `GET|POST|PUT|DELETE /task-boards`

**Contabilita**: `GET /accounting/dashboard`, `POST /accounting/import-statement`, `POST /accounting/auto-reconcile`

**Dashboard**: `GET /dashboard/unified`, `GET /dashboard/economic-kpis`, `GET /dashboard/delivery-ops`

**Fornitori**: `GET|POST|PUT|DELETE /suppliers`, `GET|POST|PUT|DELETE /supplier-invoices`, `POST /supplier-invoices/sync-fic`

**Menu**: `GET|POST|PUT|DELETE /menus`, `POST /menus/import`, `GET /menus/export`

**Zone Delivery**: `GET /delivery-zones/map`, `POST /delivery-zones/sync`, `POST /delivery-zones/push-to-oppla`

**OPPLA**: `GET /oppla/clients|users|restaurants`, `POST /oppla/sync/database|all`

**Report**: `GET /reports/invoicing`, `GET /reports/invoicing/export`

**Push Notifications**: `POST|DELETE /push-subscriptions`

## Deploy Produzione

### Server
- **Host**: pedro.oppla.club
- **Stack**: Nginx + PHP-FPM + Supervisor
- **SSL**: Let's Encrypt (auto-renewal)
- **Path**: `/var/www/onemanager`

### Comandi Deploy

```bash
# Backend
cd /var/www/onemanager/backend
git pull origin master
composer install --optimize-autoloader --no-dev
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan migrate --force

# Frontend
cd /var/www/onemanager/frontend
npm install
npm run build

# Riavvio servizi
sudo supervisorctl restart all
sudo systemctl reload nginx
```

### Supervisor (queue worker)

Il file `supervisor.conf` configura il worker per i job in coda (fatturazione automatica, import, invio SDI).

### Nginx

Il file `nginx-production.conf` gestisce:
- Reverse proxy al backend Laravel
- Serve i file statici del frontend build
- SSL termination
- Gzip compression

## Testing

### Backend (PHPUnit)

```bash
cd backend

# Eseguire tutti i test
php artisan test

# Test con copertura
php artisan test --coverage

# Solo unit test
php artisan test --testsuite=Unit

# Solo feature test
php artisan test --testsuite=Feature

# Test specifico
php artisan test --filter=InvoicingServiceTest
```

Configurazione in `backend/phpunit.xml` — usa SQLite in-memory per i test.

### Frontend (Vitest + React Testing Library)

```bash
cd frontend

# Eseguire tutti i test
npm run test

# Test con watch mode
npm run test -- --watch

# Test con copertura
npm run test:coverage
```

## Workflow di Sviluppo

```bash
# Branch principale
git checkout master

# Nuovo feature branch
git checkout -b feature/nome-feature

# Lint frontend
cd frontend && npm run lint

# Build frontend (verifica TypeScript)
cd frontend && npm run build

# Test backend
cd backend && php artisan test
```

## Sicurezza

- Autenticazione API via Bearer token (Laravel Sanctum)
- RBAC con Spatie Permissions
- Audit trail con Spatie Activity Log
- Webhook signature verification (Stripe)
- OTP via email per firma contratti
- CORS configurato per domini autorizzati

## Licenza

Proprietary - OPPLA S.R.L. 2025-2026
