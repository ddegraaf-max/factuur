<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Marketing-inzichten — {{ brand('name') }}</title>
<link rel="icon" type="image/png" sizes="32x32" href="{{ brand('favicon_32') }}">
<style>
  :root {
    --brand: {{ brand('color') }}; --text: #1C1917; --text-2: #44403C; --text-3: #78716C;
    --bg: #FAFAF9; --surface: #FFFFFF; --border: #E7E5E4; --success: #059669;
  }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: 'DM Sans', system-ui, sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; }
  .wrap { max-width: 1080px; margin: 0 auto; padding: 40px 24px 80px; }
  h1 { font-size: 26px; letter-spacing: -0.02em; margin: 0 0 4px; }
  .sub { color: var(--text-3); font-size: 14px; margin-bottom: 28px; }
  .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 28px; }
  @media (max-width: 700px) { .kpis { grid-template-columns: repeat(2, 1fr); } }
  .kpi { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 16px 18px; }
  .kpi .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-3); font-weight: 600; }
  .kpi .value { font-size: 28px; font-weight: 700; letter-spacing: -0.02em; }
  .kpi .hint { font-size: 12px; color: var(--text-3); margin-top: 2px; }
  .note { background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; border-radius: 12px; padding: 12px 16px; font-size: 13.5px; margin-bottom: 20px; }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px 22px; margin-bottom: 20px; }
  .card h2 { font-size: 16px; margin: 0 0 14px; }
  .chart { display: flex; align-items: flex-end; gap: 3px; height: 140px; }
  .chart .bar { flex: 1; min-width: 4px; background: #E7E5E4; border-radius: 2px 2px 0 0; position: relative; }
  .chart .bar .inner { position: absolute; bottom: 0; left: 0; right: 0; background: var(--brand); border-radius: 2px 2px 0 0; }
  .chart .bar:hover::after {
    content: attr(data-tip); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
    background: var(--text); color: white; font-size: 11px; padding: 4px 8px; border-radius: 6px; white-space: nowrap; z-index: 5; margin-bottom: 4px;
  }
  .legend { font-size: 12px; color: var(--text-3); margin-top: 8px; }
  .funnel-row { display: grid; grid-template-columns: 200px 1fr 60px; gap: 12px; align-items: center; padding: 6px 0; font-size: 13.5px; color: var(--text-2); }
  .funnel-row .track { background: #F5F5F4; border-radius: 6px; height: 14px; overflow: hidden; }
  .funnel-row .fill { background: var(--brand); height: 100%; border-radius: 6px; min-width: 2px; }
  .funnel-row .n { text-align: right; font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums; }
  @media (max-width: 600px) { .funnel-row { grid-template-columns: 130px 1fr 44px; } }
  .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
  @media (max-width: 800px) { .cols { grid-template-columns: 1fr; } }
  table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
  th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); padding: 6px 0; border-bottom: 1px solid var(--border); }
  th.num, td.num { text-align: right; padding-left: 16px; font-variant-numeric: tabular-nums; }
  td { padding: 7px 0; border-bottom: 1px solid var(--border); color: var(--text-2); }
  tr:last-child td { border-bottom: none; }
  .empty { color: var(--text-3); font-size: 13.5px; padding: 12px 0; }
  a.back { color: var(--text-3); font-size: 13px; text-decoration: none; }
  a.back:hover { color: var(--brand); }
