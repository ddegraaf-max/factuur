<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __(':brand verificatiecode', ['brand' => brand('name')]) }}</title>
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
        .code-box { background: #FEF2F2; border: 1px solid #FECACA; border-radius: 10px; padding: 22px; text-align: center; margin: 24px 0; }
        .code { font-family: 'SF Mono', Menlo, Consolas, monospace; font-size: 34px; font-weight: 700; letter-spacing: 0.4em; color: #7F1310; padding-left: 0.4em; }
        .code-label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: {{ brand('color_dark') }}; margin-bottom: 10px; }
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
                    <img src="{{ \App\Support\Brand::asset('icon') }}" class="logo-mark" alt="{{ brand('name') }}">
                    <span>{{ brand('name') }}</span>
                </div>
            </div>
            <div class="body">
                <h1>{{ __('Hi :name,', ['name' => $firstName]) }}</h1>
                <p>{{ __('Welkom bij :brand! Gebruik onderstaande code om je e-mailadres te bevestigen en je account te activeren.', ['brand' => brand('name')]) }}</p>

                <div class="code-box">
                    <div class="code-label">{{ __('Je verificatiecode') }}</div>
                    <div class="code">{{ $code }}</div>
                </div>

                <p>{!! __('Deze code is <strong>15 minuten</strong> geldig. Voer hem in op de verificatiepagina die je net geopend hebt.') !!}</p>

                <div class="meta">
                    {{ __('Heb jij dit niet aangevraagd? Dan kun je deze e-mail negeren — er gebeurt niets met je gegevens.') }}
                </div>
            </div>
            <div class="footer">
                © {{ date('Y') }} {{ brand('name') }} · {{ brand('positioning') }}
            </div>
        </div>
    </div>
</body>
</html>
