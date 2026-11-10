**Afgerond in versie 1.110.0**

# Extra verbruik van de merken factureren (bundels en brieven per post)

**Status: gebouwd in 1.110.0 op branch `b33-merken-bundels` (nog niet samengevoegd met `main`)**

## Probleem (gevonden 10 november 2026)

Fair use werkt in alle zes merken van het VvE/NextPro-pakket (NextPro, Napoleon,
VvEMaat, VvEklik, VvEnova, VvESlim): verbruik per kantoor of vereniging, bundels
bijkopen, brieven per post via Pingen. Het pakket zet die klaar in het koppelvlak,
maar EasyInvoice haalde ze nergens op: `VvemaatService` kende alleen VvEMaat en
alleen "betaald tot". Er werd dus nooit gefactureerd voor extra verbruik.

## Besluit Daniël

Alle facturen voor de merken (abonnement en extra verbruik) lopen via de
EasyInvoice-administratie van **NapoleonSoftware B.V.** Die bestaat nog niet;
Daniël maakt hem aan. Het team is instelbaar. Er zijn nog geen klanten, dus er
hoeft niets gemigreerd te worden.

## Gebouwd

- **`config/merken.php`**: de zes merken met code, naam, adres, koppelvlaksleutel
  (`MERK_<CODE>_KOPPELVLAK_SLEUTEL`) en de administratie die factureert
  (`MERKEN_TEAM_ID`, per merk afwijkend met `MERK_<CODE>_TEAM_ID`; optioneel
  `MERK_<CODE>_URL` en `MERK_<CODE>_HANDELSNAAM_ID`). VvEMaat valt terug op de
  oude `VVEMAAT_URL` en `VVEMAAT_KOPPELVLAK_SLEUTEL`.
- **Ophalen** (`MerkenService`, commando `merken:bundels-factureren [merk]`):
  maandelijks op de 1e om 06:40 (alleen als er een administratie is ingesteld),
  en handmatig via Eigenaar → Merken. Per merk één vraag
  `GET /koppelvlak/bundels` (kop `X-Koppelvlak-Sleutel`), die alle kantoren en
  verenigingen met open bundels en Pingen-maanden teruggeeft, met naam, adres,
  KvK, btw-nummer en mailadres.
- **Factureren**: per klant per maand één **conceptfactuur** in de ingestelde
  administratie. Klant op `merk_code` + `merk_kenmerk` (bij VvEMaat ook op het
  oude `vvemaat_slug`), anders aangemaakt met de gegevens uit het koppelvlak; een
  bestaande klant krijgt alleen lege velden aangevuld. Per bundel een regel
  (omschrijving, aantal, prijs excl. btw, 21% btw). Komt er in dezelfde maand
  nog iets bij en is het concept nog niet verstuurd, dan komt het bij dat concept.
  Nooit automatisch versturen.
- **Nooit dubbel**: tabel `merk_bundel_regels`, uniek op merk + kenmerk +
  bundel-id (het kenmerk hoort erbij omdat een Pingen-id `pingen:2026-10` alleen
  binnen één klant uniek is).
- **Afmelden**: daarna `POST /koppelvlak/bundels/gefactureerd` met de ids en als
  factuurnummer `EI-concept-<id>` (een concept heeft nog geen nummer). Mislukt
  dat, dan staat het bij de regel en wordt het bij de volgende ophaling opnieuw
  geprobeerd, zonder dat de regel opnieuw op een factuur komt.
- **Betaald tot**: een klant met `merk_code` + `merk_kenmerk` meldt een betaalde
  abonnementsfactuur aan zijn eigen merk; de bestaande VvEMaat-werking blijft.
- **Eigenaar → Merken** (`/eigenaar/merken`): per merk of de koppeling is
  ingesteld (en wat ontbreekt), de laatste ophaling, de klaargezette concepten,
  wat niet is teruggemeld, de laatste 30 ophalingen, en knoppen "Nu ophalen".
- **Toetsen**: `tests/Feature/MerkenBundelsTest.php` (nep-koppelvlak met
  Http::fake): concept per klant met de juiste regels en klantgegevens, geen
  dubbele regels, aanvullen van het concept in dezelfde maand, nieuw concept na
  versturen, afmelden, opnieuw afmelden na een fout, merk zonder sleutel en
  zonder administratie overgeslagen met melding, verkeerde sleutel als fout, het
  commando, het eigenaarsscherm en handmatig ophalen, betaalmelding per merk.

## Wat Daniël moet doen

1. De administratie van NapoleonSoftware B.V. aanmaken in EasyInvoice; het id
   staat bij Eigenaar → Administraties.
2. In Railway bij EasyInvoice: `MERKEN_TEAM_ID=<dat id>`.
3. Per merk een eigen lange willekeurige sleutel, aan beide kanten dezelfde:
   `KOPPELVLAK_SLEUTEL` bij het merk en `MERK_<CODE>_KOPPELVLAK_SLEUTEL` bij
   EasyInvoice (`NEXTPRO`, `NAPOLEON`, `VVEMAAT`, `VVEKLIK`, `VVENOVA`, `VVESLIM`).
4. De VvE-kant (branch `b33-bundels-factureren` van VVE-WEBSITE, met de route
   `GET /koppelvlak/bundels`) moet live staan voordat het ophalen iets vindt.
5. Daarna Eigenaar → Merken → "Nu ophalen" om de koppeling te proberen.

## Open punten

- Het nummer van de verstuurde factuur gaat niet alsnog naar het merk; daar
  staat `EI-concept-<id>`. Het id leidt in EasyInvoice naar dezelfde factuur.
- Verwijdert Daniël een concept, dan staan de regels in het merk als
  gefactureerd en in `merk_bundel_regels` zonder factuur; ze komen niet
  vanzelf terug.
- Rekent een merk het abonnement via Stripe af, dan blijven de bundelroutes
  werken (aangepast in de VvE-branch); alleen de abonnementsroutes weigeren (409),
  en de betaalmelding staat dan als fout in de log.
