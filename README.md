# EasyInvoice

Een complete facturatie-applicatie voor Nederlandse ondernemers, gebouwd met Laravel 11 + Inertia + Vue 3. Volledig functioneel met een echte database. Inclusief authenticatie, tweestapsverificatie, BTW-berekening, creditnota's, incasso, statistieken, huisstijl-aanpassing en een AI-assistent (EASY).

## Functies

**Verkoop**
- Facturen versturen met automatische BTW-berekening (21% / 9% / 0% per regel)
- Eigen factuurnummerreeks per jaar, atomair (database lock)
- Klanten en producten beheren
- Betalingen registreren (bankoverschrijving, iDEAL, contant, etc.)
- Creditnota's met eigen `C-{jaar}-XXXX` nummerreeks
- Incasso-overdracht aan Armaere Gerechtsdeurwaarders (`ARM-{jaar}-XXXX`)
- Bijlagen uploaden bij facturen (PDF, beeld, Office)

**Rapporten**
- Klantomzet per jaar incl/excl BTW
- Top 10 met staafdiagram
- Per-klant totalen met creditnota-verrekening

**Instellingen**
- Bedrijfsgegevens (KVK, BTW, IBAN, etc.)
- Huisstijl: logo upload, kleur-presets, template (modern/klassiek/minimaal), lettertype, live preview
- Nummering per type (facturen, klanten, producten)
- Herinneringen-schema met live tijdslijn
- Tweestapsverificatie (Google Authenticator, Authy, 1Password) met backup-codes

**AI-assistent EASY**
- Proactieve inzichten (achterstallig, BTW-deadline, incomplete klanten)
- Chat met natural language (openstaand, achterstallig, topklanten, etc.)

**Beveiliging**
- 2FA via TOTP (RFC 6238)
- Backup-codes voor noodgeval
- Multi-tenant data-isolatie (global scope op company_id)
- AVG-compliant (Nederlandse data, geen tracking)

## Stack

- **Laravel 11** (PHP 8.2+)
- **Inertia.js 1.x** + **Vue 3** (Composition API)
- **DomPDF** voor PDF-generatie
- **pragmarx/google2fa** voor 2FA
- **SQLite** (default, geen MySQL/PostgreSQL nodig) — kan ook MySQL/PostgreSQL
- **Vite** voor frontend-build

## Installatie

### Vereisten

- PHP 8.2 of hoger
- Composer 2.x
- Node.js 18+ en npm
- (Optioneel) MySQL 8 of PostgreSQL 13+ als je niet SQLite wilt

### Stap 1 — Code installeren

```bash
unzip easyinvoice-mvp.zip
cd easyinvoice
composer install
npm install
```

### Stap 2 — Environment

```bash
cp .env.example .env
php artisan key:generate
```

Standaard gebruikt EasyInvoice **SQLite** — geen extra configuratie nodig. Wil je MySQL of PostgreSQL? Pas `.env` aan:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=easyinvoice
DB_USERNAME=root
DB_PASSWORD=
```

### Stap 3 — Database

```bash
# Maak (voor SQLite) een leeg bestand aan
touch database/database.sqlite

# Migrate + seed met demo-data
php artisan migrate --seed
```

### Stap 4 — Storage

```bash
php artisan storage:link
```

Hierdoor zijn geüploade logo's en bijlagen bereikbaar via `/storage/...`.

### Stap 5 — Build assets + start server

In één terminal:

```bash
php artisan serve
```

In een tweede terminal (voor frontend hot-reload):

```bash
npm run dev
```

Open vervolgens [http://localhost:8000](http://localhost:8000).

## Demo-inloggegevens

```
E-mail:     demo@easyinvoice.test
Wachtwoord: password
```

De seeder maakt:
- 1 demo-bedrijf "Vries Design B.V."
- 1 gebruiker
- 10 klanten (mix van zakelijk + particulier)
- 6 producten
- 15 facturen in diverse statussen (concept, verstuurd, betaald, deels betaald, achterstallig)
- 1 dossier bij incasso (Armaere)
- 1 voorbeeld-creditnota

## Productie-deployment

### Railway (aanbevolen — zie DEPLOYMENT.md voor details)

Deze repo is geconfigureerd voor **Railpack**, de zero-config builder van Railway. Railpack detecteert de Laravel-app automatisch (via het `artisan`-bestand), installeert PHP + Composer + de frontend, en start de server met FrankenPHP.

**Belangrijk:** zet in het Railway-dashboard de Builder op **Railpack** (niet Dockerfile), en voeg de environment-variabele `APP_KEY` toe. De volledige stap-voor-stap instructies en checklist staan in **`DEPLOYMENT.md`**.

**Belangrijke environment-variabelen:**

| Variabele | Standaard | Uitleg |
|-----------|-----------|--------|
| `APP_KEY` | (verplicht zetten) | Genereer met `php artisan key:generate --show`. Railpack genereert deze niet zelf. |
| `DB_CONNECTION` | sqlite | `pgsql` of `mysql` voor productie. |
| `SEED_DEMO_DATA` | false | Op `true` voor demo-data bij eerste start. |

> **Let op bij SQLite op cloud-platforms:** SQLite slaat data op in een bestand. Op platforms met een ephemeral filesystem (zoals Railway/Render zonder persistent volume) verdwijnt de data bij elke redeploy. Voor echte productie: koppel een PostgreSQL- of MySQL-database via de `DB_*`-variabelen.

### Alternatief: handmatige deployment

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan storage:link
```

