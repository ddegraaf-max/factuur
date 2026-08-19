<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>Jaaroverzicht {{ $year }}</title>
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

<h1>Jaaroverzicht {{ $year }}</h1>
<div class="sub">{{ $company->name }}@if($company->kvk_number) · KvK {{ $company->kvk_number }}@endif · gegenereerd op {{ $generated_at }} · bedragen exclusief btw</div>

<div class="sect">Resultaat uit facturatie</div>
<table>
  <thead>
    <tr><th>Kwartaal</th><th class="right">Omzet</th><th class="right">Kosten</th><th class="right">Kilometeraftrek</th><th class="right">Resultaat</th></tr>
  </thead>
  <tbody>
    @foreach($quarters as $q)
      <tr>
        <td>{{ $q['label'] }} {{ $year }}</td>
        <td class="num right">€ {{ number_format($q['revenue'], 2, ',', '.') }}</td>
        <td class="num right">@if($q['costs'])− € {{ number_format($q['costs'], 2, ',', '.') }}@else—@endif</td>
        <td class="num right">@if($q['km_amount'])− € {{ number_format($q['km_amount'], 2, ',', '.') }}@else—@endif</td>
        <td class="num right">€ {{ number_format($q['result'], 2, ',', '.') }}</td>
      </tr>
    @endforeach
    <tr class="total">
      <td>Totaal {{ $year }}</td>
      <td class="num right">€ {{ number_format($totals['revenue'], 2, ',', '.') }}</td>
      <td class="num right">− € {{ number_format($totals['costs'], 2, ',', '.') }}</td>
      <td class="num right">− € {{ number_format($totals['km_amount'], 2, ',', '.') }}</td>
      <td class="num right">€ {{ number_format($totals['result'], 2, ',', '.') }}</td>
    </tr>
  </tbody>
</table>

<div class="sect">Kosten per categorie</div>
<table>
  <thead>
    <tr><th>Categorie</th><th class="right">Aantal</th><th class="right">Bedrag excl. btw</th></tr>
  </thead>
  <tbody>
    @forelse($categories as $c)
      <tr>
        <td>{{ $c['name'] }}</td>
        <td class="num right">{{ $c['count'] }}</td>
        <td class="num right">€ {{ number_format($c['amount'], 2, ',', '.') }}</td>
      </tr>
    @empty
      <tr><td colspan="3">Geen inkoopfacturen in {{ $year }}.</td></tr>
    @endforelse
  </tbody>
</table>

<div class="sect">Kilometeradministratie</div>
<table>
  <tbody>
    <tr>
      <td>Zakelijke kilometers ({{ $totals['trip_count'] }} ritten)</td>
      <td class="num right">{{ number_format($totals['km'], 1, ',', '.') }} km</td>
      <td class="num right">€ {{ number_format($totals['km_amount'], 2, ',', '.') }}</td>
    </tr>
  </tbody>
</table>

@if($previous['revenue'] || $previous['costs'])
<div class="sect">Ter vergelijking: {{ $year - 1 }}</div>
<table>
  <tbody>
    <tr>
      <td>Omzet € {{ number_format($previous['revenue'], 2, ',', '.') }}</td>
      <td>Kosten € {{ number_format($previous['costs'], 2, ',', '.') }}</td>
      <td>Kilometeraftrek € {{ number_format($previous['km_amount'], 2, ',', '.') }}</td>
      <td class="right"><strong>Resultaat € {{ number_format($previous['result'], 2, ',', '.') }}</strong></td>
    </tr>
  </tbody>
</table>
@endif

<div class="box">
  <strong>Toelichting voor de aangifte inkomstenbelasting.</strong>
  Dit overzicht bevat de cijfers uit de facturatie-administratie: omzet op factuurdatum (factuurstelsel, creditnota's
  negatief, concepten niet meegeteld), ingeboekte inkoopfacturen en de kilometeraftrek van € 0,23 per zakelijke
  kilometer met een privévervoermiddel. Nog te verwerken door de boekhouder: afschrijvingen op bedrijfsmiddelen,
  eventuele loonkosten, bijtelling bij een zakelijke auto, voorraadmutaties en de ondernemersaftrekken
  (zelfstandigenaftrek, startersaftrek, MKB-winstvrijstelling). Dit document is een administratief overzicht,
  geen fiscale winst-en-verliesrekening.
</div>

<div class="footer">Gegenereerd met EasyInvoice · {{ $company->name }} · Jaaroverzicht {{ $year }}</div>

</body>
</html>
