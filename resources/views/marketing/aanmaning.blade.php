@extends('layouts.marketing')

@section('title', 'Gratis online aanmaning versturen — ' . brand('name'))
@section('description', 'Verstuur gratis een aanmaning met een eigen pagina: de rente loopt elke dag op, je klant reageert met één klik en jij ziet wanneer hij haar opent. Zonder account.')

@php
  $partner = \App\Support\Market::incasso('partner_name');
  $day = fn ($date) => $date?->translatedFormat('j F Y');
  $faq = [
      ['Is de online aanmaning echt gratis?', 'Ja. Maken, versturen per mail, de pagina met het bedrag van vandaag en de brief met QR-code zijn gratis, en je hebt geen account nodig.'],
      ['Waarom moet ik mijn e-mailadres bevestigen?', 'De aanmaning gaat uit jouw naam. Daarom moeten we zeker weten dat jij de eigenaar bent van het adres dat is ingevuld. Tot je op de knop in de mail hebt geklikt, bestaat de pagina niet en gaat er niets naar je klant. Het is één klik, zonder account en zonder wachtwoord.'],
      ['Welke rente en kosten worden gerekend?', 'De wettelijke rente van dat moment: de handelsrente voor een zakelijke klant (artikel 6:119a BW) en de gewone wettelijke rente voor een particulier (artikel 6:119 BW), vanaf de dag na de vervaldatum. De incassokosten volgen de wettelijke staffel, met een minimum van € 40, en zijn pas verschuldigd na de termijn. Kun je geen btw verrekenen, dan komt er btw over de kosten bij.'],
      ['Wat is het verschil tussen zakelijk en particulier?', 'Een particulier krijgt wettelijk minstens veertien dagen, te rekenen vanaf de dag na ontvangst, en in de aanmaning moet het bedrag van de incassokosten staan. Dat heet ook wel de veertiendagenbrief. Voor een zakelijke klant kies je zelf een termijn van 5 tot 30 dagen.'],
      ['Wat levert een toegezegde betaaldatum op?', 'Kiest je klant "ik betaal uiterlijk op", dan erkent hij de schuld. Die erkenning staat op schrift, met tijdstip en IP-adres, en stuit de verjaring (artikel 3:318 BW). Je krijgt haar per mail.'],
      ['Ziet mijn klant mijn gegevens?', 'Ja: je bedrijfsnaam, het factuurnummer, het bedrag en je rekeningnummer. Je e-mailadres staat niet op de pagina, maar is wel het antwoordadres van de mail. Antwoordt je klant op die mail, dan komt dat bij jou. Wat het handelsregister over je klant zegt, ziet alleen jij.'],
      ['Wat gebeurt er als de termijn voorbij is?', 'Je krijgt een bericht. In je overzicht staat dan de knop Overdragen aan de deurwaarder. Met één klik gaat het dossier naar ' . $partner . ': de aanmaning, de kopie van de factuur, de berekening en het logboek. Overdragen gebeurt alleen als jij op de knop drukt.'],
      ['Kan ik dit ook vanuit mijn boekhouding doen?', 'Ja. In ' . brand('name') . ' verstuur je de aanmaning vanaf de factuur zelf. Betalingen sluiten de aanmaning vanzelf, herinneringen gaan vooraf automatisch de deur uit, en je kunt het dossier na de termijn automatisch laten overdragen.'],
  ];
  $bullets = [
      ['Het bedrag van vandaag', 'de wettelijke rente naar het percentage van dat moment, elke dag opnieuw berekend. Na de termijn komen de incassokosten volgens de staffel erbij.'],
      ['Reactie van je klant', '"ik heb betaald", "ik betaal uiterlijk op…" of "ik ben het er niet mee eens". Elk antwoord staat op schrift; een toegezegde datum is een erkenning van de schuld.'],
      ['Verstuurd uit jouw naam', 'je bevestigt je e-mailadres met één klik. Daarna mailen wij de aanmaning naar je klant; antwoorden komen bij jou.'],
      ['Je ziet wanneer hij is geopend', 'in je eigen overzicht. Je eigen bezoeken en de scanners van mailprogramma\'s tellen niet mee.'],
      ['Brief met QR-code', 'om te printen en op de post te doen. De code leidt naar dezelfde pagina met het bedrag van die dag.'],
      ['Met één klik naar de deurwaarder', 'is de termijn voorbij en is er niet betaald, dan draag je het dossier over aan ' . $partner . '.'],
  ];
  $steps = [
      'Je vult de factuur en je klant in. Een account is niet nodig.',
      'Je bevestigt je e-mailadres: je klikt op de knop in de mail die je van ons krijgt. Zo kan niemand uit jouw naam een aanmaning versturen.',
      'De pagina van de aanmaning staat online, op een eigen adres. Je klant krijgt haar per mail, met de brief en de kopie van de factuur. Heb je geen adres ingevuld, dan stuur je de link of de brief zelf.',
      'Het bedrag op de pagina loopt elke dag op met de rente. Je klant betaalt, zegt een datum toe of maakt bezwaar. Jij krijgt een mail.',
      'Geen betaling na de termijn? Dan draag je het dossier met één klik over aan de deurwaarder.',
  ];
