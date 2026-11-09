# Herinneringen: een pauze loopt niet stilletjes af

**Status: open**

## Waarom

Op 9 november 2026 kreeg een klant een betalingsherinnering voor een factuur die
Daniël op pauze had gezet ("op hold"). Hij heeft de pauze niet zelf opgeheven
en wilde zeker geen herinnering. Dat is irritant voor hem en vervelend voor de
klant.

In de code kan een factuur die echt op pauze staat geen herinnering krijgen:
`ReminderService::run()` slaat `reminders_paused_at` over en `sendManual()`
weigert een gepauzeerde factuur. Er is maar één route waarlangs een pauze
vervalt zonder dat de gebruiker iets doet: **een pauze met een einddatum.**
`liftExpiredPauses()` heft die de dag na de einddatum op, om 08:00 vlak vóór de
herinneringsronde, en dezelfde ronde stuurt dan meteen de eerstvolgende stap.
De gebruiker krijgt daar niets over te horen.

Zo'n einddatum komt op twee manieren op een factuur:
- in het pauzevenster bij "t/m" (`Invoices/Show.vue`);
- via "Akkoord met de toegezegde dag" bij een online aanmaning (`acceptPromise`
  vult de toegezegde datum als einddatum in).

## Eerst: nagaan wat er op 9 november gebeurde

- Zoek in het activiteitenlog (`Audit`, soorten `paused`, `resumed`,
  `reminded`) van Creditline welke factuur op 9 november een herinnering kreeg
  (vermoedelijk 2026-0023, Toton Onderhoudsbedrijf, vervallen 2 november).
- Stond er een `resumed` met "pauze afgelopen" vlak voor de `reminded`? Dan is
  het de route hierboven. Zo niet, zoek de andere oorzaak en meld die aan
  Daniël voordat je iets bouwt.
- Schrijf de uitkomst bovenaan dit bestand.

## Wat er moet veranderen

1. **Een afgelopen pauze laat geen post vertrekken zonder akkoord.** Als een
   pauze met einddatum afloopt, gaat de factuur niet terug in het automatische
   schema, maar naar de status "Pauze afgelopen, wacht op jou". Er gaat pas
   weer een herinnering uit als de gebruiker op "Hervat de herinneringen"
   klikt (of de pauze verlengt). Het schema schuift dan op zoals `resume()` nu
   al doet.
2. **Een dag van tevoren een seintje.** De dag vóór de einddatum krijgt de
   gebruiker een melding in de app (en in de dagelijkse samenvatting, als die
   aan staat): "De pauze van factuur … loopt morgen af. Verlengen, hervatten of
   laten staan?"
3. **Zichtbaar in het pauzevenster.** Onder het veld "t/m": "Na deze datum
   vragen we je eerst of de herinneringen weer mogen lopen." Bij
   "Akkoord met de toegezegde dag" dezelfde zin.
4. **Lijst en dashboard.** De status "Pauze afgelopen" staat in de facturenlijst
   en in het filter "Op pauze", zodat ze niet uit beeld raken.
5. **Incasso en aanmaningen** volgen dezelfde regel: na een afgelopen pauze
   gaat er niets naar incasso of de online aanmaning zonder akkoord.

## Toetsen

- Een pauze zonder einddatum: nooit een herinnering (bestaat al, controleer).
- Een pauze met einddatum gisteren: de ronde van vandaag stuurt níets, de
  factuur staat op "Pauze afgelopen", en er is een melding.
- Na "Hervat": de eerstvolgende stap volgt op dezelfde afstand als vóór de
  pauze (geen inhaalslag), zoals nu.
- De melding een dag van tevoren komt één keer, niet elke dag.

Zet bij afronding bovenaan **Afgerond in versie x.y.z**.
