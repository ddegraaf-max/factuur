@php
    $money = fn ($v) => ($v < 0 ? '− ' : '') . '€ ' . number_format(abs((float) $v), 2, ',', '.');
    $brand = $company->brand_color ?: '#E8231F';
    $statusLabels = ['closed' => 'Afgerond', 'current' => 'Loopt nu', 'future' => 'Nog niet begonnen'];
    $rateRows = [
        ['key' => '21', 'rubriek' => '1a', 'label' => 'Hoog tarief · 21%'],
        ['key' => '9',  'rubriek' => '1b', 'label' => 'Laag tarief · 9%'],
        ['key' => '0',  'rubriek' => '1e', 'label' => 'Nultarief · 0%'],
    ];
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>BTW-overzicht {{ $year }}</title>
<style>
  @page { margin: 16mm 15mm 16mm 15mm; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #1C1917; line-height: 1.5; }
  h1 { margin: 0; font-size: 20pt; font-weight: 800; letter-spacing: -0.5px; color: {{ $brand }}; }
  .sub { color: #78716C; font-size: 9pt; margin-top: 2px; }
  .header { width: 100%; border-bottom: 3px solid {{ $brand }}; padding-bottom: 12px; margin-bottom: 18px; }
  .header td { vertical-align: bottom; }
  .company { text-align: right; font-size: 9pt; color: #44403C; }
  .company .name { font-weight: 700; font-size: 10.5pt; color: #1C1917; }

  .kpis { width: 100%; margin-bottom: 16px; border-collapse: separate; border-spacing: 6px 0; }
  .kpi { width: 25%; background: #F5F5F4; border-radius: 8px; padding: 10px 12px; }
  .kpi.tint { background: #FEF2F2; }
  .kpi .lbl { font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.06em; color: #78716C; font-weight: 700; }
  .kpi .val { font-size: 12pt; font-weight: 700; margin-top: 2px; }
  .kpi.tint .val { color: {{ $brand }}; }

  .quarter { margin-bottom: 14px; border: 1px solid #E7E5E4; border-radius: 8px; padding: 12px 14px; page-break-inside: avoid; }
  .q-head { width: 100%; margin-bottom: 8px; }
  .q-title { font-size: 12pt; font-weight: 700; }
  .q-months { color: #78716C; font-size: 8.5pt; }
  .q-status { text-align: right; font-size: 8.5pt; color: #44403C; }
  .q-vat { text-align: right; font-size: 13pt; font-weight: 800; }

  table.lines { width: 100%; border-collapse: collapse; margin-top: 4px; }
  table.lines th { text-align: left; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.05em; color: #78716C; padding: 5px 6px; border-bottom: 1px solid #D6D3D1; background: #FAFAF9; }
  table.lines td { padding: 6px; border-bottom: 1px solid #E7E5E4; }
  table.lines .right { text-align: right; }
  table.lines .total td { font-weight: 700; border-bottom: none; }
  .rubriek { display: inline-block; background: #EFEEEC; border-radius: 4px; padding: 0 4px; font-size: 7.5pt; font-weight: 700; margin-right: 5px; }
  .foot-note { color: #78716C; font-size: 8pt; margin-top: 6px; }

  .disclaimer { margin-top: 14px; font-size: 8pt; color: #A8A29E; line-height: 1.55; }
</style>
</head>
<body>
  <table class="header">
    <tr>
      <td>
        <h1>BTW-overzicht {{ $year }}</h1>
        <div class="sub">Omzetbelasting per kwartaal · opgesteld op {{ $generated_at }}</div>
      </td>
      <td class="company">
        <div class="name">{{ $company->name }}</div>
        @if($company->kvk_number)<div>KVK {{ $company->kvk_number }}</div>@endif
        @if($company->vat_number)<div>BTW {{ $company->vat_number }}</div>@endif
      </td>
    </tr>
  </table>

  <table class="kpis">
    <tr>
      <td class="kpi"><div class="lbl">Omzet excl. BTW</div><div class="val">{{ $money($totals['base']) }}</div></td>
      <td class="kpi"><div class="lbl">BTW 21% (1a)</div><div class="val">{{ $money($totals['rates']['21']['vat']) }}</div></td>
      <td class="kpi"><div class="lbl">BTW 9% (1b)</div><div class="val">{{ $money($totals['rates']['9']['vat']) }}</div></td>
      <td class="kpi tint"><div class="lbl">Af te dragen {{ $year }}</div><div class="val">{{ $money($totals['vat']) }}</div></td>
    </tr>
  </table>

  @foreach($quarters as $q)
    <div class="quarter">
      <table class="q-head">
        <tr>
          <td>
            <span class="q-title">{{ $q['label'] }}</span>
            <span class="q-months">· {{ $q['months'] }} {{ $year }}</span>
          </td>
          <td class="q-status">
            {{ $statusLabels[$q['status']] ?? '' }}@if($q['declaration_due']) — aangifte vóór {{ $q['deadline_label'] }}@endif<br>
            <span class="q-vat">{{ $money($q['vat']) }}</span>
          </td>
        </tr>
      </table>

      <table class="lines">
        <thead>
          <tr>
            <th>Rubriek</th>
            <th class="right">Grondslag</th>
            <th class="right">BTW</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rateRows as $row)
            <tr>
              <td><span class="rubriek">{{ $row['rubriek'] }}</span> {{ $row['label'] }}</td>
              <td class="right">{{ $money($q['rates'][$row['key']]['base']) }}</td>
              <td class="right">{{ $row['key'] === '0' ? '—' : $money($q['rates'][$row['key']]['vat']) }}</td>
            </tr>
          @endforeach
          <tr class="total">
            <td>Totaal</td>
            <td class="right">{{ $money($q['base']) }}</td>
            <td class="right">{{ $money($q['vat']) }}</td>
          </tr>
        </tbody>
      </table>

      <div class="foot-note">
        {{ $q['invoice_count'] }} {{ $q['invoice_count'] === 1 ? 'factuur' : 'facturen' }}@if($q['credit_count']) · {{ $q['credit_count'] }} creditnota's @endif
        @if($q['status'] !== 'future') · aangifte en betaling uiterlijk {{ $q['deadline_label'] }} @endif
      </div>
    </div>
  @endforeach

  <div class="disclaimer">
    Berekend op factuurdatum (factuurstelsel) over alle verstuurde facturen en creditnota's in EasyInvoice.
    Voorbelasting (rubriek 5b) over inkoop en kosten is hierin niet opgenomen.
    Dit overzicht is een hulpmiddel — controleer de cijfers met je boekhouder voordat je aangifte doet.
  </div>
</body>
</html>
