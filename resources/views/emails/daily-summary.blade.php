@php
    $eur = fn ($n) => '€ ' . number_format((float) $n, 2, ',', '.');
    $appUrl = rtrim(config('app.url'), '/');
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Je dagoverzicht</title>
    <style>
        body { margin: 0; padding: 0; background: #FAFAF9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1C1917; }
        .wrapper { width: 100%; background: #FAFAF9; padding: 40px 16px; }
        .container { max-width: 560px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(28,25,23,0.08); }
        .header { background: linear-gradient(135deg, {{ brand('color') }} 0%, {{ brand('color_dark') }} 100%); padding: 28px 36px; color: white; }
        .logo { display: flex; align-items: center; gap: 10px; font-size: 20px; font-weight: 700; letter-spacing: -0.01em; }
        .logo-mark { width: 34px; height: 34px; display: block; border: 0; }
        .header-sub { font-size: 13px; opacity: 0.9; margin-top: 6px; }
        .body { padding: 32px 36px 28px; }
        h1 { font-size: 21px; font-weight: 600; letter-spacing: -0.015em; margin: 0 0 6px; }
        p { font-size: 15px; line-height: 1.6; color: #44403C; margin: 0 0 16px; }
        .kpis { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 4px 0 24px; }
        .kpi { background: #F5F5F4; border-radius: 10px; padding: 14px 16px; text-align: center; }
        .kpi.alert { background: #FEF2F2; border: 1px solid #FECACA; }
        .kpi.good { background: #DCFCE7; border: 1px solid #86EFAC; }
        .kpi-val { font-size: 19px; font-weight: 700; letter-spacing: -0.02em; color: #1C1917; }
        .kpi.alert .kpi-val { color: #7F1310; }
        .kpi.good .kpi-val { color: #15803D; }
        .kpi-lbl { font-size: 11px; font-weight: 600; color: #78716C; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 5px; }
        h2 { font-size: 14px; font-weight: 700; margin: 26px 0 10px; color: #1C1917; }
        .list { width: 100%; border-collapse: collapse; font-size: 14px; }
        .list td { padding: 9px 0; border-bottom: 1px solid #E7E5E4; color: #44403C; vertical-align: top; }
        .list tr:last-child td { border-bottom: none; }
        .list .who { font-weight: 600; color: #1C1917; }
        .list .nr { font-size: 12px; color: #A8A29E; }
        .list .amt { text-align: right; white-space: nowrap; font-weight: 600; }
        .late { color: #B81814; font-size: 12px; }
        .more { font-size: 13px; color: #78716C; padding-top: 10px; }
        .btn { display: inline-block; background: {{ brand('color') }}; color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 600; padding: 12px 24px; border-radius: 8px; }
        .btn-wrap { text-align: center; margin: 28px 0 4px; }
        .footer { padding: 20px 36px 28px; font-size: 12px; color: #A8A29E; text-align: center; line-height: 1.6; }
        .footer a { color: #78716C; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="{{ \App\Support\Brand::asset('email_mark') }}" class="logo-mark" alt="{{ brand('name') }}">
                <span>{{ brand('name') }}</span>
            </div>
            <div class="header-sub">Dagoverzicht · {{ now()->translatedFormat('l j F Y') }}</div>
        </div>

        <div class="body">
            <h1>Goedemorgen</h1>
            <p>Dit staat er vandaag open bij {{ $company->name }}.</p>

            <table class="kpis">
                <tr>
                    <td class="kpi {{ $s['overdue']['count'] > 0 ? 'alert' : '' }}" width="33%">
                        <div class="kpi-val">{{ $eur($s['overdue']['amount']) }}</div>
                        <div class="kpi-lbl">Vervallen</div>
                    </td>
                    <td class="kpi" width="33%">
                        <div class="kpi-val">{{ $eur($s['open']['amount']) }}</div>
                        <div class="kpi-lbl">Totaal open</div>
                    </td>
                    <td class="kpi {{ $s['paid_yesterday']['count'] > 0 ? 'good' : '' }}" width="33%">
                        <div class="kpi-val">{{ $eur($s['paid_yesterday']['amount']) }}</div>
                        <div class="kpi-lbl">Gisteren binnen</div>
                    </td>
                </tr>
            </table>

            @if ($s['overdue']['count'] > 0)
                <h2>⚠️ Vervallen facturen ({{ $s['overdue']['count'] }})</h2>
                <table class="list">
                    @foreach ($s['overdue']['items'] as $i)
                        <tr>
                            <td>
                                <span class="who">{{ $i['customer'] }}</span><br>
                                <span class="nr">{{ $i['number'] }}</span>
                                <span class="late">· {{ $i['days_overdue'] }} {{ $i['days_overdue'] === 1 ? 'dag' : 'dagen' }} te laat</span>
                            </td>
                            <td class="amt">{{ $eur($i['amount']) }}</td>
                        </tr>
                    @endforeach
                </table>
                @if ($s['overdue']['count'] > count($s['overdue']['items']))
                    <div class="more">en nog {{ $s['overdue']['count'] - count($s['overdue']['items']) }} andere…</div>
                @endif
            @endif

            @if ($s['paid_yesterday']['count'] > 0)
                <h2>✅ Gisteren betaald ({{ $s['paid_yesterday']['count'] }})</h2>
                <table class="list">
                    @foreach ($s['paid_yesterday']['items'] as $i)
                        <tr>
                            <td>
                                <span class="who">{{ $i['customer'] ?? 'Onbekend' }}</span><br>
                                <span class="nr">{{ $i['number'] }}</span>
                            </td>
                            <td class="amt">{{ $eur($i['amount']) }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @if ($s['due_soon']['count'] > 0)
                <h2>📅 Vervalt binnen 7 dagen ({{ $s['due_soon']['count'] }})</h2>
                <table class="list">
                    @foreach ($s['due_soon']['items'] as $i)
                        <tr>
                            <td>
                                <span class="who">{{ $i['customer'] }}</span><br>
                                <span class="nr">{{ $i['number'] }} · vervalt {{ $i['due_date'] }}</span>
                            </td>
                            <td class="amt">{{ $eur($i['amount']) }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @if ($s['drafts'] > 0)
                <h2>📝 Nog te versturen</h2>
                <p style="margin:0;">Je hebt <strong>{{ $s['drafts'] }}</strong> {{ $s['drafts'] === 1 ? 'concept' : 'concepten' }} klaarstaan die nog niet verstuurd zijn.</p>
            @endif

            @if ($s['incasso']['count'] > 0)
                <h2>⚖️ Bij incasso</h2>
                <p style="margin:0;">{{ $s['incasso']['count'] }} {{ $s['incasso']['count'] === 1 ? 'dossier' : 'dossiers' }} in behandeling · {{ $eur($s['incasso']['amount']) }}</p>
            @endif

            <div class="btn-wrap">
                <a href="{{ $appUrl }}/dashboard" class="btn">Open je dashboard</a>
            </div>
        </div>

        <div class="footer">
            Je ontvangt dit overzicht omdat de dagelijkse notificatie aanstaat.<br>
            Uitzetten kan bij <a href="{{ $appUrl }}/settings/company">Instellingen → Bedrijfsgegevens</a>.
        </div>
    </div>
</div>
</body>
</html>
