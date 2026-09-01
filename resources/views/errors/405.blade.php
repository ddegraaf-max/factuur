{{--
  405 Method Not Allowed in huisstijl. Laravel levert zelf nette pagina's voor
  401/403/404/419/429/500/503 maar niet voor 405, waardoor de kale Symfony-pagina
  verscheen. Bewust zonder de marketing-layout: bij een verkeerde aanroep
  draaien geen routemiddlewares (sessie, taal), dus hier alleen wat altijd werkt.
--}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{{ __('Verkeerde aanroep') }} · {{ brand('name') }}</title>
<link rel="icon" type="image/png" sizes="32x32" href="{{ brand('favicon_32') }}">
<style>
  :root { --brand: {{ brand('color') }}; --brand-dark: {{ brand('color_dark') }}; --bg: {{ brand('background') }};
    --text: #1c1917; --text-2: #57534e; --surface: #ffffff; --border: #e7e5e4; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color: var(--text); background: var(--bg);
    line-height: 1.55; -webkit-font-smoothing: antialiased; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 40px 36px; max-width: 520px; width: 100%;
    box-shadow: 0 10px 30px rgba(0,0,0,.05); }
  .mark { width: 56px; height: 56px; border-radius: 14px; display: block; margin-bottom: 20px; }
  .eyebrow { font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--brand); margin-bottom: 8px; }
  h1 { font-size: 24px; line-height: 1.25; font-weight: 800; letter-spacing: -.01em; margin-bottom: 12px; }
  p { color: var(--text-2); font-size: 15px; }
  code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 13px; background: var(--bg); border: 1px solid var(--border); border-radius: 6px; padding: 1px 6px; color: var(--text); word-break: break-all; }
  .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; }
  .btn { display: inline-flex; align-items: center; justify-content: center; padding: 11px 18px; border-radius: 10px; font-weight: 600; font-size: 15px;
    border: 1px solid var(--border); background: var(--surface); color: var(--text); text-decoration: none; }
  .btn:hover { border-color: #d6d3d1; }
  .btn-brand { background: var(--brand); border-color: var(--brand); color: #fff; }
  .btn-brand:hover { background: var(--brand-dark); border-color: var(--brand-dark); }
  .help { margin-top: 24px; font-size: 13px; }
  .help a { color: var(--brand); font-weight: 600; text-decoration: none; }
</style>
</head>
<body>
<main class="card">
  @if(brand('mark'))<img class="mark" src="{{ \App\Support\Brand::asset('mark') }}" alt="">@endif
  <div class="eyebrow">{{ __('Fout 405') }}</div>
  <h1>{{ __('Dit adres kun je zo niet openen') }}</h1>
  <p>{!! __('Het adres :path hoort bij een knop of formulier in :brand en is niet bedoeld om rechtstreeks te openen. Met je gegevens is niets gebeurd.', [
      'path' => '<code>/' . e(request()->path()) . '</code>',
      'brand' => e(brand('name')),
  ]) !!}</p>
  <div class="actions">
    {{-- De inlogpagina stuurt wie al is ingelogd door naar het dashboard. --}}
    <a class="btn btn-brand" href="{{ route('login') }}">{{ __('Naar :brand', ['brand' => brand('name')]) }}</a>
    <a class="btn" href="{{ url('/') }}">{{ __('Startpagina') }}</a>
  </div>
  <p class="help">{!! __('Blijft dit gebeuren? Mail :email.', ['email' => '<a href="mailto:' . e(brand('email')) . '">' . e(brand('email')) . '</a>']) !!}</p>
</main>
</body>
</html>
