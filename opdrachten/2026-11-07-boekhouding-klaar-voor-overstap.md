# Boekhouding: klaar maken om e-Boekhouden te vervangen

**Status: open**

## Waarom

Daniël gaat de boekhouding van Creditline zelf doen en wil die van
e-Boekhouden naar Easy halen. Op 7 november 2026 is de boekhouding van Easy
doorgelicht en naast het menu van e-Boekhouden gelegd. De grootboekkern is goed
(RGS 3.3, debet = credit in PHP én Postgres, boekjaren, XAF). Maar bank, inkoop
en btw lopen deels náást het grootboek.

**Besluiten van Daniël (7 november 2026):**
- Alleen de administratie van Creditline gaat over, **per 1 januari 2027**. Deel 1
  en de punten 1, 3, 4, 5, 6 en 7 van deel 2 moeten dan af zijn, en de
  XAF-import uit deel 3.
- De btw-aangifte wordt **zelf digitaal ingediend**. Daniël vraagt een
  certificaat aan; in het begin loopt het eventueel via de boekhouder
  (Ted Accountancy). Bouw daarom eerst een aangifte die de boekhouder kan
  overnemen (overzicht + export, en toegang voor de rol boekhouder), en daarna
  rechtstreeks indienen via Digipoort met het eigen certificaat.

Volledige doorlichting: https://claude.ai/artifact/GCHPYwEHLD7kiDpySK3LKm
(hoofdstuk "Easy als boekhouding").

Ga elk punt eerst zelf na; regelnummers zijn van 1.107.0.

## Deel 1: fouten (eerst)

1. **Verlegde btw in de verkeerde rubriek.** `salesAccount` en
   `vatPayableAccount` sturen `vat_reversed` naar 2a
   (`app/Services/LedgerPostingService.php:804` en `:826`). 2a is voor de
   afnemer. Verkoop met btw verlegd (binnenland, bouw) hoort in 1e, EU-B2B-dienst
   in 3b. Nagekeken: klopt. Raakt Creditline direct (onderaannemers).
2. **Twee btw-bronnen.** `VatService::overview` rekent uit facturen,
   `LedgerReportService::vatReturn` (wordt nergens aangeroepen) uit het
   grootboek. Landbepaling verschilt (`strtoupper(customer_country)` zonder
   `CountryCode::of`, rond `:843`). Maak het grootboek de enige bron voor de
   aangifte; handmatige memoriaalboekingen op btw-rekeningen moeten meetellen.
3. **Inkoop wijzigen of verwijderen boekt niet mee.** `postPurchase` geeft een
   bestaande boeking terug (`:405`); geen `deleted`-hook op `PurchaseInvoice`;
   ook `reopen` en bankkoppeling ongedaan maken (`BankController.php:216`) laten
   boekingen staan. Herboeken of tegenboeken.
4. **Tegenboeken in een gesloten jaar loopt vast.** `LedgerService::reverse`
   neemt de oude datum (`:120`) en `assertYearOpen` weigert; de fout wordt
   weggelogd. Tegenboeken op de eerste dag van het eerste open jaar.
5. **Fouten bij boeken onzichtbaar.** `sync()` vangt alles af en logt
   (`LedgerPostingService.php:79-88`). `ledger:check` draait niet op schema.
   Signaal in de UI en `ledger:check` dagelijks in de scheduler.
6. **Te hoge bankbetaling verdwijnt** (`BankController.php:145`, `min(...)`).
   Het meerdere naar een betalingsverschil- of vooruitontvangenrekening.

Kleiner: categorie naar rekening op `str_contains` (`:877`, "auto" pakt
"automatisering"); scheve beginbalans stil naar kapitaal
(`BookYearService.php:83-88`) — weigeren of hard waarschuwen; jaar vaststellen
controleert niet of alles geboekt is en of Q4-btw is ingediend; heropenen zonder
logregel; auditlog ontbreekt op `JournalEntry`, `LedgerAccount`, `BookYear`,
`VatFiling` (`AppServiceProvider.php:33-37`); `CompanyPurger` wist het
grootboek zonder 7-jaarscontrole.

