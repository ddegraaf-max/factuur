@php
    $money = fn ($v) => money($v);
    $whole = fn ($v) => money($v, true, 0);
    $brand = $company->brand_color ?: brand('color');
    $typeLabel = ['quarter' => 'kwartaal', 'month' => 'maand', 'year' => 'jaar'][$period_type] ?? 'kwartaal';
    $statusLabel = function ($p) {
        if ($p['paid']) return 'Aangegeven en betaald';
        if ($p['filed']) return 'Aangegeven';
        if ($p['declaration_due']) return 'Aangifte doen vóór ' . $p['deadline_label'];
        return ['closed' => 'Afgesloten', 'current' => 'Loopt nu', 'future' => 'Nog niet begonnen'][$p['status']] ?? '';
    };
    $show = fn ($r) => in_array($r['key'], ['1a', '1b', '1e', '5a', '5b', '5c'], true)
        || abs((float) ($r['base'] ?? 0)) > 0.004 || abs((float) $r['vat']) > 0.004;
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>Btw-aangifte {{ $year }}</title>
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
  .period { margin-bottom: 14px; border: 1px solid #E7E5E4; border-radius: 8px; padding: 12px 14px; page-break-inside: avoid; }
  .p-head { width: 100%; margin-bottom: 8px; }
  .p-title { font-size: 12pt; font-weight: 700; }
  .p-months { color: #78716C; font-size: 8.5pt; }
  .p-status { text-align: right; font-size: 8.5pt; color: #44403C; }
  .p-vat { text-align: right; font-size: 13pt; font-weight: 800; }
  table.lines { width: 100%; border-collapse: collapse; margin-top: 4px; }
  table.lines th { text-align: left; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.05em; color: #78716C; padding: 5px 6px; border-bottom: 1px solid #D6D3D1; background: #FAFAF9; }
  table.lines td { padding: 5px 6px; border-bottom: 1px solid #E7E5E4; }
  table.lines .right { text-align: right; }
  table.lines .total td { font-weight: 700; }
  table.lines .grand td { font-weight: 800; border-bottom: none; }
  table.lines .whole { color: #78716C; font-size: 8.5pt; }
  .rubriek { display: inline-block; background: #EFEEEC; border-radius: 4px; padding: 0 4px; font-size: 7.5pt; font-weight: 700; margin-right: 5px; min-width: 14px; text-align: center; }
  .pay { margin-top: 8px; font-size: 8.5pt; color: #44403C; background: #FAFAF9; border: 1px solid #E7E5E4; border-radius: 6px; padding: 6px 10px; }
  .pay b { font-family: 'DejaVu Sans Mono', monospace; }
  .foot-note { color: #78716C; font-size: 8pt; margin-top: 6px; }
  .disclaimer { margin-top: 14px; font-size: 8pt; color: #A8A29E; line-height: 1.55; }
</style>
</head>
<body>
  <table class="header">
    <tr>
      <td>
        <h1>Btw-aangifte {{ $year }}</h1>
        <div class="sub">Omzetbelasting per {{ $typeLabel }} · alle rubrieken · opgesteld op {{ $generated_at }}</div>
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
      <td class="kpi"><div class="lbl">Omzet excl. btw</div><div class="val">{{ $money($totals['base']) }}</div></td>
      <td class="kpi"><div class="lbl">Btw over omzet (5a)</div><div class="val">{{ $money($totals['vat']) }}</div></td>
      <td class="kpi"><div class="lbl">Voorbelasting (5b)</div><div class="val">{{ $money($totals['input_vat']) }}</div></td>
      <td class="kpi tint"><div class="lbl">{{ $totals['balance'] < 0 ? 'Terug te ontvangen' : 'Per saldo te betalen' }} {{ $year }}</div><div class="val">{{ $money($totals['balance']) }}</div></td>
    </tr>
  </table>

  @foreach($periods as $p)
    <div class="period">
      <table class="p-head">
        <tr>
          <td>
            <span class="p-title">{{ $p['label'] }}</span>
            <span class="p-months">· {{ $p['months'] }}</span>
          </td>
          <td class="p-status">
            {{ $statusLabel($p) }}@if($p['filed_at_label']) ({{ $p['filed_at_label'] }})@endif<br>
            <span class="p-vat">{{ $whole($p['balance_rounded']) }}</span>
          </td>
        </tr>
      </table>

      <table class="lines">
        <thead>
          <tr>
            <th>Rubriek</th>
            <th class="right">Grondslag</th>
            <th class="right">Afgerond</th>
            <th class="right">Btw</th>
            <th class="right">Afgerond</th>
          </tr>
        </thead>
        <tbody>
          @foreach($p['rubrieken'] as $r)
            @continue(! $show($r))
            <tr class="{{ $r['source'] === 'total' ? ($r['key'] === '5c' ? 'grand' : 'total') : '' }}">
              <td><span class="rubriek">{{ $r['key'] }}</span> {{ $r['label'] }}</td>
              <td class="right">{{ $r['base'] === null ? '' : $money($r['base']) }}</td>
              <td class="right whole">{{ $r['base_rounded'] === null ? '' : $whole($r['base_rounded']) }}</td>
              <td class="right">{{ $r['no_vat'] ? '—' : $money($r['vat']) }}</td>
              <td class="right whole">{{ $r['no_vat'] ? '' : $whole($r['vat_rounded']) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>

      @if($p['payment']['amount'] > 0)
        <div class="pay">
          Betalen: <b>{{ $whole($p['payment']['amount']) }}</b> naar <b>{{ $p['payment']['iban'] }}</b> t.n.v. {{ $p['payment']['beneficiary'] }}@if($p['payment']['reference_formatted']), betalingskenmerk <b>{{ $p['payment']['reference_formatted'] }}</b>@endif — uiterlijk {{ $p['deadline_label'] }}.
        </div>
      @endif

      <div class="foot-note">
        {{ $p['invoice_count'] }} {{ $p['invoice_count'] === 1 ? 'verkoopfactuur' : 'verkoopfacturen' }}@if($p['credit_count']) · {{ $p['credit_count'] }} creditnota's @endif · {{ $p['purchase_count'] }} inkoopfacturen
        @if($p['status'] !== 'future') · aangifte en betaling uiterlijk {{ $p['deadline_label'] }} @endif
      </div>
    </div>
  @endforeach

  <div class="disclaimer">
    Berekend op factuurdatum (factuurstelsel) over alle verstuurde facturen en creditnota's in {{ brand('name') }}. 0%-regels zijn op klantland
    verdeeld over 1e (Nederland), 3b (EU) en 3a (buiten de EU). De voorbelasting (5b) komt uit de ingeboekte inkoopfacturen, plus wat u zelf
    hebt aangevuld. Afgeronde bedragen zijn in uw voordeel afgerond (te betalen btw en grondslagen omlaag, voorbelasting omhoog), zoals de
    Belastingdienst toestaat. Dit overzicht is een hulpmiddel — controleer de cijfers met uw boekhouder voordat u aangifte doet.
  </div>
</body>
</html>
