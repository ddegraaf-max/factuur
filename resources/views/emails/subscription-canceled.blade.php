<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Je EasyInvoice-abonnement is opgezegd</title>
    <style>
        body { margin: 0; padding: 0; background: #FAFAF9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1C1917; }
        .wrapper { width: 100%; background: #FAFAF9; padding: 40px 16px; }
        .container { max-width: 520px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(28,25,23,0.08); }
        .header { background: linear-gradient(135deg, #E8231F 0%, #B81814 100%); padding: 28px 36px; color: white; }
        .logo { display: flex; align-items: center; gap: 10px; font-size: 20px; font-weight: 700; letter-spacing: -0.01em; }
        .logo-mark { width: 34px; height: 34px; display: block; border: 0; }
        .body { padding: 36px 36px 32px; }
        h1 { font-size: 22px; font-weight: 600; letter-spacing: -0.015em; margin: 0 0 12px; color: #1C1917; }
        p { font-size: 15px; line-height: 1.6; color: #44403C; margin: 0 0 16px; }
        .info-box { background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 10px; padding: 18px 20px; margin: 24px 0; }
        .info-box .label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #78716C; margin-bottom: 4px; }
        .info-box .value { font-size: 15px; font-weight: 600; color: #1C1917; }
        .btn { display: inline-block; background: #E8231F; color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 600; padding: 13px 26px; border-radius: 8px; }
        .btn-wrap { text-align: center; margin: 28px 0 8px; }
        .price { font-size: 13px; color: #78716C; text-align: center; margin-top: 6px; }
        .feats { margin: 8px 0 0; padding: 0; list-style: none; }
        .feats li { font-size: 14px; color: #44403C; padding: 5px 0 5px 24px; position: relative; }
        .feats li:before { content: '✓'; position: absolute; left: 0; color: #059669; font-weight: 700; }
        .meta { font-size: 13px; color: #78716C; margin-top: 24px; padding-top: 20px; border-top: 1px solid #E7E5E4; }
        .footer { padding: 20px 36px 28px; font-size: 12px; color: #A8A29E; text-align: center; }
        .footer a { color: #78716C; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <div class="logo">
                    <img src="{{ rtrim(config('app.url'), '/') }}/images/easyinvoice-icon-512.png" class="logo-mark" alt="EasyInvoice">
                    <span>EasyInvoice</span>
                </div>
            </div>
            <div class="body">
                <h1>Hi {{ $firstName }},</h1>

                <p>Je hebt je EasyInvoice-abonnement opgezegd. Jammer dat je gaat — bedankt dat je EasyInvoice hebt gebruikt.</p>

                @if ($ended)
                    <p>Je toegang tot de app is nu beëindigd. <strong>Je account, facturen en gegevens blijven gewoon bewaard</strong> — je kunt op elk moment weer verdergaan waar je gebleven was.</p>
                @else
                    <div class="info-box">
                        <div class="label">Toegang tot en met</div>
                        <div class="value">{{ $accessUntil }}</div>
                    </div>
                    <p>Tot die datum houd je volledige toegang. Daarna stopt je abonnement automatisch. <strong>Je account, facturen en gegevens blijven bewaard</strong>.</p>
                @endif

                <p>Van gedachten veranderd? Je kunt met één klik weer verder — voor <strong>€10 per maand</strong> (excl. btw) heb je meteen weer alles:</p>
                <ul class="feats">
                    <li>Onbeperkt facturen, klanten en producten</li>
                    <li>BTW automatisch · herinneringen · incasso</li>
                    <li>Maandelijks opzegbaar — geen verplichtingen</li>
                </ul>

                <div class="btn-wrap">
                    <a href="{{ $billingUrl }}" class="btn">{{ $ended ? 'Weer activeren' : 'Abonnement hervatten' }}</a>
                </div>
                <div class="price">Veilig betalen · maandelijks opzegbaar</div>

                <div class="meta">
                    Ging er iets mis of missen we iets? We horen het graag — je feedback helpt ons echt verder. Mail ons op <a href="mailto:hallo@easyinvoice.nl" style="color:#E8231F;">hallo@easyinvoice.nl</a>.
                </div>
            </div>
            <div class="footer">
                © {{ date('Y') }} EasyInvoice · Nederlandse facturatie voor MKB en ZZP
            </div>
        </div>
    </div>
</body>
</html>