@endphp

@push('styles')
<style>
  .gen-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 32px; max-width: 860px; margin: 0 auto; box-shadow: var(--shadow-sm); }
  .gen-section-title { font-family: var(--font-display); font-size: 12.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--brand); margin: 26px 0 14px; padding-top: 22px; border-top: 1px solid var(--border); }
  .gen-section-title.first { margin-top: 0; padding-top: 0; border-top: none; }
  .gen-check { display: flex; align-items: flex-start; gap: 10px; font-size: 14.5px; color: var(--text-2); margin: 0 0 14px; line-height: 1.5; cursor: pointer; }
  .gen-check input { width: 18px; height: 18px; margin-top: 2px; flex: none; }
  .gen-hint { font-size: 12.5px; color: var(--text-3); margin-top: 5px; line-height: 1.5; }
  .gen-trap { position: absolute; left: -9999px; }
  .calc-box { margin: 22px 0; padding: 18px 20px; border-radius: 12px; background: var(--surface-2, #FAFAF9); border: 1px solid var(--border); }
  .calc-box[hidden] { display: none; }
  .calc-title { font-family: var(--font-display); font-weight: 700; font-size: 16px; margin-bottom: 8px; }
  .calc-row { display: flex; justify-content: space-between; gap: 16px; padding: 6px 0; font-size: 14.5px; color: var(--text-2); font-variant-numeric: tabular-nums; }
  .calc-row.grand { border-top: 2px solid var(--text); margin-top: 6px; padding-top: 10px; font-weight: 700; font-size: 16.5px; color: var(--text); }
  .calc-note { font-size: 13px; color: var(--text-3); margin-top: 8px; line-height: 1.55; }
  .privacy-note { font-size: 12.5px; color: var(--text-3); margin-top: 14px; line-height: 1.6; }
  .lead-list { max-width: 760px; margin: 0 auto; padding: 0; list-style: none; }
  .lead-list li { position: relative; padding: 7px 0 7px 30px; color: var(--text-2); line-height: 1.65; }
  .lead-list li::before { content: ''; position: absolute; left: 0; top: 12px; width: 18px; height: 18px; border-radius: 50%; background: var(--success-bg) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23059669' stroke-width='3'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E") no-repeat center; }
  .example-box { max-width: 860px; margin: 28px auto; padding: 24px 28px; border-radius: 16px; border: 1px dashed var(--brand-border); background: var(--brand-tint); }
  .example-box h2 { font-size: 21px; margin: 4px 0 8px; }
  .example-box p { color: var(--text-2); margin: 0 0 14px; line-height: 1.6; font-size: 15px; }
  .kicker { font-size: 12px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase; color: var(--brand); }
  .res-card { max-width: 860px; margin: 0 auto; background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 30px 32px; box-shadow: var(--shadow-sm); }
  .res-card h2 { font-size: 24px; margin: 6px 0 10px; }
  .res-card > p { color: var(--text-2); line-height: 1.65; margin: 0 0 16px; max-width: 70ch; }
  .res-row { display: grid; grid-template-columns: 220px 1fr; gap: 14px; padding: 11px 0; border-top: 1px solid var(--border); font-size: 14.5px; }
  .res-row .k { color: var(--text-3); }
  .res-row .v { color: var(--text); font-weight: 600; overflow-wrap: anywhere; }
  .res-row .v small { font-weight: 400; color: var(--text-3); font-size: 13px; }
  .res-row .v a { color: var(--brand); }
  .res-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 20px; align-items: center; }
  .res-actions form { margin: 0; }
  .res-note { font-size: 13px; color: var(--text-3); line-height: 1.6; margin: 14px 0 0; max-width: 70ch; }
  .res-ok { background: var(--success-bg); border: 1px solid var(--success-border); color: var(--success); padding: 12px 16px; border-radius: 12px; font-weight: 600; font-size: 14px; margin-bottom: 14px; }
  .steps { max-width: 760px; margin: 0 auto; counter-reset: step; list-style: none; padding: 0; }
  .steps li { position: relative; padding: 4px 0 22px 58px; color: var(--text-2); line-height: 1.7; }
  .steps li::before { counter-increment: step; content: counter(step); position: absolute; left: 0; top: 0; width: 38px; height: 38px; border-radius: 50%; background: var(--brand-tint); border: 1px solid var(--brand-border); color: var(--brand); font-family: var(--font-display); font-weight: 700; display: flex; align-items: center; justify-content: center; }
  .steps li:not(:last-child)::after { content: ''; position: absolute; left: 19px; top: 44px; bottom: 4px; width: 1px; background: var(--border-strong); }
  .legal-note { max-width: 760px; margin: 26px auto 0; font-size: 12.5px; color: var(--text-3); line-height: 1.6; }
  @media (max-width: 700px) {
    .gen-card, .res-card { padding: 22px 18px; }
    .res-row { grid-template-columns: 1fr; gap: 2px; }
    /* 16px voorkomt dat iOS inzoomt zodra een veld focus krijgt. */
    .m-field input, .m-field textarea, .m-field select { font-size: 16px; }
  }
</style>
@endpush

@section('content')
<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Online aanmaning · gratis</span>
    <h1>Gratis aanmaning die zelf <span style="color:var(--brand);">de rente bijhoudt</span></h1>
    <p class="lead">Vul de factuur en je klant in. Je krijgt een aanmaning met een eigen pagina en een QR-code. Het bedrag loopt elke dag op, je klant reageert met één klik en jij ziet wanneer hij haar opent. Zonder account, zonder kosten.</p>
  </div>
</section>

@if ($state === 'wait')
{{-- Na het formulier: wachten op de klik in de bevestigingsmail --}}
<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="res-card">
      <div class="kicker">Nog één stap</div>
      <h2>Bevestig je e-mailadres</h2>
      @if ($expired)
        <div class="alert-error">Deze link is verlopen; hij was {{ \App\Models\PaymentDemand::CONFIRM_DAYS }} dagen geldig. Maak de aanmaning opnieuw.</div>
      @elseif ($mailFailed ?? false)
        <div class="alert-error">De mail met de link kon niet worden verstuurd. Controleer je e-mailadres en vul het formulier opnieuw in.</div>
      @else
        <p>We hebben een link gestuurd naar <strong>{{ $demand->creditor_email }}</strong>. Open de mail en bevestig. Pas dan staat de aanmaning online{{ $demand->sent_to ? ' en gaat ze naar je klant' : '' }}. De link is {{ \App\Models\PaymentDemand::CONFIRM_DAYS }} dagen geldig.</p>
      @endif
      @if ($resent ?? false)<div class="res-ok">We hebben de link opnieuw gestuurd.</div>@endif
      @if ($resendLimit ?? false)<div class="alert-error">De link is net of al een paar keer verstuurd. Wacht een minuut, kijk in je map met ongewenste mail, of vul het formulier opnieuw in.</div>@endif

      <div class="res-row"><span class="k">Factuur</span><span class="v">{{ $demand->invoice_number }}</span></div>
      <div class="res-row"><span class="k">Klant</span><span class="v">{{ $demand->debtor_name }}</span></div>
      <div class="res-row"><span class="k">Te betalen vandaag</span><span class="v">{{ money($claim['total']) }}</span></div>

      <p class="res-note">Geen mail gekregen? Kijk in je map met ongewenste mail, of laat de link opnieuw sturen. Staat er een fout in je adres, vul het formulier dan opnieuw in. De bevestiging voorkomt dat iemand uit naam van een ander aanmaningen verstuurt.</p>
      <div class="res-actions">
        @unless ($expired)
          <form method="POST" action="{{ route('aanmaning.resend') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $demand->token }}">
            <button type="submit" class="btn btn-primary">Stuur de link opnieuw</button>
          </form>
        @endunless
        <a href="{{ route('aanmaning') }}" class="btn btn-secondary">Terug naar het formulier</a>
      </div>
    </div>
  </div>
</section>

@elseif ($state === 'confirm')
{{-- De link uit de mail: de gegevens, met de knop die bevestigt --}}
<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="res-card">
      <div class="kicker">Bevestiging</div>
      <h2>Bevestig je aanmaning</h2>
      <p>Controleer de gegevens. Na je bevestiging staat de pagina van de aanmaning online en begint de termijn vandaag te lopen.</p>

      <div class="res-row"><span class="k">Schuldeiser</span><span class="v">{{ $demand->creditor_name }}</span></div>
      <div class="res-row"><span class="k">Klant</span><span class="v">{{ $demand->debtor_name }} <small>· {{ $demand->isBusiness() ? 'zakelijk' : 'particulier' }}</small></span></div>
      <div class="res-row"><span class="k">Factuur</span><span class="v">{{ $demand->invoice_number }} <small>· vervallen op {{ $day($demand->due_date) }}</small></span></div>
      <div class="res-row"><span class="k">Te betalen vandaag</span><span class="v">{{ money($claim['total']) }}@if ($claim['with_interest']) <small>({{ money($claim['principal']) }} + {{ money($claim['interest']) }} rente)</small>@endif</span></div>
      <div class="res-row"><span class="k">Na de termijn</span><span class="v">{{ money($claim['total_after']) }} <small>(met {{ money($claim['costs_total']) }} incassokosten)</small></span></div>
      <div class="res-row"><span class="k">Termijn</span><span class="v">{{ $demand->term_days }} dagen</span></div>
      <div class="res-row"><span class="k">Wij mailen naar</span><span class="v">{{ $demand->sent_to ?: 'geen adres ingevuld: je stuurt de link zelf' }}</span></div>
      @if ($file = $demand->fileInfo())<div class="res-row"><span class="k">Kopie van de factuur</span><span class="v">{{ $file->filename }}</span></div>@endif

      @if ($expired)
        <div class="alert-error" style="margin-top:16px;">Deze link is verlopen; hij was {{ \App\Models\PaymentDemand::CONFIRM_DAYS }} dagen geldig. Maak de aanmaning opnieuw.</div>
        <div class="res-actions"><a href="{{ route('aanmaning') }}" class="btn btn-primary">Maak de aanmaning opnieuw</a></div>
      @else
        @if ($debtorLimit ?? false)<div class="alert-error" style="margin-top:16px;">Naar het adres van deze klant zijn vandaag al een paar aanmaningen gegaan. Bevestig morgen.</div>@endif
        <div class="res-actions">
          <form method="POST" action="{{ route('demand.confirm.store', $demand->token) }}">
            @csrf
            <input type="hidden" name="k" value="{{ $demand->creditor_key }}">
            <button type="submit" class="btn btn-primary btn-lg">{{ $demand->sent_to ? 'Ik bevestig: verstuur de aanmaning' : 'Ik bevestig: zet de aanmaning online' }}</button>
          </form>
        </div>
        <p class="res-note">Met je bevestiging verklaar je dat je namens de schuldeiser handelt en dat de vordering opeisbaar is. We leggen het tijdstip, het IP-adres en de browser van de bevestiging vast.</p>
      @endif
    </div>
  </div>
</section>

@elseif ($state === 'ok')
{{-- Na de bevestiging: de links en de stand --}}
<section class="section" style="padding-top:36px;">
  <div class="container">
    <div class="res-card">
      <div class="kicker">Klaar</div>
      <h2>Je aanmaning staat online</h2>
      <p>De links hebben we ook naar je e-mailadres gestuurd. Het bedrag loopt elke dag op; reageert je klant, dan krijg je een mail.</p>

      <div class="res-row"><span class="k">Link voor je klant</span><span class="v"><a href="{{ $demand->url() }}" target="_blank" rel="noopener">{{ $demand->url() }}</a></span></div>
      <div class="res-row"><span class="k">Jouw overzicht</span><span class="v"><a href="{{ $demand->creditorUrl() }}" target="_blank" rel="noopener">stand, geopend en reactie van je klant</a> <small>· alleen voor jou, niet doorsturen</small></span></div>
      <div class="res-row"><span class="k">Brief om te printen</span><span class="v"><a href="{{ route('demand.pdf', ['token' => $demand->token, 'k' => $demand->creditor_key]) }}" target="_blank" rel="noopener">PDF met QR-code</a></span></div>
      <div class="res-row"><span class="k">Mail aan je klant</span><span class="v">{{ $demand->sent_to ? 'verstuurd naar ' . $demand->sent_to : 'geen adres ingevuld: stuur de link of de brief zelf' }}@if ($demand->sent_to) <small>· uit jouw naam; antwoorden komen bij jou</small>@endif</span></div>
      @if ($file = $demand->fileInfo())<div class="res-row"><span class="k">Kopie van de factuur</span><span class="v">{{ $file->filename }}</span></div>@endif
      <div class="res-row"><span class="k">Te betalen vandaag</span><span class="v">{{ money($claim['total']) }}@if ($claim['with_interest']) <small>({{ money($claim['principal']) }} + {{ money($claim['interest']) }} rente)</small>@endif</span></div>
      <div class="res-row"><span class="k">Termijn tot en met</span><span class="v">{{ $day($demand->deadline) }} <small>· daarna {{ money($claim['costs_total']) }} incassokosten</small></span></div>
      @if ($facts)<div class="res-row"><span class="k">Klant in het handelsregister</span><span class="v">{{ $facts }}</span></div>@endif

      <div class="res-actions">
        <a href="{{ $demand->creditorUrl() }}" target="_blank" rel="noopener" class="btn btn-primary">Open je overzicht</a>
        <a href="{{ route('demand.pdf', ['token' => $demand->token, 'k' => $demand->creditor_key]) }}" target="_blank" rel="noopener" class="btn btn-secondary">Brief (PDF)</a>
        <a href="{{ route('aanmaning') }}" class="btn btn-secondary">Nog een aanmaning maken</a>
      </div>
      <p class="res-note">Is er na {{ $day($demand->deadline) }} niet betaald, dan draag je het dossier vanuit je overzicht met één klik over aan {{ $partner }}. De aanmaning, de berekening en het logboek gaan mee.</p>
    </div>

    <div class="example-box" style="margin-top:22px;">
      <div class="kicker">Vaker een klant die niet betaalt?</div>
      <h2>In {{ brand('name') }} gaat dit vanzelf</h2>
      <p>Herinneringen gaan automatisch de deur uit, de aanmaning verstuur je vanaf de factuur en een betaling sluit haar vanzelf. De eerste 14 dagen zijn gratis.</p>
      <a href="{{ route('register') }}" class="btn btn-primary">Probeer 14 dagen gratis →</a>
    </div>
  </div>
</section>

@else
<section class="section" style="padding-top:36px;">
  <div class="container">
    <ul class="lead-list">
      @foreach ($bullets as [$title, $body])
        <li><strong>{{ $title }}</strong>: {{ $body }}</li>
      @endforeach
    </ul>

    <div class="example-box">
      <div class="kicker">Voorbeeld</div>
      <h2>Bekijk precies wat we versturen</h2>
      <p>Bekijk voordat je iets invult een aanmaning met verzonnen gegevens: de pagina die je klant ziet, en alle mails die erbij horen.</p>
      <a href="{{ route('aanmaning.example') }}" class="btn btn-secondary">Bekijk het voorbeeld</a>
    </div>

    <form class="gen-card" method="POST" action="{{ route('aanmaning.store') }}" enctype="multipart/form-data" id="genForm">
      @csrf
      <input type="text" name="website" tabindex="-1" autocomplete="off" class="gen-trap" aria-hidden="true">

      @if ($errors->any())
        <div class="alert-error">Controleer het formulier: {{ $errors->first() }}</div>
      @endif

      <div class="gen-section-title first">Schuldeiser (jij)</div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="van_bedrijf">Bedrijfsnaam *</label>
          <input type="text" id="van_bedrijf" name="van_bedrijf" required maxlength="120" value="{{ old('van_bedrijf') }}" placeholder="Jansen Timmerwerk">
        </div>
        <div class="m-field">
          <label for="van_email">E-mailadres *</label>
          <input type="email" id="van_email" name="van_email" required maxlength="180" value="{{ old('van_email') }}" placeholder="jij@bedrijf.nl">
          <div class="gen-hint">Hier komen de link om te bevestigen en de reacties van je klant.</div>
        </div>
      </div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="van_iban">IBAN (waarop je klant betaalt)</label>
          <input type="text" id="van_iban" name="van_iban" maxlength="40" value="{{ old('van_iban') }}" placeholder="NL00 BANK 0123 4567 89">
        </div>
        <div class="m-field">
          <label for="van_kvk">KvK-nummer</label>
          <input type="text" id="van_kvk" name="van_kvk" maxlength="20" value="{{ old('van_kvk') }}" placeholder="12345678">
        </div>
      </div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="van_adres">Adres</label>
          <textarea id="van_adres" name="van_adres" rows="2" maxlength="300" placeholder="Straatnaam 1&#10;1234 AB Plaats">{{ old('van_adres') }}</textarea>
        </div>
        <div class="m-field">
          <label for="van_telefoon">Telefoon</label>
          <input type="text" id="van_telefoon" name="van_telefoon" maxlength="40" value="{{ old('van_telefoon') }}" placeholder="06 12 34 56 78">
        </div>
      </div>
      <input type="hidden" name="geen_btw_aftrek" value="0">
      <label class="gen-check">
        <input type="checkbox" name="geen_btw_aftrek" id="geen_btw_aftrek" value="1" @checked(old('geen_btw_aftrek'))>
        <span>Ik kan geen btw verrekenen, bijvoorbeeld door de kleineondernemersregeling. Over de incassokosten komt dan 21% btw.</span>
      </label>

      <div class="gen-section-title">Je klant</div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="aan_naam">Naam of bedrijfsnaam *</label>
          <input type="text" id="aan_naam" name="aan_naam" required maxlength="120" value="{{ old('aan_naam') }}" placeholder="De Vries Bouw B.V.">
        </div>
        <div class="m-field">
          <label for="klant">Soort klant *</label>
          <select id="klant" name="klant" required>
            <option value="zakelijk" @selected(old('klant', 'zakelijk') === 'zakelijk')>Zakelijk</option>
            <option value="particulier" @selected(old('klant') === 'particulier')>Particulier</option>
          </select>
        </div>
      </div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="aan_email">E-mailadres van je klant</label>
          <input type="email" id="aan_email" name="aan_email" maxlength="180" value="{{ old('aan_email') }}" placeholder="administratie@klant.nl">
          <div class="gen-hint">Na jouw bevestiging mailen wij de aanmaning hierheen. Leeg laten kan: dan stuur je de link of de brief zelf.</div>
        </div>
        <div class="m-field">
          <label for="aan_kvk">KvK-nummer van je klant</label>
          <input type="text" id="aan_kvk" name="aan_kvk" maxlength="8" inputmode="numeric" value="{{ old('aan_kvk') }}" placeholder="87654321">
          <div class="gen-hint">Als het er is, zoeken we je klant op in het handelsregister. Alleen jij ziet de uitkomst.</div>
        </div>
      </div>
      <div class="m-field">
        <label for="aan_adres">Adres</label>
        <textarea id="aan_adres" name="aan_adres" rows="2" maxlength="300" placeholder="Straatnaam 2&#10;5678 CD Plaats">{{ old('aan_adres') }}</textarea>
      </div>

      <div class="gen-section-title">De factuur</div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="factuurnummer">Factuurnummer *</label>
          <input type="text" id="factuurnummer" name="factuurnummer" required maxlength="40" value="{{ old('factuurnummer') }}" placeholder="{{ date('Y') }}-001">
        </div>
        <div class="m-field">
          <label for="bedrag">Openstaand bedrag, inclusief btw *</label>
          <input type="text" inputmode="decimal" id="bedrag" name="bedrag" required maxlength="20" value="{{ old('bedrag') }}" placeholder="1.250,00">
        </div>
      </div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="vervaldatum">Vervaldatum *</label>
          <input type="date" id="vervaldatum" name="vervaldatum" required min="{{ config('rente.from') }}" max="{{ now()->subDay()->toDateString() }}" value="{{ old('vervaldatum') }}">
          <div class="gen-hint">De laatste dag waarop je klant had moeten betalen.</div>
        </div>
        <div class="m-field">
          <label for="factuurdatum">Factuurdatum</label>
          <input type="date" id="factuurdatum" name="factuurdatum" max="{{ now()->toDateString() }}" value="{{ old('factuurdatum') }}">
        </div>
      </div>
      <div class="m-row-2">
        <div class="m-field">
          <label for="termijn">Termijn in dagen</label>
          <input type="number" id="termijn" name="termijn" min="{{ $terms['min_zakelijk'] }}" max="{{ $terms['max'] }}" value="{{ old('termijn') }}" placeholder="{{ $terms['zakelijk'] }}">
          <div class="gen-hint">Leeg laten: {{ $terms['zakelijk'] }} dagen voor een zakelijke klant, {{ $terms['particulier'] }} dagen voor een particulier.</div>
        </div>
        <div class="m-field">
          <label for="factuur">Kopie van de factuur</label>
          <input type="file" id="factuur" name="factuur" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
          <div class="gen-hint">PDF, JPG of PNG, maximaal 8 MB. Je klant krijgt haar als bijlage en via de pagina. Geen "wij hebben de factuur nooit ontvangen" meer.</div>
        </div>
      </div>
      <input type="hidden" name="rente" value="0">
      <label class="gen-check">
        <input type="checkbox" name="rente" id="rente" value="1" @checked(old('rente', '1'))>
        <span>Wettelijke rente meerekenen, vanaf de dag na de vervaldatum.</span>
      </label>

      {{-- Berekening vooraf. Zonder JavaScript blijft dit blok weg; de aanmaning klopt dan net zo goed. --}}
      <div class="calc-box" id="calcBox" hidden>
        <div class="calc-title">Wat er in de aanmaning komt</div>
        <div class="calc-row"><span>Openstaand bedrag</span><span id="cPrincipal"></span></div>
        <div class="calc-row" id="cInterestRow"><span id="cInterestLabel">Wettelijke rente</span><span id="cInterest"></span></div>
        <div class="calc-row grand"><span>Te betalen vandaag</span><span id="cTotal"></span></div>
        <div class="calc-row"><span id="cCostsLabel">Incassokosten na de termijn</span><span id="cCosts"></span></div>
        <div class="calc-note" id="cNote"></div>
      </div>

      @if(config('services.turnstile.sitekey'))
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.sitekey') }}" style="margin-bottom:14px;"></div>
        @error('cf-turnstile-response')<div class="m-err">{{ $message }}</div>@enderror
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
      @endif

      <button type="submit" class="btn btn-primary btn-lg btn-block">Maak de aanmaning, gratis →</button>
      <div class="privacy-note">Je krijgt eerst een mail om je adres te bevestigen. Tot die tijd gaat er niets naar je klant. We maken de aanmaning in jouw opdracht en uit jouw naam; {{ brand('name') }} is geen gemachtigde en geen incassobureau. De gegevens gebruiken we alleen voor deze aanmaning.</div>
    </form>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <h2>Zo werkt het</h2>
    </div>
    <ol class="steps">
      @foreach ($steps as $step)
        <li>{{ $step }}</li>
      @endforeach
    </ol>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>Veelgestelde vragen</h2>
    </div>
    <div class="faq-list">
      @foreach ($faq as [$question, $answer])
        <details class="faq-item">
          <summary>{{ $question }}<svg class="faq-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></summary>
          <div class="faq-content">{{ $answer }}</div>
        </details>
      @endforeach
    </div>
    <p class="legal-note">Grondslag: artikel 6:96, 6:119 en 6:119a van het Burgerlijk Wetboek, het Besluit vergoeding voor buitengerechtelijke incassokosten en artikel 3:317 en 3:318 BW (stuiting van de verjaring). Dit hulpmiddel is informatief en vervangt geen juridisch advies. Meer lezen: <a href="{{ route('kennisbank.artikel', 'betalingstermijn-aanmanen') }}">betalingstermijnen en aanmanen</a>, <a href="{{ route('kennisbank.artikel', 'incassokosten-wettelijke-rente-berekenen') }}">incassokosten en wettelijke rente</a>, de <a href="{{ route('incassokosten-calculator') }}">calculator</a> en het <a href="{{ route('help.article', 'online-aanmaning') }}">helpcentrum</a>.</p>
  </div>
</section>

<section class="cta-final">
  <div class="container cta-inner">
    <h2>Vaker een klant die niet betaalt?</h2>
    <p>In {{ brand('name') }} gaan herinneringen vanzelf de deur uit, verstuur je de aanmaning vanaf de factuur en sluit een betaling haar vanzelf. De eerste 14 dagen zijn gratis.</p>
    <div class="hero-ctas">
      <a href="{{ route('register') }}" class="btn btn-white btn-lg">Start gratis →</a>
    </div>
  </div>
</section>

<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn ($item) => [
        '@type' => 'Question',
        'name' => $item[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item[1]],
    ], $faq),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

<script>
(function () {
  var form = document.getElementById('genForm');
  var terms = @json($terms);
  var byId = function (id) { return document.getElementById(id); };

  // Onthoud alleen je eigen bedrijfsgegevens, lokaal in je eigen browser (gedeeld met de gratis factuur).
  var ownFields = ['van_bedrijf', 'van_email', 'van_adres', 'van_kvk', 'van_iban'];
  try {
    var saved = JSON.parse(localStorage.getItem('ei_gratis_factuur') || '{}');
    ownFields.forEach(function (id) {
      var el = byId(id);
      if (el && !el.value && saved[id]) el.value = saved[id];
    });
  } catch (e) {}

  // De termijn volgt de soort klant.
  function syncTerm() {
    var consumer = byId('klant').value === 'particulier';
    var field = byId('termijn');
    field.min = consumer ? terms.particulier : terms.min_zakelijk;
    field.placeholder = consumer ? terms.particulier : terms.zakelijk;
    if (field.value && Number(field.value) < Number(field.min)) field.value = field.min;
  }

  // Berekening vooraf: alleen bedragen en datums gaan naar de server, geen namen of adressen.
  var timer = null;
  function calculate() {
    if (!byId('bedrag').value || !byId('vervaldatum').value) { byId('calcBox').hidden = true; return; }
    var params = new URLSearchParams({
      bedrag: byId('bedrag').value,
      vervaldatum: byId('vervaldatum').value,
      klant: byId('klant').value,
      termijn: byId('termijn').value,
      rente: byId('rente').checked ? '1' : '0',
      geen_btw_aftrek: byId('geen_btw_aftrek').checked ? '1' : '0',
      aan_email: byId('aan_email').value ? '1' : '0'
    });
    fetch(@json(route('aanmaning.calculation')) + '?' + params.toString(), { headers: { Accept: 'application/json' } })
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (data) {
        if (!data) { byId('calcBox').hidden = true; return; }
        byId('cPrincipal').textContent = data.principal;
        byId('cInterestRow').style.display = data.with_interest ? '' : 'none';
        byId('cInterestLabel').textContent = data.interest_label;
        byId('cInterest').textContent = data.interest;
        byId('cTotal').textContent = data.total;
        byId('cCostsLabel').textContent = data.costs_label;
        byId('cCosts').textContent = data.costs;
        byId('cNote').textContent = data.note;
        byId('calcBox').hidden = false;
      })
      .catch(function () { byId('calcBox').hidden = true; });
  }
  function later() { clearTimeout(timer); timer = setTimeout(calculate, 350); }

  ['bedrag', 'vervaldatum', 'termijn', 'aan_email'].forEach(function (id) { byId(id).addEventListener('input', later); });
  ['rente', 'geen_btw_aftrek'].forEach(function (id) { byId(id).addEventListener('change', calculate); });
  byId('klant').addEventListener('change', function () { syncTerm(); calculate(); });
  syncTerm();
  calculate();

  form.addEventListener('submit', function () {
    try {
      var data = JSON.parse(localStorage.getItem('ei_gratis_factuur') || '{}');
      ownFields.forEach(function (id) { var el = byId(id); if (el) data[id] = el.value; });
      localStorage.setItem('ei_gratis_factuur', JSON.stringify(data));
    } catch (err) {}
  });
})();
</script>
@endif
@endsection
