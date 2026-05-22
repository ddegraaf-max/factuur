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
php artisan migrate --force --seed
```
Dit maakt alle tabellen aan én vult demo-data. De 500 verdwijnt.

## Inloggen

Na een geslaagde deploy met database:
```
E-mail:    demo@easyinvoice.test
Wachtwoord: password
```

De seeder is idempotent: hij vult demo-data alleen de EERSTE keer. Bij volgende
deploys slaat hij zichzelf over (geen dubbele data, geen crash).

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
