{{-- Beoordeelpagina uit de mail aan de eigenaar (Nederlands, zonder inloggen; de link kent alleen de eigenaar). --}}
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>Demo-aanvraag · {{ brand('name') }}</title>
  <style>
    body { margin: 0; padding: 32px 16px; background: #f5f5f4; font-family: Inter, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; color: #1c1917; }
    .card { max-width: 560px; margin: 0 auto; background: #fff; border: 1px solid #e7e5e4; border-radius: 14px; padding: 28px; }
    .eyebrow { margin: 0 0 6px; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: #78716c; }
    h1 { margin: 0 0 16px; font-size: 21px; }
    table { border-collapse: collapse; width: 100%; font-size: 14px; }
    td { padding: 6px 0; vertical-align: top; }
    td:first-child { color: #78716c; width: 130px; }
    .msg { margin-top: 14px; padding: 14px 16px; background: #fafaf9; border-radius: 10px; font-size: 15px; line-height: 1.6; white-space: pre-line; }
    .status { margin-top: 16px; font-size: 13px; color: #78716c; }
    .actions { display: flex; gap: 10px; margin-top: 22px; flex-wrap: wrap; }
    .btn { display: inline-block; padding: 12px 22px; border-radius: 8px; border: 1px solid transparent; font: inherit; font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none; }
    .primary { background: {{ brand('color') }}; color: #fff; }
    .secondary { background: #fff; color: #1c1917; border-color: #d6d3d1; }
    .ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: 10px; padding: 12px 16px; font-size: 14px; }
    .warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; border-radius: 10px; padding: 12px 16px; font-size: 14px; }
    a { color: {{ brand('color') }}; }
    .foot { margin-top: 20px; font-size: 13px; color: #78716c; line-height: 1.6; }
  </style>
</head>
<body>
  <div class="card">
    <p class="eyebrow">{{ brand('name') }} · demo-aanvraag</p>
    @if (isset($done))
      @if ($done === 'approve')
        <h1>Goedgekeurd</h1>
        @if ($mailed)
          <div class="ok">{{ $demoRequest->email }} heeft de persoonlijke link per e-mail gekregen. De link werkt tot {{ $demoRequest->expires_at?->translatedFormat('j F Y') }}.</div>
        @else
          <div class="warn">Goedgekeurd, maar de mail aan {{ $demoRequest->email }} kon niet worden verstuurd. Stuur de link zelf: <a href="{{ route('demo.landing', $demoRequest->token) }}">{{ route('demo.landing', $demoRequest->token) }}</a></div>
        @endif
      @else
        <h1>Afgewezen</h1>
        <div class="warn">{{ $demoRequest->email }} krijgt geen link en geen bericht. Wil je toch reageren, beantwoord dan de aanvraagmail.</div>
      @endif
      <p class="foot"><a href="{{ route('owner.demo.index') }}">Naar alle demo-aanvragen in de app</a></p>
    @else
      <h1>{{ $demoRequest->displayName() }} wil de demo zien</h1>
      <table>
        @if($demoRequest->name)<tr><td>Naam</td><td>{{ $demoRequest->name }}</td></tr>@endif
        <tr><td>E-mail</td><td><a href="mailto:{{ $demoRequest->email }}">{{ $demoRequest->email }}</a></td></tr>
        @if($demoRequest->company)<tr><td>Bedrijf</td><td>{{ $demoRequest->company }}</td></tr>@endif
        <tr><td>Taal</td><td>{{ strtoupper((string) $demoRequest->locale) }}</td></tr>
        <tr><td>Aangevraagd</td><td>{{ $demoRequest->created_at?->translatedFormat('j F Y H:i') }}@if($demoRequest->ip) · {{ $demoRequest->ip }}@endif</td></tr>
      </table>
      @if($demoRequest->message)<div class="msg">{{ $demoRequest->message }}</div>@endif
      <div class="status">
        Status: {{ ['pending' => 'wacht op jouw oordeel', 'approved' => 'al goedgekeurd', 'denied' => 'al afgewezen'][$demoRequest->status] ?? $demoRequest->status }}
        @if($demoRequest->isApproved() && $demoRequest->expires_at) · link werkt tot {{ $demoRequest->expires_at->translatedFormat('j F Y') }} · {{ $demoRequest->starts }}× gestart @endif
      </div>
      <div class="actions">
        <form method="POST" action="{{ route('demo.decide', [$demoRequest->review_token, 'approve']) }}">
          @csrf
          <button type="submit" class="btn primary">{{ $demoRequest->isApproved() ? 'Verlengen en opnieuw mailen' : 'Goedkeuren en link mailen' }}</button>
        </form>
        <form method="POST" action="{{ route('demo.decide', [$demoRequest->review_token, 'deny']) }}">
          @csrf
          <button type="submit" class="btn secondary">Afwijzen</button>
        </form>
      </div>
      <p class="foot">Goedkeuren stuurt de aanvrager een persoonlijke link die {{ \App\Models\DemoRequest::VALID_DAYS }} dagen werkt (vaker te gebruiken, elke start een verse omgeving). Afwijzen stuurt niets.</p>
    @endif
  </div>
</body>
</html>
