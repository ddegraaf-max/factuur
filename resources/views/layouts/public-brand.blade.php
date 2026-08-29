{{-- Publieke pagina's in de huisstijl van een administratie (visitekaartje, website). --}}
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', $company->publicName())</title>
<meta name="description" content="@yield('description')">
<meta property="og:title" content="@yield('title', $company->publicName())">
<meta property="og:description" content="@yield('description')">
<meta property="og:type" content="website">
<meta name="robots" content="@yield('robots', 'index,follow')">
@if($company->logo_data)<link rel="icon" href="{{ $company->logo_data }}">@endif
<style>
  :root {
    --brand: {{ $company->brand_color ?: brand('color') }};
    --accent: {{ $company->accent_color ?: '#1C1917' }};
    --font: {!! ($company->invoice_font ?? 'sans') === 'serif' ? "Georgia, 'Times New Roman', serif" : "'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif" !!};
    --text: #1c1917; --text-2: #57534e; --bg: #fafaf9; --surface: #ffffff; --border: #e7e5e4;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html { scroll-behavior: smooth; }
  body { font-family: var(--font); color: var(--text); background: var(--bg); line-height: 1.55; -webkit-font-smoothing: antialiased; }
  a { color: inherit; text-decoration: none; }
  img { max-width: 100%; }
  .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 18px; border-radius: 10px; font-weight: 600; font-size: 15px; border: 1px solid var(--border); background: var(--surface); color: var(--text); cursor: pointer; transition: transform .12s, box-shadow .12s; }
  .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
  .btn-brand { background: var(--brand); color: #fff; border-color: var(--brand); }
  .made { text-align: center; font-size: 12px; color: var(--text-2); padding: 28px 16px; }
  .made a { color: var(--brand); font-weight: 600; }
@yield('styles')
</style>
</head>
<body>
@yield('content')
<div class="made">Gemaakt met <a href="{{ config('app.url') }}/?utm_source=publiek&utm_medium={{ $madeWith ?? 'kaart' }}" rel="noopener">{{ brand('name') }}</a> — factureren en je bedrijf presenteren, in één.</div>
</body>
</html>
