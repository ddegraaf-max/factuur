<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>Factuur {{ $data['factuurnummer'] }}</title>
<style>
  @page { margin: 24mm 22mm 26mm 22mm; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1C1917; line-height: 1.6; }
  h1 { margin: 0; font-size: 26pt; font-weight: 700; letter-spacing: -1px; }
  .meta { color: #78716C; font-size: 10pt; margin-top: 4px; }

  .parties { width: 100%; margin: 40px 0 44px; }
  .parties td { vertical-align: top; width: 50%; padding-right: 24px; }
  .party-label { font-size: 8pt; text-transform: uppercase; letter-spacing: 0.12em; color: #A8A29E; margin-bottom: 6px; }
  .party-name { font-weight: 700; font-size: 11pt; }
  .party-line { color: #44403C; font-size: 9.5pt; }

  .lines { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  .lines th {
    text-align: left; padding: 10px 0 7px; font-size: 8pt; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.1em; color: #78716C; border-bottom: 2px solid #1C1917;
  }
  .lines th.right, .lines td.right { text-align: right; }
  .lines td { padding: 10px 0; border-bottom: 1px solid #E7E5E4; font-size: 10pt; vertical-align: top; }

  .totals { width: 260px; margin-left: auto; margin-top: 12px; border-collapse: collapse; }
  .totals td { padding: 4px 0; font-size: 10pt; color: #44403C; }
  .totals td.value { text-align: right; }
  .totals .grand td { border-top: 2px solid #1C1917; padding-top: 10px; font-weight: 700; font-size: 12.5pt; color: #1C1917; }

  .note { clear: both; margin-top: 44px; font-size: 9.5pt; color: #44403C; }
  .pay { margin-top: 16px; font-size: 9.5pt; color: #44403C; }
  .credit { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 8pt; color: #A8A29E; text-align: center; }
</style>
</head>
<body>

<h1>Factuur</h1>
<div class="meta">
  Factuurnummer {{ $data['factuurnummer'] }}
  &nbsp;·&nbsp; Factuurdatum {{ \Carbon\Carbon::parse($data['factuurdatum'])->format(market('date_format')) }}
  @if (!empty($data['vervaldatum']))
    &nbsp;·&nbsp; Vervaldatum {{ \Carbon\Carbon::parse($data['vervaldatum'])->format(market('date_format')) }}
  @endif
</div>

<table class="parties">
  <tr>
    <td>
      <div class="party-label">Van</div>
      <div class="party-name">{{ $data['van_bedrijf'] }}</div>
      @foreach (preg_split('/\r\n|\r|\n/', (string) ($data['van_adres'] ?? '')) as $line)
        @if (trim($line) !== '')<div class="party-line">{{ $line }}</div>@endif
      @endforeach
      @if (!empty($data['van_email']))<div class="party-line">{{ $data['van_email'] }}</div>@endif
      @if (!empty($data['van_kvk']))<div class="party-line">KvK {{ $data['van_kvk'] }}</div>@endif
      @if (!empty($data['van_btw']))<div class="party-line">Btw-id {{ $data['van_btw'] }}</div>@endif
    </td>
    <td>
      <div class="party-label">Aan</div>
      <div class="party-name">{{ $data['aan_bedrijf'] }}</div>
      @foreach (preg_split('/\r\n|\r|\n/', (string) ($data['aan_adres'] ?? '')) as $line)
        @if (trim($line) !== '')<div class="party-line">{{ $line }}</div>@endif
      @endforeach
    </td>
  </tr>
</table>

<table class="lines">
  <thead>
    <tr>
      <th style="width: 46%;">Omschrijving</th>
      <th class="right" style="width: 12%;">Aantal</th>
      <th class="right" style="width: 16%;">Prijs</th>
      <th class="right" style="width: 10%;">Btw</th>
      <th class="right" style="width: 16%;">Bedrag</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($rows as $row)
      <tr>
        <td>{{ $row['omschrijving'] }}</td>
        <td class="right">{{ rtrim(rtrim(number_format($row['aantal'], 2, ',', '.'), '0'), ',') }}</td>
        <td class="right">{{ money($row['prijs']) }}</td>
        <td class="right">{{ $data['btw_type'] === 'normaal' ? $row['btw'].'%' : '—' }}</td>
        <td class="right">{{ money($row['bedrag']) }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<table class="totals">
  <tr>
    <td>Subtotaal (excl. btw)</td>
    <td class="value">{{ money($subtotal) }}</td>
  </tr>
  @foreach ($vatTotals as $rate => $amount)
    <tr>
      <td>Btw {{ $rate }}%</td>
      <td class="value">{{ money($amount) }}</td>
    </tr>
  @endforeach
  <tr class="grand">
    <td>Totaal</td>
    <td class="value">{{ money($total) }}</td>
  </tr>
</table>

@if ($data['btw_type'] === 'verlegd')
  <div class="note"><strong>Btw verlegd.</strong> De btw is verlegd naar de afnemer (artikel 12 Wet OB).</div>
@elseif ($data['btw_type'] === 'vrijgesteld')
  <div class="note"><strong>Vrijgesteld van btw.</strong> Op deze factuur is geen btw van toepassing (bijvoorbeeld door de kleineondernemersregeling of een vrijstelling).</div>
@endif

@if (!empty($data['opmerking']))
  <div class="note">{{ $data['opmerking'] }}</div>
@endif

@if (!empty($data['van_iban']))
  <div class="pay">
    Graag het totaalbedrag
    @if (!empty($data['vervaldatum'])) vóór {{ \Carbon\Carbon::parse($data['vervaldatum'])->format(market('date_format')) }} @endif
    overmaken naar {{ $data['van_iban'] }} ten name van {{ $data['van_bedrijf'] }},
    onder vermelding van factuurnummer {{ $data['factuurnummer'] }}.
  </div>
@endif

<div class="credit">Gemaakt met {{ brand('name') }} &mdash; gratis facturen maken op {{ brand('domain') }}</div>

</body>
</html>
