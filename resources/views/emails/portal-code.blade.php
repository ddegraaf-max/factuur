<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toegangscode klantenportaal</title>
    <style>
        body { margin: 0; padding: 0; background: #FAFAF9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1C1917; }
        .wrapper { width: 100%; background: #FAFAF9; padding: 40px 16px; }
        .container { max-width: 520px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(28,25,23,0.08); }
        .header { background: #1C1917; padding: 26px 36px; color: white; }
        .logo { font-size: 18px; font-weight: 700; letter-spacing: -0.01em; }
        .logo table { border-collapse: collapse; }
        .logo img { width: 34px; height: 34px; border: 0; border-radius: 9px; display: block; }
        .logo .name { font-size: 18px; font-weight: 700; color: #FFFFFF; padding-left: 10px; line-height: 1.2; }
        .logo .name span { color: #F87171; }
        .logo .tag { font-size: 12px; font-weight: 500; color: #A8A29E; padding-left: 10px; }
        .logo .accent { color: #FCA5A5; }
        .body { padding: 36px 36px 32px; }
        h1 { font-size: 21px; font-weight: 600; letter-spacing: -0.015em; margin: 0 0 12px; color: #1C1917; }
        p { font-size: 15px; line-height: 1.6; color: #44403C; margin: 0 0 16px; }
        .code-box { background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 10px; padding: 22px; text-align: center; margin: 24px 0; }
        .code { font-family: 'SF Mono', Menlo, Consolas, monospace; font-size: 34px; font-weight: 700; letter-spacing: 0.4em; color: #1C1917; padding-left: 0.4em; }
        .code-label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #78716C; margin-bottom: 10px; }
        .meta { font-size: 13px; color: #78716C; margin-top: 24px; padding-top: 20px; border-top: 1px solid #E7E5E4; }
        .footer { padding: 20px 36px 28px; font-size: 12px; color: #A8A29E; text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <div class="logo">
                    <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                        <td><img src="{{ \App\Support\Brand::asset('icon') }}" alt="{{ brand('name') }}"></td>
                        <td><div class="name">Easy<span>Invoice</span></div><div class="tag">🔒 Klantenportaal · beveiligde omgeving</div></td>
                    </tr></table>
                </div>
            </div>
            <div class="body">
                <h1>Je toegangscode</h1>
                <p>Met onderstaande code krijg je veilig toegang tot je facturen en offertes in het online klantenportaal.</p>

                <div class="code-box">
                    <div class="code-label">Toegangscode</div>
                    <div class="code">{{ $code }}</div>
                </div>

                <p>De code is <strong>10 minuten</strong> geldig en werkt alleen in het browservenster waarin je hem hebt aangevraagd.</p>

                <div class="meta">
                    Heb jij dit niet aangevraagd? Dan kun je deze e-mail veilig negeren —
                    zonder deze code krijgt niemand toegang tot je documenten.
                </div>
            </div>
            <div class="footer">
                © {{ date('Y') }} {{ brand('name') }} · Beveiligd klantenportaal voor facturen en offertes
            </div>
        </div>
    </div>
</body>
</html>
