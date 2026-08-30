<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Uitnodiging :brand', ['brand' => brand('name')]) }}</title>
    <style>
        body { margin: 0; padding: 0; background: #FAFAF9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1C1917; }
        .wrapper { width: 100%; background: #FAFAF9; padding: 40px 16px; }
        .container { max-width: 520px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(28,25,23,0.08); }
        .header { background: linear-gradient(135deg, {{ brand('color') }} 0%, {{ brand('color_dark') }} 100%); padding: 28px 36px; color: white; }
        .logo { display: flex; align-items: center; gap: 10px; font-size: 20px; font-weight: 700; letter-spacing: -0.01em; }
        .logo-mark { width: 34px; height: 34px; display: block; border: 0; }
        .body { padding: 36px 36px 32px; }
        h1 { font-size: 22px; font-weight: 600; letter-spacing: -0.015em; margin: 0 0 12px; color: #1C1917; }
        p { font-size: 15px; line-height: 1.6; color: #44403C; margin: 0 0 16px; }
        .role-box { background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 10px; padding: 14px 18px; margin: 20px 0; font-size: 14px; }
        .role-box strong { color: #1C1917; }
        .btn-td { border-radius: 8px; background: {{ brand('color') }}; }
        .btn { display: inline-block; padding: 13px 26px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px; }
        .meta { font-size: 13px; color: #78716C; margin-top: 24px; padding-top: 20px; border-top: 1px solid #E7E5E4; }
        .footer { padding: 20px 36px 28px; font-size: 12px; color: #A8A29E; text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <div class="logo">
                    <img src="{{ \App\Support\Brand::asset('icon') }}" class="logo-mark" alt="{{ brand('name') }}">
                    <span>{{ brand('name') }}</span>
                </div>
            </div>
            <div class="body">
                <h1>{{ __('Je bent uitgenodigd') }} 🎉</h1>
                <p>{!! __('<strong>:inviter</strong> nodigt je uit om mee te werken in de :brand-omgeving van <strong>:company</strong>.', ['inviter' => e($inviter), 'brand' => e(brand('name')), 'company' => e($company)]) !!}</p>

                <div class="role-box">
                    {!! __('Je rol wordt: <strong>:role</strong>', ['role' => e(__($roleLabel))]) !!}
                </div>

                <p>{{ __('Klik op de knop, kies een wachtwoord en je kunt direct aan de slag — een eigen abonnement is niet nodig.') }}</p>

                <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 24px 0 8px;">
                    <tr>
                        <td class="btn-td">
                            <a href="{{ $url }}" class="btn">{{ __('Uitnodiging accepteren') }}&nbsp;&nbsp;→</a>
                        </td>
                    </tr>
                </table>

                <div class="meta">
                    {!! __('Deze uitnodiging is geldig tot <strong>:date</strong>.', ['date' => e($expires)]) !!}
                    {{ __('Verwachtte je deze e-mail niet? Dan kun je hem veilig negeren.') }}
                </div>
            </div>
            <div class="footer">
                © {{ date('Y') }} {{ brand('name') }} · {{ brand('positioning') }}
            </div>
        </div>
    </div>
</body>
</html>