## Deel 2: wat e-Boekhouden heeft en Easy nog niet

Uit het menu van de e-Boekhouden-administratie van Daniël:

1. **Bank volledig in het grootboek.** Elke bankregel boekbaar: tegenrekening
   kiezen met btw, privé, btw-betaling, salaris, bankkosten. Bankregels (op IBAN
   of omschrijving automatisch boeken), splitsen, één betaling op meerdere
   facturen. Tussenrekening (KRUISPOST) voor Mollie/Stripe met kostenboeking.
   De rekeningen `KRUISPOST`, `BTW_AFGEDRAGEN`, `PRIVE_OPNAME/STORTING` bestaan
   al maar worden niet gebruikt.
2. **Mutaties zoeken** op datum, mutatienummer, bedrag, soort boeking, relatie,
   btw-code, factuurnummer, omschrijving, boekstuk.
3. **Mutatie invoeren** (geld ontvangen, geld uitgegeven, memoriaal) ook voor de
   rol boekhouder, niet alleen `role:owner` (`routes/web.php:893`).
4. **Afgesloten periodes**: periode op slot na ingediende btw-aangifte
   (`VatFiling.filed_at`); geboekte posten daarin alleen tegenboeken, nooit
   verwijderen (`LedgerService.php:166-178`).
5. **Vaste activa**: register, afschrijvingsschema, automatische boekingen,
   desinvestering.
6. **Btw-aangifte en ICP-opgave indienen** (Digipoort/SBR), verstuurde aangiftes
   bewaren, suppletie. Verlegde inkoop (2a, 4a, 4b), niet-aftrekbare btw,
   privégebruik (1d).
7. **Open posten crediteuren**: leverancier als relatie (nu vrije tekst),
   ouderdomsanalyse, SEPA-betaalbatch (pain.001).
8. **Rekeninggroepen** voor eigen rapportage-indeling.
9. **Accountantscontrole**: scherm met de controles uit `ledger:check`.
10. **Digitaal archief** per boekjaar, binnen 7 jaar niet te wissen.
11. **Budgetscenario's**: budget per grootboekrekening naast werkelijk.
12. **Auditfile** met `openingBalance` (nu 0 treffers in `XafExporter.php`).
13. **Rapportages** per maand/kwartaal met vergelijking, kolommenbalans.

## Deel 3: overstap van e-Boekhouden (uitgewerkt 8 november 2026)

Daniël exporteert straks zelf uit e-Boekhouden en importeert in Easy. Doel:
**een juiste beginbalans per 1 januari 2027** voor Creditline, met de
openstaande posten erbij, zodat de boekhouding in Easy vanaf dag één klopt.
De administratie in e-Boekhouden heet "Creditline Incasso- en
Recherchediensten" (nummer 1872230). In e-Boekhouden staat de export onder
Beheer → Exporteren → Auditfile; relaties onder Relaties → Overzicht.

### Wat er moet komen

1. **Scherm "Overstappen van e-Boekhouden"** in de boekhouding (rol beheerder én
   boekhouder), met stappen: uploaden → koppelen → controleren → vastleggen.
2. **XAF-auditfile inlezen** (versie 3.2 en 4.0; e-Boekhouden levert XAF).
   - Rekeningschema: elke rekening uit de auditfile koppelen aan een RGS-rekening
     in Easy. Eerst op de RGS-code als e-Boekhouden die meegeeft, dan op nummer en
     naam, en wat overblijft laat Daniël kiezen. Nieuwe eigen rekeningen mogen.
   - **Beginbalans 2027** = de eindsaldi per 31-12-2026 van alle
     balansrekeningen uit de auditfile (of de openingBalance van 2027 als de file
     al over 2027 gaat). Via `BookYearService::setOpeningBalance`.
   - Het resultaat 2026 hoort niet als los W&V-saldo binnen te komen maar op het
     eigen vermogen. **Creditline is een B.V.**: dus naar "onverdeelde winst
     boekjaar" / overige reserves, niet naar ondernemingsvermogen van een
     eenmanszaak (ook `postResultToEquity` moet de rechtsvorm volgen).
   - Een beginbalans die niet sluit **weigeren** met het verschil en de
     rekeningen erbij; niet stil naar kapitaal (zie deel 1).
