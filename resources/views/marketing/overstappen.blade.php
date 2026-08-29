@extends('layouts.marketing')

@section('title', 'Overstappen van ' . $from['name'] . ' naar ' . brand('name') . ' — in 10 minuten geregeld')
@section('description', 'Stap over van ' . $from['name'] . ' naar ' . brand('name') . ': klanten, producten en openstaande facturen neem je met de overstapwizard in tien minuten over. Onbeperkt factureren, offertes met digitale ondertekening, iDEAL, Peppol en incasso — zonder verbruikslimieten.')

@section('content')
<style>
  .ov-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-top:28px;}
  .ov-step{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:22px;box-shadow:var(--shadow-sm);}
  .ov-step .nr{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:100px;background:var(--brand);color:#fff;font-weight:700;font-size:14px;margin-bottom:12px;}
  .ov-step h3{font-size:17px;margin:0 0 8px;}
  .ov-step p{color:var(--text-2);line-height:1.65;margin:0;font-size:14.5px;}
  .ov-table{width:100%;border-collapse:collapse;font-size:14.5px;margin-top:22px;background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;}
  .ov-table th,.ov-table td{padding:13px 16px;border-bottom:1px solid var(--border);text-align:left;vertical-align:top;color:var(--text-2);}
  .ov-table th{background:var(--surface-2);color:var(--text);font-weight:600;}
  .ov-table td:first-child{color:var(--text);font-weight:500;width:34%;}
  .ov-table tr:last-child td{border-bottom:none;}
  .ov-yes{color:var(--success);font-weight:600;}
  .ov-note{font-size:13px;color:var(--text-3);margin-top:12px;line-height:1.6;}
  .ov-faq{max-width:760px;}
  .ov-faq h3{font-size:16px;margin:22px 0 6px;}
  .ov-faq p{color:var(--text-2);line-height:1.7;margin:0;}
  @media (max-width:820px){.ov-steps{grid-template-columns:minmax(0,1fr);}}
</style>

<section class="page-hero">
  <div class="container page-hero-inner">
    <span class="eyebrow">Overstappen van {{ $from['name'] }}</span>
    <h1>Van {{ $from['name'] }} naar {{ brand('name') }} in tien minuten</h1>
    <p class="lead">{{ $from['intro'] }} Je klanten, producten en openstaande facturen neem je zelf over met de overstapwizard — of je mailt ons je export en wij doen het.</p>
    <div class="hero-ctas" style="margin-top:28px;">
      <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Start gratis proefperiode →</a>
      <a href="{{ route('demo') }}" class="btn btn-secondary btn-lg">Bekijk de demo</a>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <h2>Zo stap je over</h2>
      <p>Drie stappen, geen dubbel invoerwerk. Je oude pakket kun je gewoon nog even laten draaien tot alles staat.</p>
    </div>
    <div class="ov-steps">
      <div class="ov-step"><div class="nr">1</div><h3>Exporteer in {{ $from['name'] }}</h3><p>{{ $from['export'] }}</p></div>
      <div class="ov-step"><div class="nr">2</div><h3>Upload in de overstapwizard</h3><p>In {{ brand('name') }} ga je naar Instellingen → Overstappen. Upload de CSV; de kolommen (naam, e-mail, adres, KvK, btw-nummer, prijzen) worden automatisch herkend. Je ziet een voorbeeld en klikt op importeren. Dubbele klanten worden overgeslagen.</p></div>
      <div class="ov-step"><div class="nr">3</div><h3>Openstaande facturen mee</h3><p>Exporteer alleen de nog open facturen (nummer, klant, datum, vervaldatum, bedrag). Ze komen als "verstuurd" binnen, zodat herinneringen, het debiteurenoverzicht en het klantenportaal meteen kloppen. Betaalde historie blijft in je oude pakket of in je auditfile.</p></div>
    </div>
    <p class="ov-note">Liever niet zelf? Mail je export naar <a href="mailto:{{ brand('email') }}">{{ brand('email') }}</a> — wij zetten hem kosteloos over, meestal dezelfde werkdag.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>Wat verandert er?</h2>
      <p>{{ $from['compare_intro'] }}</p>
    </div>
    <table class="ov-table">
      <thead><tr><th></th><th>{{ $from['name'] }}</th><th>{{ brand('name') }}</th></tr></thead>
      <tbody>
        @foreach($from['rows'] as $row)
          <tr><td>{{ $row[0] }}</td><td>{{ $row[1] }}</td><td class="ov-yes">{{ $row[2] }}</td></tr>
        @endforeach
      </tbody>
    </table>
    <p class="ov-note">{{ $from['compare_note'] }}</p>
  </div>
</section>

<section class="section section-alt">
  <div class="container ov-faq">
    <div class="section-header" style="text-align:left;">
      <h2>Veelgestelde vragen bij het overstappen</h2>
    </div>
    <h3>Blijven mijn factuurnummers doorlopen?</h3>
    <p>Ja. Bij Instellingen → Nummering stel je het startnummer en het formaat in, zodat je nieuwe facturen netjes aansluiten op je laatste nummer in {{ $from['name'] }}.</p>
    <h3>Wat gebeurt er met mijn betaalde facturen van vorige jaren?</h3>
    <p>Die hoef je niet over te nemen; de fiscale bewaarplicht (7 jaar) regel je met een export uit {{ $from['name'] }} of met een auditfile. {{ brand('name') }} maakt vanaf de overstap zelf ook een XAF-auditfile per boekjaar voor je accountant.</p>
    <h3>Kan mijn boekhouder meekijken?</h3>
    <p>Ja, gratis. Je nodigt hem of haar uit als boekhouder (alleen-lezen); daarnaast krijgt je boekhouder desgewenst automatisch een kopie (BCC) van elke factuur en een XAF-auditfile per jaar.</p>
    <h3>Zit ik ergens aan vast?</h3>
    <p>Nee: maandelijks opzegbaar, en je administratie exporteer je altijd volledig (ZIP met CSV en JSON) — ook als je later toch weer weg wilt.</p>
    <div style="margin-top:28px;"><a href="{{ route('register') }}" class="btn btn-primary btn-lg">Probeer 14 dagen gratis →</a></div>
  </div>
</section>
@endsection