</style>
</head>
<body>
@php $nr = fn ($n) => number_format((int) $n, 0, ',', '.'); @endphp
<div class="wrap">
  <a class="back" href="{{ route('dashboard') }}">← Terug naar {{ brand('name') }}</a>
  <h1 style="margin-top:14px;">Marketing-inzichten</h1>
  <div class="sub">Publieke pagina's, afgelopen {{ $days }} dagen. Zelf gemeten, zonder cookies. Een bezoek telt als mens zodra de browser een teken van leven geeft: scrollen, tikken of tien seconden kijken.</div>
  <div class="sub" style="margin-top:6px;"><a href="{{ route('brand.index') }}">Merkbewaking →</a> verwarringslog en maandelijkse merkgebruik-dossiers. &nbsp;·&nbsp; <a href="{{ route('owner.companies.index') }}">Administraties →</a> alle accounts, testaccounts opruimen.</div>

  @if (! $measuredSince)
    <div class="note">Mensen worden pas geteld sinds deze versie. Van de dagen daarvoor is alleen bekend hoeveel verzoeken er binnenkwamen en wie via een zoekmachine kwam.</div>
  @elseif (\Carbon\Carbon::parse($measuredSince)->gt(now()->subDays($days - 1)->startOfDay()))
    <div class="note">Mensen worden geteld sinds {{ \Carbon\Carbon::parse($measuredSince)->translatedFormat('j F Y') }}. De dagen daarvoor tellen alleen mee bij de verzoeken en bij de zoekmachines.</div>
  @endif

  <div class="kpis">
    <div class="kpi"><div class="label">Bezoekers</div><div class="value">{{ $nr($totals['people']) }}</div><div class="hint">mensen, per dag geteld</div></div>
    <div class="kpi"><div class="label">Via zoekmachine of AI</div><div class="value">{{ $nr($totals['search']) }}</div><div class="hint">Google, Bing, ChatGPT en andere</div></div>
    <div class="kpi"><div class="label">Robots en kale verzoeken</div><div class="value" style="color:var(--text-3);">{{ $nr($totals['robots']) }}</div><div class="hint">geen teken van leven</div></div>
    <div class="kpi"><div class="label">Registraties</div><div class="value" style="color:var(--success);">{{ $nr($totals['registrations']) }}</div><div class="hint">nieuwe administraties</div></div>
  </div>

  <div class="card">
    <h2>Bezoek per dag</h2>
    @php $max = max(1, $series->max('requests')); @endphp
    <div class="chart">
      @foreach ($series as $day)
        <div class="bar" style="height: {{ max(2, round($day['requests'] / $max * 100)) }}%;"
             data-tip="{{ \Carbon\Carbon::parse($day['date'])->format('d-m') }}: {{ $day['people'] }} mensen, {{ max(0, $day['requests'] - $day['people']) }} robots">
          <div class="inner" style="height: {{ $day['requests'] > 0 ? round($day['people'] / $day['requests'] * 100) : 0 }}%;"></div>
        </div>
      @endforeach
    </div>
    <div class="legend">Grijze balk = alle verzoeken, gekleurde vulling = mensen. Beweeg over een balk voor de aantallen.</div>
  </div>

  <div class="card">
    <h2>Van bezoek naar aanmelding</h2>
    @php $top = max(1, $funnel[0]['n']); @endphp
    @foreach ($funnel as $step)
      <div class="funnel-row">
        <div>{{ $step['label'] }}</div>
        <div class="track"><div class="fill" style="width: {{ min(100, round($step['n'] / $top * 100)) }}%;"></div></div>
        <div class="n">{{ $nr($step['n']) }}</div>
      </div>
    @endforeach
    <div class="legend">Formulier verstuurd telt elke poging, ook een die op de controle van de velden strandde. Is het verschil met Geregistreerd groot, dan verliest het formulier mensen.</div>
  </div>

  <div class="cols">
    <div class="card">
      <h2>Populairste pagina's</h2>
      @if ($topPages->isEmpty())
        <div class="empty">Nog geen bezoekers geteld.</div>
      @else
        <table>
          <tr><th>Pagina</th><th class="num">Bezoekers</th><th class="num">Weergaven</th></tr>
          @foreach ($topPages as $page)
            <tr><td>{{ $page->path }}</td><td class="num">{{ $nr($page->visitors) }}</td><td class="num">{{ $nr($page->views) }}</td></tr>
          @endforeach
        </table>
      @endif
    </div>

    <div class="card">
      <h2>Binnengekomen via zoekmachine of AI</h2>
      @if ($searchPages->isEmpty())
        <div class="empty">Nog niemand via een zoekmachine gezien.</div>
      @else
        <table>
          <tr><th>Eerste pagina</th><th class="num">Bezoekers</th></tr>
          @foreach ($searchPages as $page)
            <tr><td>{{ $page->path }}</td><td class="num">{{ $nr($page->visitors) }}</td></tr>
          @endforeach
        </table>
      @endif
    </div>
  </div>

  <div class="cols">
    <div class="card">
      <h2>Herkomst</h2>
      @if ($sources->isEmpty())
        <div class="empty">Nog geen herkomst gezien. Tip: gebruik links als <code>?utm_source=linkedin</code> in posts en mails.</div>
      @else
        <table>
          <tr><th>Site of campagne</th><th class="num">Bezoekers</th></tr>
          @foreach ($sources as $source)
            <tr><td>{{ $source->source }}</td><td class="num">{{ $nr($source->visitors) }}</td></tr>
          @endforeach
        </table>
      @endif
    </div>

    <div class="card">
      <h2>Aanmeldingen en hun herkomst</h2>
      @if ($signups->isEmpty())
        <div class="empty">Nog geen aanmeldingen in deze periode.</div>
      @else
        <table>
          <tr><th>Dag</th><th>Herkomst</th><th>Apparaat</th></tr>
          @foreach ($signups as $signup)
            <tr>
              <td>{{ $signup->viewed_on->format('d-m-Y') }}</td>
              <td>{{ $signup->utm_source ?: ($signup->referrer_host ?: 'Direct of onbekend') }}@if ($signup->utm_campaign) · {{ $signup->utm_campaign }}@endif</td>
              <td>{{ $signup->device === 'mobile' ? 'Mobiel' : 'Computer' }}</td>
            </tr>
          @endforeach
        </table>
      @endif
    </div>
  </div>
</div>
</body>
</html>