3. **Openstaande posten meenemen.** Het saldo debiteuren en crediteuren alleen
   op de balans is niet genoeg: in januari komen betalingen binnen op facturen
   uit 2026 die Easy dan moet herkennen.
   - Uit de auditfile (of de export "Open posten") per openstaande factuur:
     relatie, factuurnummer, datum, vervaldatum, openstaand bedrag.
   - Inlezen als **historische factuur** (verkoop en inkoop): zichtbaar, af te
     letteren, mee in aanmaningen en ouderdom, maar **zonder** opnieuw omzet,
     kosten of btw te boeken. Die zitten al in de beginbalans. De betaling
     boekt straks alleen bank tegen debiteuren/crediteuren.
   - De som van de historische open posten moet gelijk zijn aan het saldo
     debiteuren en crediteuren in de beginbalans; zo niet: tonen, niet vastleggen.
4. **Relaties** uit de auditfile (customerSupplier) overnemen, ontdubbelen
   tegen de klanten die al in Easy staan (op KvK, btw-nummer, IBAN, naam).
5. **Bankrekening**: het banksaldo per 31-12-2026 uit de beginbalans moet gelijk
   zijn aan het afschrift; bij de eerste CAMT/Ponto-import van 2027 controleren
   dat het beginsaldo van het afschrift aansluit en anders waarschuwen.
6. **Voorlopig en definitief.** In januari is 2026 bij e-Boekhouden/de
   boekhouder nog niet afgesloten. De beginbalans moet daarom later opnieuw
   ingelezen kunnen worden (nieuwe auditfile na de jaarafsluiting), zonder dat
   de boekingen van 2027 verloren gaan: alleen de beginbalanspost en de
   historische open posten worden vervangen, en wat in 2027 al op een
   historische factuur is afgeletterd blijft staan. Toon het verschil tussen
   voorlopig en definitief per rekening.
7. **Controlerapport na het vastleggen** (ook als PDF voor de boekhouder):
   proefbalans 1-1-2027 naast de eindbalans uit e-Boekhouden, per rekening;
   debiteuren = som open posten; crediteuren = som open posten; bank = afschrift;
   btw-rekeningen (nog af te dragen over Q4 2026) apart genoemd.
8. **Btw Q4 2026**: wordt nog met de boekhouder/e-Boekhouden aangegeven. De
   schuld staat in de beginbalans; de betaling in 2027 moet in Easy op die
   btw-schuld kunnen worden geboekt (bankregel → rekening btw af te dragen).
9. **Toets** met een echte XAF-export van e-Boekhouden (Daniël levert een
   voorbeeldbestand, anders een nagebouwde XAF 3.2): inlezen, koppelen,
   beginbalans sluit, open posten sluiten aan, een januaribetaling op een
   historische factuur boekt alleen bank/debiteuren, en het opnieuw inlezen van
   een definitieve auditfile vervangt alleen wat het moet vervangen.

### Volgorde tot 1 januari 2027

1. Deel 1 (de zes fouten), want de beginbalans moet in een kloppend grootboek
   landen.
2. Deel 3 punt 1–5 en 7: de import en het controlerapport.
3. Deel 2 punt 1 (bank volledig boeken), 4 (periodeslot), 5 (activa: de vaste
   activa uit e-Boekhouden met aanschafwaarde, afschrijvingsschema en
   boekwaarde overnemen), 3 en 7.
4. Deel 3 punt 6 en 8.
5. Proefdraaien: Daniël leest in december een auditfile van 2026 tot dan in,
   zodat fouten vóór 1 januari boven komen.

## Toets

Per punt een feature-test; draai ze ook tegen Postgres (de balans- en
jaartriggers bestaan niet op SQLite).
