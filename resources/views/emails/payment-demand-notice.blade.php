@php
    $expired = $kind === 'expired';
    $partner = \App\Support\Market::incasso('partner_name');
    $day = fn (?\Carbon\CarbonInterface $at) => $at?->translatedFormat('j F Y');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $expired ? __('Termijn verstreken') : __('Reactie op je aanmaning') }}</title>
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
        .facts td { padding: 9px 14px; border-bottom: 1px solid #EBE9E6; }
        .facts tr:last-child td { border-bottom: none; }
        .facts .k { color: #78716C; width: 46%; }
        .facts .v { font-weight: 600; color: #1C1917; }
        .reason { background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 10px; padding: 12px 16px; font-size: 14px; line-height: 1.6; margin: 0 0 18px; }
        .tip { background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 10px; padding: 12px 16px; font-size: 14px; line-height: 1.6; margin: 0 0 20px; color: #166534; }
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
                <div class="header-sub">{{ __('Factuur :number', ['number' => $invoice->number]) }} · {{ $invoice->customer_name }}</div>
            </div>
            <div class="body">
                @if($expired)
                    <h1>{{ __('De termijn van je aanmaning is voorbij') }}</h1>
                    <p>{!! __('<strong>:customer</strong> heeft factuur :number niet betaald binnen de termijn, die liep tot en met :date. Je kunt het dossier nu met één klik overdragen aan :partner.', ['customer' => e($invoice->customer_name), 'number' => e($invoice->number), 'date' => e($day($demand->deadline)), 'partner' => e($partner)]) !!}</p>
                @else
                    <h1>{{ $responseLabel }}</h1>
                    <p>{!! __('<strong>:customer</strong> heeft gereageerd op je aanmaning voor factuur :number.', ['customer' => e($invoice->customer_name), 'number' => e($invoice->number)]) !!}</p>
                    @if($demand->response_note)
                        <div class="reason"><strong>{{ __('Toelichting van de klant:') }}</strong><br>{!! nl2br(e($demand->response_note)) !!}</div>
                    @endif
                    @if($demand->response === 'promise')
                        <div class="tip">{{ __('Een schriftelijke toezegging is een erkenning van de schuld en stuit de verjaring (artikel 3:318 BW). Bewaar dit bericht. Ga je akkoord, zet de factuur dan op pauze tot en met de toegezegde dag.') }}</div>
                    @elseif($demand->response === 'paid')
                        <div class="tip">{{ __('Controleer je bankrekening en boek de betaling op de factuur. De aanmaning sluit dan vanzelf.') }}</div>
                    @elseif($demand->response === 'dispute')
                        <div class="tip">{{ __('Een betwiste vordering vraagt om een inhoudelijk antwoord. Leg de bezwaren naast je stukken voordat je het dossier overdraagt.') }}</div>
                    @endif
                @endif

                <table class="facts" role="presentation">
                    <tr><td class="k">{{ __('Aanmaning verstuurd') }}</td><td class="v">{{ $day($demand->sent_at) }}</td></tr>
                    <tr><td class="k">{{ __('Voor het eerst geopend') }}</td><td class="v">{{ $demand->first_opened_at ? $demand->first_opened_at->translatedFormat('j F Y, H:i') : __('Niet geopend') }}</td></tr>
                    <tr><td class="k">{{ __('Termijn tot en met') }}</td><td class="v">{{ $day($demand->deadline) }}</td></tr>
                    <tr><td class="k">{{ __('Hoofdsom') }}</td><td class="v">{{ money($claim['principal']) }}</td></tr>
                    @if($claim['with_interest'])<tr><td class="k">{{ __('Rente tot vandaag') }}</td><td class="v">{{ money($claim['interest']) }}</td></tr>@endif
                    <tr><td class="k">{{ __('Incassokosten') }}</td><td class="v">{{ money($claim['costs_total']) }}@if(! $claim['costs_due']) <span style="font-weight:400;color:#78716C;">({{ __('na de termijn') }})</span>@endif</td></tr>
                </table>

                <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 8px 0 4px;">
                    <tr>
                        <td class="btn-td">
                            <a href="{{ $url }}" class="btn">{{ $expired ? __('Open de factuur en draag over') : __('Open de factuur in :brand', ['brand' => brand('name')]) }}&nbsp;&nbsp;→</a>
                        </td>
                    </tr>
                </table>

                <div class="meta">
                    {{ __('Je ontvangt dit bericht omdat je voor :company een online aanmaning hebt verstuurd.', ['company' => $company?->name ?? __('je administratie')]) }}
                </div>
            </div>
            <div class="footer">
                © {{ date('Y') }} {{ brand('name') }} · {{ brand('positioning') }}
            </div>
        </div>
    </div>
</body>
</html>
