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
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 20px 22px; margin-bottom: 20px; }
  .card h2 { font-size: 16px; margin: 0 0 14px; }
  .chart { display: flex; align-items: flex-end; gap: 3px; height: 140px; }
  .chart .bar { flex: 1; min-width: 4px; background: #FECACA; border-radius: 2px 2px 0 0; position: relative; }
  .chart .bar .inner { position: absolute; bottom: 0; left: 0; right: 0; background: var(--brand); border-radius: 2px 2px 0 0; }
  .chart .bar:hover::after {
    content: attr(data-tip); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
    background: var(--text); color: white; font-size: 11px; padding: 4px 8px; border-radius: 6px; white-space: nowrap; z-index: 5; margin-bottom: 4px;
  }
  .legend { font-size: 12px; color: var(--text-3); margin-top: 8px; }
  .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
  @media (max-width: 800px) { .cols { grid-template-columns: 1fr; } }
  table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
  th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); padding: 6px 0; border-bottom: 1px solid var(--border); }
  th.num, td.num { text-align: right; font-variant-numeric: tabular-nums; }
  td { padding: 7px 0; border-bottom: 1px solid var(--border); color: var(--text-2); }
  tr:last-child td { border-bottom: none; }
  .empty { color: var(--text-3); font-size: 13.5px; padding: 12px 0; }
  a.back { color: var(--text-3); font-size: 13px; text-decoration: none; }
  a.back:hover { color: var(--brand); }
</style>
</head>
<body>
<div class="wrap">
  <a class="back" href="{{ route('dashboard') }}">← Terug naar {{ brand('name') }}</a>
  <h1 style="margin-top:14px;">Marketing-inzichten</h1>
  <div class="sub">Publieke pagina's, afgelopen {{ $days }} dagen. First-party gemeten, zonder cookies — bots en ingelogde gebruikers tellen niet mee.</div>
  <div class="sub" style="margin-top:6px;"><a href="{{ route('brand.index') }}">Merkbewaking →</a> verwarringslog en maandelijkse merkgebruik-dossiers. &nbsp;·&nbsp; <a href="{{ route('owner.companies.index') }}">Administraties →</a> alle accounts, testaccounts opruimen.</div>

  <div class="kpis">
    <div class="kpi"><div class="label">Unieke bezoekers</div><div class="value">{{ number_format($totals['visitors'], 0, ',', '.') }}</div></div>
    <div class="kpi"><div class="label">Paginaweergaven</div><div class="value">{{ number_format($totals['views'], 0, ',', '.') }}</div></div>
    <div class="kpi"><div class="label">Demo's gestart</div><div class="value">{{ number_format($totals['demo_starts'], 0, ',', '.') }}</div></div>
    <div class="kpi"><div class="label">Registraties</div><div class="value" style="color:var(--success);">{{ number_format($totals['registrations'], 0, ',', '.') }}</div></div>
  </div>

  <div class="card">
    <h2>Bezoek per dag</h2>
    @php $max = max(1, $series->max('views')); @endphp
    <div class="chart">
      @foreach ($series as $day)
        <div class="bar" style="height: {{ max(2, round($day['views'] / $max * 100)) }}%;"
             data-tip="{{ \Carbon\Carbon::parse($day['date'])->format('d-m') }}: {{ $day['views'] }} weergaven, {{ $day['visitors'] }} bezoekers">
          <div class="inner" style="height: {{ $day['views'] > 0 ? round($day['visitors'] / max(1, $day['views']) * 100) : 0 }}%;"></div>
        </div>
      @endforeach
    </div>
    <div class="legend">Lichte balk = paginaweergaven, donkere vulling = unieke bezoekers. Beweeg over een balk voor de aantallen.</div>
  </div>

  <div class="cols">
    <div class="card">
      <h2>Populairste pagina's</h2>
      @if ($topPages->isEmpty())
        <div class="empty">Nog geen metingen — de teller loopt vanaf nu.</div>
      @else
        <table>
          <tr><th>Pagina</th><th class="num">Weergaven</th><th class="num">Bezoekers</th></tr>
          @foreach ($topPages as $page)
            <tr><td>{{ $page->path }}</td><td class="num">{{ $page->views }}</td><td class="num">{{ $page->visitors }}</td></tr>
          @endforeach
        </table>
      @endif
    </div>

    <div class="card">
      <h2>Herkomst (externe sites)</h2>
      @if ($topReferrers->isEmpty())
        <div class="empty">Nog geen externe verwijzingen gezien.</div>
      @else
        <table>
          <tr><th>Site</th><th class="num">Weergaven</th></tr>
          @foreach ($topReferrers as $ref)
            <tr><td>{{ $ref->referrer_host }}</td><td class="num">{{ $ref->views }}</td></tr>
          @endforeach
        </table>
      @endif

      <h2 style="margin-top:24px;">Campagnes (utm_source)</h2>
      @if ($topSources->isEmpty())
        <div class="empty">Nog geen campagneverkeer. Tip: gebruik links als <code>?utm_source=linkedin</code> in posts en mails.</div>
      @else
        <table>
          <tr><th>Bron</th><th class="num">Weergaven</th></tr>
          @foreach ($topSources as $source)
            <tr><td>{{ $source->utm_source }}</td><td class="num">{{ $source->views }}</td></tr>
          @endforeach
        </table>
      @endif
    </div>
  </div>
</div>
</body>
</html>