Zorg dat `storage/` en `bootstrap/cache/` schrijfbaar zijn voor de webserver-user. Wijs de webserver-document-root naar `public/`.

### Multi-tenant

Elke `Company` heeft zijn eigen geïsoleerde data via Eloquent global scopes (`company_id`). Een gebruiker ziet altijd alleen de data van zijn eigen bedrijf.

## Architectuur

```
app/
├── Http/Controllers/         Web controllers (Inertia rendering)
│   ├── Auth/                 Login, register, 2FA challenge
│   ├── DashboardController
│   ├── InvoiceController
│   ├── CustomerController
│   ├── ProductController
│   ├── CreditNoteController
│   ├── IncassoController
│   ├── AttachmentController
│   ├── StatsController
│   ├── SettingsController    Company / Brand / Numbering / Reminders
│   └── SecurityController    2FA setup, recovery codes
├── Models/                   Eloquent models met company-scoping
└── Services/                 Domain logic
    ├── VatCalculator         BTW-berekening (PHP_ROUND_HALF_UP)
    ├── InvoiceManager        Create/send/update flow
    ├── InvoiceNumberGenerator Atomair nummeren via DB lock
    ├── CreditNoteService     Crediteren met eigen reeks
    ├── IncassoService        Overdracht naar Armaere
    └── EasyInsightsService   AI-assistent insights

database/
├── migrations/               Schema (12 migrations)
└── seeders/                  Demo data

resources/
├── js/
│   ├── Pages/                Inertia pages
│   ├── Layouts/              AppLayout, AuthLayout
│   └── Components/           EasyAgent, Toast
└── views/
    └── pdf/                  Blade PDF-templates
```

## Belangrijk om te weten

**SQLite vs MySQL** — De standaard SQLite-database is prima voor development en kleine deployments (tot ±100 facturen/dag). Voor productie met meerdere gebruikers raden we MySQL of PostgreSQL aan.

**E-mails** — Het versturen van facturen, herinneringen en aanmaningen is geconfigureerd via Laravel's queue + Mailable systeem, maar de standaard mail-driver is `log` — uitgaande mails worden opgeslagen in `storage/logs/laravel.log`. Voor productie configureer SMTP, SES, Mailgun of Postmark via `.env`.

**iDEAL/Mollie betalingen** — Nog niet ingebouwd. Aanbeveling: `mollie/laravel-mollie` koppelen aan de Payment-controller voor automatische verwerking.

**Peppol/UBL** — Nog niet ingebouwd, op de roadmap.

**Herinneringen-job** — De settings worden opgeslagen, maar de scheduled job die ze daadwerkelijk verzendt moet nog worden gecodeerd. Stub:

```bash
# In app/Console/Kernel.php
$schedule->command('reminders:dispatch')->dailyAt('08:00');
```

## Veelvoorkomende issues

**"could not find driver" bij `php artisan migrate`**
Installeer de SQLite of PostgreSQL/MySQL extensies voor PHP:
```bash
sudo apt install php-sqlite3      # of php-mysql / php-pgsql
```

**Frontend assets laden niet** — Controleer dat `npm run dev` draait. Voor productie `npm run build` gevolgd door `php artisan config:cache`.

**Logo wordt niet getoond** — Voer `php artisan storage:link` uit.

**2FA verifieert niet** — Controleer dat de tijd op je server klopt (TOTP gebruikt 30-seconden vensters). `timedatectl` op Linux.

## Licentie

Voor demo-doeleinden, geen specifieke licentie. Pas naar wens aan voor je eigen behoefte.

## Testen

```bash
php artisan test
```

Inclusief unit tests voor `VatCalculator` (BTW-rounding) en factuurnummer-generatie.
