<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Je demo van :brand staat klaar', ['brand' => brand('name')]) }}</title>
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
        .btn-td { border-radius: 8px; background: {{ brand('color') }}; }
        .btn { display: inline-block; padding: 13px 26px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px; }
        .url { font-size: 12px; color: #78716C; word-break: break-all; }
        .meta { font-size: 13px; color: #78716C; margin-top: 24px; padding-top: 20px; border-top: 1px solid #E7E5E4; line-height: 1.6; }
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
                <h1>{{ __('Je demo staat klaar') }}</h1>
                <p>{{ __('Je vroeg toegang tot de demo van :brand. Die is goedgekeurd: met de knop hieronder start je een eigen, afgeschermde voorbeeldomgeving. Alle gegevens zijn voorbeelden en er gaat geen e-mail naar echte ontvangers.', ['brand' => brand('name')]) }}</p>

                <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 24px 0 8px;">
                    <tr>
                        <td class="btn-td">
                            <a href="{{ $url }}" class="btn">{{ __('Start de demo') }}&nbsp;&nbsp;→</a>
                        </td>
                    </tr>
                </table>
                <p class="url">{{ __('Werkt de knop niet? Kopieer dan deze link:') }} {{ $url }}</p>

                <div class="meta">
                    {{ __('De link werkt tot :date en mag je vaker gebruiken; elke start is een verse omgeving die na 24 uur vanzelf wordt opgeruimd.', ['date' => $expires]) }}
                    {{ __('Vroeg je geen demo aan? Dan kun je deze e-mail negeren.') }}
                </div>
            </div>
            <div class="footer">
                © {{ date('Y') }} {{ brand('name') }} · {{ brand('positioning') }}
            </div>
        </div>
    </div>
</body>
</html>
