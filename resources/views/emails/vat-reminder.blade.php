@php
    $eur = fn ($n) => ($n < 0 ? '− ' : '') . '€ ' . number_format(abs((float) $n), 2, ',', '.');
    $whole = fn ($n) => ($n < 0 ? '− ' : '') . '€ ' . number_format(abs((float) $n), 0, ',', '.');
    $appUrl = rtrim(config('app.url'), '/');
    $r = collect($p['rubrieken'])->keyBy('key');
    $days = (int) ($p['days_left'] ?? 0);
    $amount = (float) $p['payment']['amount'];
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Btw-aangifte {{ $p['label'] }} {{ $p['year'] }}</title>
    <style>
        body { margin: 0; padding: 0; background: #FAFAF9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1C1917; }
        .wrapper { width: 100%; background: #FAFAF9; padding: 40px 16px; }
        .container { max-width: 560px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(28,25,23,0.08); }
        .header { background: linear-gradient(135deg, #E8231F 0%, #B81814 100%); padding: 28px 36px; color: white; }
        .logo { display: flex; align-items: center; gap: 10px; font-size: 20px; font-weight: 700; letter-spacing: -0.01em; }
        .logo-mark { width: 34px; height: 34px; display: block; border: 0; }
        .header-sub { font-size: 13px; opacity: 0.9; margin-top: 6px; }
        .body { padding: 32px 36px 28px; }
        h1 { font-size: 21px; font-weight: 600; letter-spacing: -0.015em; margin: 0 0 6px; }
        p { font-size: 15px; line-height: 1.6; color: #44403C; margin: 0 0 16px; }
        .kpis { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 4px 0 20px; }
        .kpi { background: #F5F5F4; border-radius: 10px; padding: 14px 12px; text-align: center; }
        .kpi.tint { background: #FEF2F2; border: 1px solid #FECACA; }
        .kpi-val { font-size: 18px; font-weight: 700; letter-spacing: -0.02em; color: #1C1917; }
        .kpi.tint .kpi-val { color: #7F1310; }
        .kpi-lbl { font-size: 11px; font-weight: 600; color: #78716C; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 5px; }
        .pay { width: 100%; border-collapse: collapse; background: #FAFAF9; border: 1px solid #E7E5E4; border-radius: 10px; margin: 6px 0 20px; font-size: 14px; }
        .pay td { padding: 9px 14px; border-bottom: 1px solid #EBE9E6; }
        .pay tr:last-child td { border-bottom: none; }
        .pay .k { color: #78716C; width: 38%; }
        .pay .v { font-weight: 600; font-family: Menlo, Consolas, monospace; }
        .note { font-size: 13px; color: #78716C; line-height: 1.6; }
        .btn { display: inline-block; background: #E8231F; color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 600; padding: 12px 24px; border-radius: 8px; }
        .btn-wrap { text-align: center; margin: 24px 0 4px; }
        .footer { padding: 20px 36px 28px; font-size: 12px; color: #A8A29E; text-align: center; line-height: 1.6; }
        .footer a { color: #78716C; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="{{ $appUrl }}/images/easyinvoice-favicon-180.png" class="logo-mark" alt="EasyInvoice">
                <span>EasyInvoice</span>
            </div>
            <div class="header-sub">Btw-aangifte · {{ $p['label'] }} {{ $p['year'] }}</div>
        </div>

        <div class="body">
            <h1>{{ $final ? 'Laatste herinnering: ' : '' }}tijd voor je btw-aangifte</h1>
            <p>
                Het tijdvak <strong>{{ $p['label'] }} {{ $p['year'] }}</strong> van {{ $company->name }} is afgesloten.
                Aangifte én betaling moeten uiterlijk <strong>{{ $p['deadline_label'] }}</strong> binnen zijn bij de
                Belastingdienst — {{ $days === 0 ? 'dat is vandaag' : 'nog ' . $days . ($days === 1 ? ' dag' : ' dagen') }}.
            </p>

            <table class="kpis">
                <tr>
                    <td class="kpi" width="33%"><div class="kpi-val">{{ $eur($r['5a']['vat']) }}</div><div class="kpi-lbl">Btw over omzet (5a)</div></td>
                    <td class="kpi" width="33%"><div class="kpi-val">{{ $eur($r['5b']['vat']) }}</div><div class="kpi-lbl">Voorbelasting (5b)</div></td>
                    <td class="kpi tint" width="33%"><div class="kpi-val">{{ $whole($p['balance_rounded']) }}</div><div class="kpi-lbl">{{ $p['balance_rounded'] < 0 ? 'Terug te ontvangen' : 'Te betalen (5c)' }}</div></td>
                </tr>
            </table>

            @if($amount > 0)
                <p><strong>Zo betaal je:</strong></p>
                <table class="pay">
                    <tr><td class="k">Bedrag</td><td class="v">{{ $whole($amount) }}</td></tr>
                    <tr><td class="k">IBAN</td><td class="v">{{ $p['payment']['iban'] }}</td></tr>
                    <tr><td class="k">Ten name van</td><td class="v">{{ $p['payment']['beneficiary'] }}</td></tr>
                    @if($p['payment']['reference_formatted'])
                        <tr><td class="k">Betalingskenmerk</td><td class="v">{{ $p['payment']['reference_formatted'] }}</td></tr>
                    @endif
                </table>
                <p class="note">
                    @if($p['payment']['reference_formatted'])
                        Zet het betalingskenmerk in het veld "Betalingskenmerk" van je bankoverschrijving — zonder kenmerk kan de Belastingdienst je betaling niet verwerken.
                        @if($p['payment']['reference_source'] === 'auto') Het kenmerk is berekend uit je omzetbelastingnummer; controleer het met het kenmerk bij je ingestuurde aangifte. @endif
                    @else
                        Het betalingskenmerk vind je in Mijn Belastingdienst Zakelijk bij je ingestuurde aangifte. Stel je omzetbelastingnummer in op de btw-pagina, dan berekent EasyInvoice het voortaan zelf.
                    @endif
                </p>
            @elseif($p['balance_rounded'] < 0)
                <p class="note">Je krijgt per saldo btw terug. Dien de aangifte in; de Belastingdienst betaalt het bedrag uit na verwerking.</p>
            @endif

            <p class="note">Alle rubrieken staan klaar in EasyInvoice — in de indeling van Mijn Belastingdienst Zakelijk, dus overnemen is zo gedaan. Markeer het tijdvak daarna als aangegeven, dan stopt deze herinnering.</p>

            <div class="btn-wrap">
                <a href="{{ $appUrl }}/btw?year={{ $p['year'] }}" class="btn">Open je btw-aangifte</a>
            </div>
        </div>

        <div class="footer">
            Je ontvangt deze herinnering omdat die aanstaat bij Btw-aangifte → Instellingen in EasyInvoice.<br>
            Daar kun je hem ook uitzetten.
        </div>
    </div>
</div>
</body>
</html>
