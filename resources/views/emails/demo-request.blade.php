{{-- Nieuwe demo-aanvraag, aan de eigenaar (Nederlands). Zelfde huisstijl als de andere systeemmails (kop met beeldmerk). --}}
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo-aanvraag {{ brand('name') }}</title>
    <style>
        body { margin: 0; padding: 0; background: #FAFAF9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1C1917; }
        .wrapper { width: 100%; background: #FAFAF9; padding: 40px 16px; }
        .container { max-width: 520px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(28,25,23,0.08); }
        .header { background: linear-gradient(135deg, {{ brand('color') }} 0%, {{ brand('color_dark') }} 100%); padding: 28px 36px; color: white; }
        .logo { display: flex; align-items: center; gap: 10px; font-size: 20px; font-weight: 700; letter-spacing: -0.01em; }
        .logo-mark { width: 34px; height: 34px; display: block; border: 0; }
        .body { padding: 32px 36px; }
        .eyebrow { margin: 0 0 8px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: #78716C; }
        h1 { font-size: 22px; font-weight: 600; letter-spacing: -0.015em; margin: 0 0 20px; color: #1C1917; }
        p { font-size: 15px; line-height: 1.6; color: #44403C; margin: 0 0 16px; }
        .facts { width: 100%; border-collapse: collapse; font-size: 14px; }
        .facts td { padding: 9px 0; border-top: 1px solid #F5F5F4; vertical-align: top; }
        .facts tr:first-child td { border-top: 0; }
        .facts .k { width: 110px; color: #78716C; }
        .facts .v { color: #1C1917; font-weight: 500; }
        .msg-label { margin: 22px 0 6px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: #78716C; }
        .msg { background: {{ brand('color_tint') }}; border: 1px solid {{ brand('color_tint_border') }}; border-left: 4px solid {{ brand('color') }}; border-radius: 10px; padding: 14px 16px; font-size: 15px; line-height: 1.6; color: #1C1917; white-space: pre-line; }
        .btn-wrap { text-align: center; margin: 28px 0 8px; }
        .btn { display: inline-block; background: {{ brand('color') }}; color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 600; padding: 13px 26px; border-radius: 8px; }
        .hint { font-size: 13px; color: #78716C; text-align: center; margin-top: 6px; }
        .meta { font-size: 13px; color: #78716C; line-height: 1.6; margin-top: 24px; padding-top: 20px; border-top: 1px solid #E7E5E4; }
        .meta a { color: {{ brand('color') }}; }
        .footer { padding: 20px 36px 28px; font-size: 12px; color: #A8A29E; text-align: center; }
    </style>
</head>
<body style="margin:0;padding:0;background:#FAFAF9;">
    <div class="wrapper" style="padding:40px 16px;background:#FAFAF9;">
        <div class="container" style="max-width:520px;margin:0 auto;background:#FFFFFF;border-radius:14px;overflow:hidden;">
            <div class="header" style="background:{{ brand('color') }};padding:28px 36px;color:#ffffff;">
                <div class="logo" style="font-size:20px;font-weight:700;color:#ffffff;">
                    <img src="{{ \App\Support\Brand::asset('icon') }}" class="logo-mark" alt="{{ brand('name') }}" width="34" height="34" style="vertical-align:middle;margin-right:10px;">
                    <span style="vertical-align:middle;">{{ brand('name') }}</span>
                </div>
            </div>
            <div class="body" style="padding:32px 36px;">
                <p class="eyebrow">Demo op aanvraag</p>
                <h1>{{ $demoRequest->displayName() }} wil de demo zien</h1>

                <table class="facts" role="presentation" cellpadding="0" cellspacing="0">
                    @if($demoRequest->name)
                        <tr><td class="k">Naam</td><td class="v">{{ $demoRequest->name }}</td></tr>
                    @endif
                    <tr><td class="k">E-mail</td><td class="v"><a href="mailto:{{ $demoRequest->email }}" style="color:{{ brand('color') }};text-decoration:none;">{{ $demoRequest->email }}</a></td></tr>
                    @if($demoRequest->company)
                        <tr><td class="k">Bedrijf</td><td class="v">{{ $demoRequest->company }}</td></tr>
                    @endif
                    <tr><td class="k">Taal</td><td class="v">{{ strtoupper((string) $demoRequest->locale) }}</td></tr>
                    <tr><td class="k">Aangevraagd</td><td class="v">{{ $demoRequest->created_at?->translatedFormat('j F Y \o\m H:i') }}</td></tr>
                    @if($demoRequest->ip)
                        <tr><td class="k">IP-adres</td><td class="v" style="font-weight:400;color:#44403C;">{{ $demoRequest->ip }}</td></tr>
                    @endif
                </table>

                @if($demoRequest->message)
                    <p class="msg-label">Bericht van de aanvrager</p>
                    <div class="msg" style="background:{{ brand('color_tint') }};border-left:4px solid {{ brand('color') }};border-radius:10px;padding:14px 16px;">{{ $demoRequest->message }}</div>
                @endif

                <div class="btn-wrap">
                    <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto;">
                        <tr>
                            <td bgcolor="{{ brand('color') }}" style="border-radius:8px;background:{{ brand('color') }};">
                                <a href="{{ $reviewUrl }}" class="btn" style="display:inline-block;padding:13px 26px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">Beoordelen: goedkeuren of afwijzen&nbsp;&nbsp;→</a>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="hint">Zonder jouw goedkeuring start er geen demo.</div>

                <div class="meta">
                    Keur je goed, dan krijgt de aanvrager een persoonlijke link die {{ \App\Models\DemoRequest::VALID_DAYS }} dagen werkt.
                    Alle aanvragen staan ook in de app onder <a href="{{ $ownerUrl }}" style="color:{{ brand('color') }};">Eigenaar → Demo-aanvragen</a>.
                    Beantwoord deze mail om de aanvrager direct te schrijven.
                </div>
            </div>
            <div class="footer">
                © {{ date('Y') }} {{ brand('name') }} · {{ brand('positioning') }}
            </div>
        </div>
    </div>
</body>
</html>
