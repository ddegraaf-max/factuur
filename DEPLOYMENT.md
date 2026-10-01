# ★★★ De app DRAAIT! — laatste stap: database

## Waar we staan

De container start nu correct (geen heroku-fout meer), de webserver draait, Laravel
reageert. Je ziet een **HTTP 500** — dat is een runtime-fout in de app zelf, GEEN
build- of deploy-probleem meer. We zijn er bijna.

## De oorzaak: geen werkende database

De app gebruikt standaard **SQLite**, maar op Railway:
- bestaat het SQLite-bestand niet automatisch → migraties falen → elke pagina geeft 500
- is het filesystem ephemeral → SQLite-data zou toch verdwijnen bij elke redeploy

**De oplossing is PostgreSQL** — dat lost de 500 op én geeft je blijvende data.

## Stap 1 — (optioneel) Bevestig de fout met APP_DEBUG

Railway → Variables → zet `APP_DEBUG=true`, redeploy, ververs de pagina.
Je ziet dan de echte fout (waarschijnlijk "no such table" of "database does not exist").
Zet `APP_DEBUG` daarna weer op `false`.

## Stap 2 — Voeg PostgreSQL toe (de echte fix)

1. In je Railway-project: **+ New** → **Database** → **Add PostgreSQL**
2. Railway maakt een Postgres-service aan (meestal "Postgres" genaamd)
3. Ga naar je **app-service** → **Variables** → **Raw Editor** → plak erbij:

```
DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}
```

(Heet je Postgres-service anders dan "Postgres"? Vervang dan die naam in de `${{...}}`.)

4. **Redeploy.**

Bij de deploy draait automatisch (via `railway.json` preDeployCommand):
```
php artisan migrate --force
```
Dit maakt alle tabellen aan. De 500 verdwijnt.

**Geen `--seed` erbij zetten.** Hier stond eerder `migrate --force --seed`, en
op 28-08-2026 zette die seeder na een deploy de voorbeeldadministratie "Vries
Design B.V." in de productiedatabase — inclusief een incassodossier dat naar de
incassopartner gemaild werd, en een gebruiker met een wachtwoord dat in deze
openbare repo stond. De seeder weigert nu zelf te draaien in productie, en
`--seed` is uit `railway.json` gehaald. Voorbeelddata hoort bij lokaal
ontwikkelen; de publieke demo op de site loopt via `DemoDataBuilder`, dat
afgeschermde omgevingen maakt met `is_demo` en een vervaldatum.

## Inloggen

Op een nieuwe productieomgeving maak je zelf het eerste account aan via de
registratiepagina. Er is geen standaardaccount en geen standaardwachtwoord.

Lokaal maakt de seeder `demo@easyinvoice.test` met een willekeurig wachtwoord
dat hij in je terminal zet — zie README.md.

## Belangrijke correctie

In eerdere instructies noemde ik een variabele `SEED_DEMO_DATA=true`. **Die deed
niets** (er was geen code die hem las — mijn fout). Je kunt die variabele
verwijderen. Het seeden gebeurt nu via `migrate --force --seed` in de
preDeployCommand, met de idempotente guard in de seeder.

## Variabelen-overzicht (app-service)

| Variabele | Waarde |
|-----------|--------|
| `APP_KEY` | (jouw base64-sleutel, in Railway Variables) |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://easyinvoice-production-3ab7.up.railway.app` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `${{Postgres.PGHOST}}` |
| `DB_PORT` | `${{Postgres.PGPORT}}` |
| `DB_DATABASE` | `${{Postgres.PGDATABASE}}` |
| `DB_USERNAME` | `${{Postgres.PGUSER}}` |
| `DB_PASSWORD` | `${{Postgres.PGPASSWORD}}` |

## Eigen domein (easyinvoice.nl)

Later: Railway → Settings → Networking → Custom Domain → voeg `easyinvoice.nl` toe
en volg de DNS-instructies (CNAME naar de Railway-URL).
