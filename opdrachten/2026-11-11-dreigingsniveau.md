# Dreigingsniveau ("Defcon") ook in EasyInvoice

**Open.** Opgesteld op 11 november 2026, als vervolg op V6 in het VvE/NextPro-pakket (repo VVE-WEBSITE, `ontwerp/opdrachten/2026-11-11-verkoopklaar.md`, gebouwd in branch `v6-veiligheid`). Daniël: "goede veiligheid, een soort Defcon-systeem … liever een blokkade te veel dan data op straat." Alleen de opdracht; nog niet bouwen.

## Wat er moet komen

Dezelfde vijf standen als in het VvE-pakket, met dezelfde namen, zodat één beslissing op alle platforms hetzelfde betekent:

| Niveau | In EasyInvoice |
|---|---|
| 5 Normaal | zoals nu |
| 4 Verhoogd | strengere rate limits (de helft); elke export (CSV, auditfile/XAF, PDF-bundels, boekhoudexport) en download van meer dan N facturen opnieuw met de tweede stap bevestigen; alarm bij elk ongewoon patroon |
| 3 Hoog | iedereen opnieuw inloggen met tweestap; bulk-exports alleen na goedkeuring door het platform; de API en de MCP-koppeling alleen lezen; nieuwe inlog vanaf een onbekend apparaat of land pas na bevestiging per mail |
| 2 Ernstig | alles alleen lezen; klantportaal dicht met een nette melding; API, MCP en webhooks dicht, **behalve betaalwebhooks** (Mollie/Stripe blijven binnenkomen) |
| 1 Noodstop | alleen een onderhoudspagina, alle sessies en API-tokens ongeldig, alleen het platformbeheer vanaf `ADMIN_IP_ALLOWLIST` |

- **Zetten**: in het platformbeheer met tweestap en een reden; noodweg via env `DREIGINGSNIVEAU` (de strengste van env en database geldt). Omlaag alleen met de hand.
- **Logboek**: elke wijziging in het auditlog en per mail naar Daniël; band bovenaan het platformbeheer.
- **Automatisch naar Verhoogd** bij: veel mislukte logins over veel accounts; een gebruiker die in korte tijd veel facturen/klanten/IBAN's opent of exporteert; een nieuw API-token (of MCP-koppeling) dat veel leest; sessiefouten op schaal. Met een maatregel op dat account of token (tijdelijk blokkeren, tweestap opnieuw, token een uur dicht).
- **Het koppelvlak met het VvE-pakket blijft werken** (Daniël, 12 november 2026): aan de VvE-kant blijft het betaaldeel (`/koppelvlak/abonnement…`, `/koppelvlak/bundels…`) open tot en met Ernstig, en `POST /koppelvlak/abonnement/betaald` en `POST /koppelvlak/bundels/gefactureerd` ook bij een noodstop. Het gegevensdeel (vragenlijsten, inzendingen) is vanaf Hoog alleen lezen en vanaf Ernstig dicht (antwoord 503 met `fout: "dreigingsniveau"`, of 423 als de sleutel tijdelijk dicht is). EasyInvoice moet een 503/423 netjes opvangen en later opnieuw proberen, niet als "fout bij de klant" tonen. Andersom geldt hetzelfde: de aanroepen die het VvE-pakket bij EasyInvoice doet voor het factureren en de betaalmeldingen blijven op elk niveau open (met de sleutel).
- **Graag ook**: het niveau overnemen van het VvE-platform. Het koppelvlak tussen de twee bestaat al (`KOPPELVLAK_SLEUTEL`); voorstel: een `GET /koppelvlak/dreiging` aan de VvE-kant die EasyInvoice elke minuut leest, en dan de strengste van de twee aanhouden. Nooit andersom versoepelen.

## Waar naar te kijken in het VvE-pakket

`lib/dreigingsniveau.js` (standen, signalen, maatregelen), `middleware/dreiging.js` (de poorten per niveau), `routes/dreiging.js` (zetten, wachtende exports, draaiboek datalek), `test-dreigingsniveau.js` (wat per niveau dicht en open moet), en `ontwerp/beveiliging/2026-11-11-nalopen.md`.

## Toetsen

Per niveau wat dicht moet en wat open blijft (betaalwebhooks altijd open); de automatische ophoging; alleen het platform kan omlaag; de noodweg via env.
