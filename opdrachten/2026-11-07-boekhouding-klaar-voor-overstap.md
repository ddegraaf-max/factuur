# Boekhouding: klaar maken om e-Boekhouden te vervangen

**Status: open**

## Waarom

Daniël gaat de boekhouding van Creditline zelf doen en wil die van
e-Boekhouden naar Easy halen. Op 7 november 2026 is de boekhouding van Easy
doorgelicht en naast het menu van e-Boekhouden gelegd. De grootboekkern is goed
(RGS 3.3, debet = credit in PHP én Postgres, boekjaren, XAF). Maar bank, inkoop
en btw lopen deels náást het grootboek.

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

## Deel 3: overstap

**Import van een XAF-auditfile uit e-Boekhouden**: rekeningschema koppelen aan
RGS, beginbalans en mutaties van het lopende jaar inlezen, relaties overnemen.
Daarna kan een kwartaal parallel draaien en de btw van beide pakketten naast
elkaar gelegd worden.

## Toets

Per punt een feature-test; draai ze ook tegen Postgres (de balans- en
jaartriggers bestaan niet op SQLite).
