<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
<style>
  @page { margin: 16mm 15mm; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #1C1917; line-height: 1.5; }
  h1 { font-size: 19pt; margin: 0 0 2px; }
  .sub { color: #78716C; font-size: 9pt; margin-bottom: 18px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
  th { text-align: left; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.06em; color: #A8A29E; padding: 0 8px 6px; border-bottom: 2px solid #1C1917; }
  th.right, td.right { text-align: right; }
  td { padding: 6px 8px; border-bottom: 1px solid #EFEEEC; font-size: 9.5pt; vertical-align: top; }
  td.num { font-family: 'Courier', monospace; white-space: nowrap; }
  td.muted { color: #78716C; font-size: 8.5pt; }
  .total td { border-top: 2px solid #1C1917; border-bottom: none; font-weight: bold; }
  .sect { font-size: 11pt; font-weight: bold; margin: 18px 0 2px; }
  .sect-sub { color: #78716C; font-size: 8.5pt; margin: 0 0 8px; }
  .kpis { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px 16px; }
  .kpis td { background: #FAFAF9; border: 1px solid #E7E5E4; border-radius: 6px; padding: 10px 12px; width: 25%; }
  .kpis .lbl { font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.06em; color: #A8A29E; }
  .kpis .val { font-size: 13pt; font-weight: bold; margin-top: 2px; }
  .box { background: #FAFAF9; border-left: 3px solid #B45309; border-radius: 4px; padding: 10px 14px; font-size: 8.5pt; color: #44403C; margin-top: 10px; }
  .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #E7E5E4; font-size: 8pt; color: #A8A29E; }
</style>
</head>
<body>

<h1>{{ $title }}</h1>
<div class="sub">{{ $company->name }}@if($company->kvk_number) · {{ __('KvK :kvk', ['kvk' => $company->kvk_number]) }}@endif · {{ $subtitle }}</div>

<table class="kpis">
  <tr>
    <td><div class="lbl">{{ __('Online ontvangen') }}</div><div class="val">{{ money($totals['received']) }}</div></td>
    <td><div class="lbl">{{ __('Betalingen') }}</div><div class="val">{{ $totals['count'] }}</div></td>
    <td><div class="lbl">{{ __('In een uitbetaling') }}</div><div class="val">{{ money($totals['settled']) }}</div></td>
    <td><div class="lbl">{{ __('Nog niet uitbetaald') }}</div><div class="val">{{ money($totals['unsettled']) }}</div></td>
  </tr>
</table>

@forelse($groups as $group)
  <div class="sect">
    @if($group['settlement_id'])
      {{ __('Uitbetaling :reference', ['reference' => $group['reference'] ?: $group['settlement_id']]) }}
    @else
      {{ __('Nog niet uitbetaald (of nog niet opgehaald bij Mollie)') }}
    @endif
  </div>
  <div class="sect-sub">
    @if($group['settlement_id'])
      @if($group['settled_label']){{ __('Overgemaakt op :date', ['date' => $group['settled_label']]) }}@else{{ __('Nog niet overgemaakt') }}@endif
      @if($group['total'] !== null) · {{ __('Bedrag op bankafschrift: :amount', ['amount' => money($group['total'])]) }}@endif
      · {{ __('Kenmerk Mollie: :id', ['id' => $group['settlement_id']]) }}
    @else
      {{ __('Deze betalingen zijn binnen bij Mollie; het geld volgt in een volgende uitbetaling.') }}
    @endif
  </div>
  <table>
    <thead>
      <tr>
        <th>{{ __('Betaald op') }}</th>
        <th>{{ __('Factuur') }}</th>
        <th>{{ __('Klant') }}</th>
        <th>{{ __('Methode') }}</th>
        <th>{{ __('Mollie-betaling') }}</th>
        <th class="right">{{ __('Bedrag') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach($group['rows'] as $row)
        <tr>
          <td>{{ $row['paid_label'] }}</td>
          <td>{{ $row['number'] }}</td>
          <td>{{ $row['customer'] }}</td>
          <td>{{ $row['method'] }}</td>
          <td class="muted">{{ $row['mollie_id'] }}</td>
          <td class="num right">{{ money($row['amount']) }}</td>
        </tr>
      @endforeach
      <tr class="total">
        <td colspan="5">{{ __('Door klanten betaald (:n)', ['n' => $group['count']]) }}</td>
        <td class="num right">{{ money($group['gross']) }}</td>
      </tr>
      @if($group['total'] !== null)
        <tr>
          <td colspan="5">
            @if($group['difference'] < 0)
              {{ __('Kosten Mollie (verschil met de uitbetaling)') }}
            @elseif($group['difference'] > 0)
              {{ __('Andere ontvangsten in deze uitbetaling (niet via :brand)', ['brand' => brand('name')]) }}
            @else
              {{ __('Verschil met de uitbetaling') }}
            @endif
          </td>
          <td class="num right">{{ $group['difference'] < 0 ? '-' : '' }}{{ money(abs($group['difference'])) }}</td>
        </tr>
        <tr class="total">
          <td colspan="5">{{ __('Overgemaakt door Mollie') }}</td>
          <td class="num right">{{ money($group['total']) }}</td>
        </tr>
      @endif
    </tbody>
  </table>
@empty
  <p>{{ __('Geen online betalingen in deze periode.') }}</p>
@endforelse

<div class="box">
  {{ __('De bedragen per factuur zijn wat de klant heeft betaald. Mollie maakt die betalingen gebundeld over, minus zijn kosten; het bedrag op het bankafschrift is daardoor lager dan het totaal van de facturen. Het verschil boekt de boekhouder als betaalkosten.') }}
</div>

<div class="footer">{{ brand('name') }} · {{ __('gegenereerd op :date', ['date' => $generated_at]) }}</div>

</body>
</html>
