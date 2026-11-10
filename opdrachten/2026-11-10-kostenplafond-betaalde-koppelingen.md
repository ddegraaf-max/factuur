**Gebouwd in branch e1-kostenplafond**

# Plafond op betaalde koppelingen: KvK eerst (prioriteit 1)

Open — 10 november 2026.

## Waarom

Daniël, 10 november 2026: "Wel die stop op de aanvragen van KvK, ik wil zoals gezegd geen torenhoge rekeningen krijgen. Check dat overal nogmaals, wat ben straks failliet voordat ik begin, want er zijn grapjassen zat in de wereld."

Bij de controle bleek dat NextPro en de VvE-merken een dagplafond hebben op de KvK en een centrale kostenbewaker (`lib/kostenplafond.js` in VVE-WEBSITE). EasyInvoice (en dus ook Fachora, dat uit deze repo draait) heeft dat **niet**. `KVK_API_KEY` staat op productie.

KvK-tarieven, opgezocht op developers.kvk.nl/pricing op 10 november 2026:
- Zoeken: € 0;
- Basisprofiel, Vestigingsprofiel en Naamgeving: € 0,02 per bevraging;
- daarbovenop € 6,40 per maand.

## Wat er nu openstaat (origin/main, Easy 1.111.1)

- **Bedrijfsgegevens ophalen.** `KvkService::profile()` vraagt het betaalde basisprofiel op. Het wordt aangeroepen via `GET kvk/profiel/{kvkNumber}`: `throttle:30,1` per gebruiker, en het onthoudt een antwoord één uur per nummer.
- **Klantscore.** `KvkService::facts()` vraagt ook het betaalde basisprofiel op. Het onthoudt een antwoord 7 dagen, maar `$fresh = true` wist dat geheugen. Dat gebeurt via de klantscore (`customers.score`, `throttle:20,1`, `CustomerScoreController::refresh`).
- **Geen plafond.** Er is geen plafond over alle gebruikers samen, en ook geen plafond per team per dag.
  - Een ingelogde gebruiker, ook iemand die gratis proeft, kan met een script ongeveer 50 betaalde opvragingen per minuut doen. Dat zijn er ruim 70.000 per dag, ongeveer € 1.400. Met meer accounts wordt dat veelvoud.
  - Een nummer dat niet bestaat wordt niet onthouden (`failed()` → `null`, en `Cache::remember` bewaart null niet betrouwbaar). Nummers aflopen kost dus elke keer opnieuw geld.
- **Ook nakijken:**
  - `CcbrService`: kredietcheck, betaald per check, met `throttle:10,1` per gebruiker;
  - `PeppolService` / Recommand: versturen kost per document;
  - `SmsService`: dit loopt via tegoed (`SmsCreditService`); controleer dat er zonder tegoed niets verstuurd wordt;
  - de aanroepen naar Anthropic (`ANTHROPIC_API_KEY`).

## Wat er moet komen

1. **Eén kostenbewaker voor betaalde koppelingen** (`app/Services/CostGuard.php` of een passende naam in de stijl van de repo). Elke betaalde aanroep gaat erdoorheen, zodat een nieuwe functie het plafond niet kan vergeten. Hij telt per dienst:
   - per dag over het hele platform;
   - per dag per team (administratie);
   - per maand een budget in eurocenten.

   Gebruik een teller die gedeeld wordt over alle processen: de database of de gedeelde cache, met atomair ophogen. Valt die weg, dan telt hij in het werkgeheugen door. Een storing mag het plafond nooit opheffen.
2. **Standaarden.** Die zijn laag en in te stellen met env. Een onleesbare waarde valt terug op de standaard, en `0` betekent dat de dienst dicht staat.
   - **KvK betaald** (basisprofiel, vestigingsprofiel, naamgeving): `KVK_DAGLIMIET` standaard 50 per dag voor het platform, 10 per team per dag, maandbudget € 20.
   - **KvK zoeken:** telt NIET mee voor de kosten (€ 0). Zet wel een redelijke grens per minuut tegen misbruik van onze sleutel.
   - **CCBR, Peppol en AI:** stel een standaard voor op basis van de prijs per aanroep (zoek die op of vraag het Daniël) en zet die in het opdrachtbestand.
3. **Noodstop:** `NOODSTOP=1` of `NOODSTOP_KVK=1` (enzovoort per dienst) zet een dienst direct dicht, zonder dat er code gedeployd hoeft te worden.
4. **Geheugen:**
   - `profile()` en `facts()` delen één geheugen per KvK-nummer van minstens 24 uur. Ook "niet gevonden" (404) wordt onthouden, een storing (5xx of time-out) niet.
   - `$fresh` mag het geheugen hoogstens één keer per 24 uur per nummer per team verversen.
5. **Bij het plafond:**
   - de aanroep gaat niet naar buiten;
   - de gebruiker krijgt een vriendelijke melding ("Automatisch ophalen is nu even niet beschikbaar, vul de gegevens zelf in") zonder het plafond of de hoogte ervan te noemen;
   - de rest van de app werkt gewoon door.
