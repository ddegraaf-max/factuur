@php
    $confirm = $kind === 'confirm';
    $partner = \App\Support\Market::incasso('partner_name');
    $day = fn (?\Carbon\CarbonInterface $at) => $at?->translatedFormat('j F Y');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $confirm ? __('Bevestig je aanmaning') : __('Je aanmaning staat online') }}</title>
    <style>
        body { margin: 0; padding: 0; background: #FAFAF9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1C1917; }
        .wrapper { width: 100%; background: #FAFAF9; padding: 40px 16px; }
        .container { max-width: 560px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(28,25,23,0.08); }
        .header { background: linear-gradient(135deg, {{ brand('color') }} 0%, {{ brand('color_dark') }} 100%); padding: 28px 36px; color: white; }
        .logo { display: flex; align-items: center; gap: 10px; font-size: 20px; font-weight: 700; letter-spacing: -0.01em; }
        .logo-mark { width: 34px; height: 34px; display: block; border: 0; }
        .header-sub { font-size: 13px; opacity: 0.9; margin-top: 6px; }
        .body { padding: 32px 36px 28px; }
        h1 { font-size: 22px; font-weight: 600; letter-spacing: -0.015em; margin: 0 0 12px; color: #1C1917; }
        p { font-size: 15px; line-height: 1.6; color: #44403C; margin: 0 0 16px; }
        .facts { width: 100%; border-collapse: collapse; background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 10px; margin: 6px 0 20px; font-size: 14px; }
        .facts td { padding: 9px 14px; border-bottom: 1px solid #EBE9E6; vertical-align: top; }
        .facts tr:last-child td { border-bottom: none; }
        .facts .k { color: #78716C; width: 42%; }
        .facts .v { font-weight: 600; color: #1C1917; word-break: break-word; }
        .facts .v a { color: #1C1917; }
        .tip { background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 10px; padding: 12px 16px; font-size: 14px; line-height: 1.6; margin: 0 0 20px; color: #166534; }
        .warn { background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 10px; padding: 12px 16px; font-size: 14px; line-height: 1.6; margin: 0 0 18px; }
        .btn-td { border-radius: 8px; background: {{ brand('color') }}; }
        .btn { display: inline-block; padding: 13px 26px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px; }
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
                <div class="header-sub">{{ __('Factuur :number', ['number' => $demand->invoice_number]) }} · {{ $demand->debtor_name }}</div>
            </div>
            <div class="body">
                @if($confirm)
                    <h1>{{ __('Bevestig je aanmaning') }}</h1>
                    <p>{{ __('Je hebt op :domain een aanmaning gemaakt. Bevestig dat dit jouw e-mailadres is; pas daarna staat de aanmaning online.', ['domain' => brand('domain')]) }}
                        {{ filled($demand->sent_to)
                            ? __('Na je bevestiging mailen wij de aanmaning naar :email.', ['email' => $demand->sent_to])
                            : __('Je hebt geen e-mailadres van je klant ingevuld; na je bevestiging krijg je de link om zelf door te sturen.') }}</p>
                @else
                    <h1>{{ __('Je aanmaning staat online') }}</h1>
                    <p>{{ filled($demand->sent_to)
                            ? __('Wij hebben de aanmaning naar :email gemaild, uit jouw naam. Antwoordt je klant op die mail, dan komt dat bij jou.', ['email' => $demand->sent_to])
                            : __('Stuur de link hieronder naar je klant, of print de brief en doe hem op de post.') }}
                        {{ __('Het bedrag op de pagina loopt elke dag op. Reageert je klant, dan krijg je een mail.') }}</p>
                @endif

                <table class="facts" role="presentation">
                    <tr><td class="k">{{ __('Klant') }}</td><td class="v">{{ $demand->debtor_name }}</td></tr>
                    <tr><td class="k">{{ __('Factuur') }}</td><td class="v">{{ $demand->invoice_number }} · {{ __('vervallen op :date', ['date' => $day($demand->due_date)]) }}</td></tr>
                    <tr><td class="k">{{ __('Te betalen vandaag') }}</td><td class="v">{{ money($claim['total']) }}@if($claim['with_interest']) <span style="font-weight:400;color:#78716C;">({{ __('waarvan :amount rente', ['amount' => money($claim['interest'])]) }})</span>@endif</td></tr>
                    <tr><td class="k">{{ __('Incassokosten na de termijn') }}</td><td class="v">{{ money($claim['costs_total']) }}</td></tr>
                    @if(! $confirm)
                        <tr><td class="k">{{ __('Termijn tot en met') }}</td><td class="v">{{ $day($demand->deadline) }}</td></tr>
                        <tr><td class="k">{{ __('Link voor je klant') }}</td><td class="v"><a href="{{ $debtorUrl }}">{{ $debtorUrl }}</a></td></tr>
                        <tr><td class="k">{{ __('Brief om te printen') }}</td><td class="v"><a href="{{ $letterUrl }}">{{ __('PDF met QR-code') }}</a></td></tr>
                        @if($facts)<tr><td class="k">{{ __('Klant in het handelsregister') }}</td><td class="v">{{ $facts }}</td></tr>@endif
                    @endif
                </table>

                <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 8px 0 4px;">
                    <tr>
                        <td class="btn-td">
                            <a href="{{ $confirm ? $confirmUrl : $creditorUrl }}" class="btn">{{ $confirm ? __('Bekijk en bevestig') : __('Open je overzicht') }}&nbsp;&nbsp;→</a>
                        </td>
                    </tr>
                </table>

                @if($confirm)
                    <p style="margin-top:18px;font-size:13.5px;color:#78716C;">{{ __('De link is :days dagen geldig.', ['days' => $confirmDays]) }}</p>
                    <div class="warn">{{ __('Was jij dit niet? Iemand heeft dit adres ingevuld op :domain. Doe niets: zonder bevestiging wordt er niets verstuurd.', ['domain' => brand('domain')]) }}</div>
                @else
                    <p style="margin-top:18px;font-size:13.5px;color:#78716C;">{{ __('In je overzicht zie je wanneer je klant de aanmaning opent en wat hij antwoordt. Die link is alleen voor jou; stuur hem niet door.') }}</p>
                    <div class="tip">{{ __('Niet betaald als de termijn voorbij is? Dan draag je het dossier vanuit je overzicht met één klik over aan :partner.', ['partner' => $partner]) }}</div>
                @endif

                <div class="meta">
                    {{ __('Je ontvangt dit bericht omdat dit e-mailadres is ingevuld bij een aanmaning op :domain.', ['domain' => brand('domain')]) }}
                </div>
            </div>
            <div class="footer">
                © {{ date('Y') }} {{ brand('name') }} · {{ brand('positioning') }}
            </div>
        </div>
    </div>
</body>
</html>
