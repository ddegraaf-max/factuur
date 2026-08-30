<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<title>{{ __('Jaaroverzicht :year', ['year' => $year]) }}</title>
<style>
  @page { margin: 16mm 15mm; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #1C1917; line-height: 1.5; }
  h1 { font-size: 19pt; margin: 0 0 2px; }
  .sub { color: #78716C; font-size: 9pt; margin-bottom: 18px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
  th { text-align: left; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.06em; color: #A8A29E; padding: 0 8px 6px; border-bottom: 2px solid #1C1917; }
  th.right, td.right { text-align: right; }
  td { padding: 7px 8px; border-bottom: 1px solid #EFEEEC; font-size: 9.5pt; }
  td.num { font-family: 'Courier', monospace; }
  .total td { border-top: 2px solid #1C1917; border-bottom: none; font-weight: bold; }
  .box { background: #FAFAF9; border-left: 3px solid #B45309; border-radius: 4px; padding: 11px 14px; font-size: 8.5pt; color: #44403C; margin-top: 6px; }
  .sect { font-size: 11pt; font-weight: bold; margin: 16px 0 8px; }
  .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #E7E5E4; font-size: 8pt; color: #A8A29E; }
</style>
</head>
<body>

<h1>{{ __('Jaaroverzicht :year', ['year' => $year]) }}</h1>
<div class="sub">{{ $company->name }}@if($company->kvk_number) · {{ __('KvK :kvk', ['kvk' => $company->kvk_number]) }}@endif · {{ __('gegenereerd op :date', ['date' => $generated_at]) }} · {{ __('bedragen exclusief btw') }}</div>

<div class="sect">{{ __('Resultaat uit facturatie') }}</div>
<table>
  <thead>
    <tr><th>{{ __('Kwartaal') }}</th><th class="right">{{ __('Omzet') }}</th><th class="right">{{ __('Kosten') }}</th><th class="right">{{ __('Kilometeraftrek') }}</th><th class="right">{{ __('Resultaat') }}</th></tr>
  </thead>
  <tbody>
    @foreach($quarters as $q)
      <tr>
        <td>{{ $q['label'] }} {{ $year }}</td>
        <td class="num right">{{ money($q['revenue']) }}</td>
        <td class="num right">@if($q['costs'])-&nbsp;{{ money($q['costs']) }}@else—@endif</td>
        <td class="num right">@if($q['km_amount'])-&nbsp;{{ money($q['km_amount']) }}@else—@endif</td>
        <td class="num right">{{ money($q['result']) }}</td>
      </tr>
    @endforeach
    <tr class="total">
      <td>{{ __('Totaal :year', ['year' => $year]) }}</td>
      <td class="num right">{{ money($totals['revenue']) }}</td>
      <td class="num right">-&nbsp;{{ money($totals['costs']) }}</td>
      <td class="num right">-&nbsp;{{ money($totals['km_amount']) }}</td>
      <td class="num right">{{ money($totals['result']) }}</td>
    </tr>
  </tbody>
</table>

<div class="sect">{{ __('Kosten per categorie') }}</div>
<table>
  <thead>
    <tr><th>{{ __('Categorie') }}</th><th class="right">{{ __('Aantal') }}</th><th class="right">{{ __('Bedrag excl. btw') }}</th></tr>
  </thead>
  <tbody>
    @forelse($categories as $c)
      <tr>
        <td>{{ $c['name'] }}</td>
        <td class="num right">{{ $c['count'] }}</td>
        <td class="num right">{{ money($c['amount']) }}</td>
      </tr>
    @empty
      <tr><td colspan="3">{{ __('Geen inkoopfacturen in :year.', ['year' => $year]) }}</td></tr>
    @endforelse
  </tbody>
</table>

<div class="sect">{{ __('Kilometeradministratie') }}</div>
<table>
  <tbody>
    <tr>
      <td>{{ __('Zakelijke kilometers (:count ritten)', ['count' => $totals['trip_count']]) }}</td>
      <td class="num right">{{ number_format($totals['km'], 1, market('decimal_separator', ','), market('thousands_separator', '.')) }} km</td>
      <td class="num right">{{ money($totals['km_amount']) }}</td>
    </tr>
  </tbody>
</table>

@if($previous['revenue'] || $previous['costs'])
<div class="sect">{{ __('Ter vergelijking: :year', ['year' => $year - 1]) }}</div>
<table>
  <tbody>
    <tr>
      <td>{{ __('Omzet') }} {{ money($previous['revenue']) }}</td>
      <td>{{ __('Kosten') }} {{ money($previous['costs']) }}</td>
      <td>{{ __('Kilometeraftrek') }} {{ money($previous['km_amount']) }}</td>
      <td class="right"><strong>{{ __('Resultaat') }} {{ money($previous['result']) }}</strong></td>
    </tr>
  </tbody>
</table>
@endif

<div class="box">
  <strong>{{ __('Toelichting voor de aangifte inkomstenbelasting.') }}</strong>
  {{ __('Dit overzicht bevat de cijfers uit de facturatie-administratie: omzet op factuurdatum (factuurstelsel, creditnota\'s negatief, concepten niet meegeteld), ingeboekte inkoopfacturen en de kilometeraftrek van :rate per zakelijke kilometer met een privévervoermiddel. Nog te verwerken door de boekhouder: afschrijvingen op bedrijfsmiddelen, eventuele loonkosten, bijtelling bij een zakelijke auto, voorraadmutaties en de ondernemersaftrekken (zelfstandigenaftrek, startersaftrek, MKB-winstvrijstelling). Dit document is een administratief overzicht, geen fiscale winst-en-verliesrekening.', ['rate' => money(market('km_rate'))]) }}
</div>

<div class="footer">{{ __('Gegenereerd met :brand', ['brand' => brand('name')]) }} · {{ $company->name }} · {{ __('Jaaroverzicht :year', ['year' => $year]) }}</div>

</body>
</html>