6. **Alarm:** bij 80% van het dagplafond en bij het bereiken ervan gaat er één mail per dag per dienst naar het beheeradres dat de repo al gebruikt voor meldingen aan Daniël. Zoek eerst wat er is.
7. **Overzicht:** een klein overzicht voor de beheerder (Daniël) met het verbruik vandaag en deze maand per dienst. Gebruik een bestaande beheer- of instellingenpagina als die er is.
8. **Toetsen** (Pest/PHPUnit, zoals de repo doet):
   - het plafond per dag (platform en team);
   - een tweede opvraging van hetzelfde nummer kost niets;
   - "niet gevonden" wordt onthouden;
   - `$fresh` hoogstens één keer per dag;
   - de noodstop;
   - de melding bij het plafond;
   - zoeken telt niet mee;
   - een storing van de teller heft het plafond niet op.

## Niet

- Geen bedragen of hoogtes van plafonds in meldingen aan gebruikers.
- De functies blijven werken zonder KvK. Het plafond haalt alleen het invulgemak weg.

## Gebouwd (branch e1-kostenplafond)

- `app/Services/CostGuard.php` met de instellingen in `config/kostenplafond.php` en de tellers in de tabel `cost_counters`. Elke betaalde aanroep gaat erdoorheen: KvK-basisprofiel, KvK zoeken (alleen grens per minuut), AI (alle vier de aanroepplekken van Claude, via `Ai\AiBudget`), Peppol versturen, sms op kosten van het platform, brieven via Pingen en het CCBR.
- Tellen: in de database met één `UPDATE … WHERE calls < plafond` per teller, alles in één transactie. Werkt de database niet, dan telt hij door in een bestand op de server (`storage/framework/cache/kostenplafond.json`, met slot), en lukt ook dat niet, dan in het werkgeheugen. Het plafond blijft in alle gevallen gelden.
- Noodstop: `NOODSTOP=1` voor alles, of `NOODSTOP_KVK`, `NOODSTOP_AI`, `NOODSTOP_PEPPOL`, `NOODSTOP_SMS`, `NOODSTOP_POST`, `NOODSTOP_CCBR`.
- KvK: `profile()` en `facts()` delen één geheugen per nummer (gevonden 7 dagen, niet gevonden 24 uur; storing niet). Verversen via de klantscore hoogstens één keer per 24 uur per nummer per team.
- Bij het plafond: geen aanroep naar buiten; de gebruiker ziet een vriendelijke melding zonder bedragen; de rest van de app werkt door.
- Alarm: mail naar `OwnerAccess::emails()` (hetzelfde adres als de foutmeldingen) bij 80% van het dagplafond, bij het bereiken ervan en bij 80% en 100% van het maandbudget; hoogstens één per soort per dienst per dag.
- Overzicht: bovenaan Eigenaar → Administraties, met vandaag en deze maand per dienst.
- Toetsen: `tests/Feature/KostenplafondTest.php`.

## Gekozen standaarden

Alle waarden zijn in te stellen met env; leeg of onleesbaar = standaard, 0 = dicht. Maandbudget in hele euro's.

| Dienst | Prijs per aanroep | Per dag (platform) | Per team per dag | Maandbudget | Per minuut | Env |
|---|---|---|---|---|---|---|
| KvK basisprofiel | € 0,02 (developers.kvk.nl, 10-11-2026) | 50 | 10 | € 20 | – | `KVK_DAGLIMIET`, `KVK_TEAMLIMIET`, `KVK_MAANDBUDGET` |
| KvK zoeken | € 0 | – | – | – | 60 | `KVK_ZOEKEN_MINUUTLIMIET` |
| AI (Claude) | schatting € 0,08; achteraf verrekend met de tokens ($ 5 in / $ 25 uit per miljoen, Claude Opus 5) | 300 | 50 | € 150 | – | `AI_DAGLIMIET`, `AI_TEAMLIMIET`, `AI_MAANDBUDGET` |
| Peppol (Recommand) | aanname € 0,10 per document (prijs niet gevonden, vraag Daniël) | 200 | 50 | € 50 | – | `PEPPOL_DAGLIMIET`, `PEPPOL_TEAMLIMIET`, `PEPPOL_MAANDBUDGET` |
| Sms op kosten van het platform | aanname € 0,08 per sms (Smstools) | 100 | 50 | € 40 | – | `SMS_DAGLIMIET`, `SMS_TEAMLIMIET`, `SMS_MAANDBUDGET` |
| Post (Pingen) | aanname € 1,50 per brief | 25 | – | € 100 | – | `POST_DAGLIMIET`, `POST_MAANDBUDGET` |
| CCBR | € 0 (abonnement en gebruik gratis volgens rechtspraak.nl) | 200 | 40 | – | 20 | `CCBR_DAGLIMIET`, `CCBR_TEAMLIMIET`, `CCBR_MINUUTLIMIET` |

Toelichting:
- Het CCBR blijkt gratis (rechtspraak.nl, "Abonnement service CCBR"): er staat geen bedrag bij, alleen grenzen tegen misbruik van ons abonnement.
- AI: een publiek onderdeel telt als eigen team: `fachora` (AI-hulp op het formulier en de verrijking van een opdracht) en `fachora-vertalen` (de PKD-vertaling die elke tien minuten draait). Zo kan het publieke formulier het AI-plafond van betalende klanten niet opmaken. De bestaande grens van `AI_MONTHLY_LIMIT` per administratie blijft gelden.
- Sms: alleen wat het platform betaalt telt mee (verificatiecodes van Fachora en het klantportaal, per afzender een eigen team, en de eigen administraties zonder tegoed). Sms uit gekocht tegoed betaalt de klant; zonder tegoed gaat er niets uit (`remaining()` wordt vóór het versturen gecontroleerd).
