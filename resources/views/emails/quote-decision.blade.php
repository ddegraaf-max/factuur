@php
    $appUrl = rtrim(config('app.url'), '/');
    $eur = fn ($n) => money($n);
    $confirmationSent = $accepted && (bool) ($quote->company?->quote_accept_mail_enabled ?? false) && filled($quote->customer_email);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $accepted ? __('Offerte ondertekend') : __('Offerte afgewezen') }}</title>
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
        .facts .k { color: #78716C; width: 38%; }
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
                <div class="header-sub">{{ __('Offerte :number', ['number' => $quote->number]) }} · {{ $quote->customer_name }}</div>
            </div>
            <div class="body">
                @php
                    // "5 september 2026 om 14:30" — datum en tijd in de taal van de markt.
                    $stamp = fn (?\Carbon\CarbonInterface $at) => $at ? __(':date om :time', ['date' => $at->translatedFormat('j F Y'), 'time' => $at->format('H:i')]) : '';
                @endphp
                @if($accepted)
                    <h1>🎉 {{ __('Offerte :number is ondertekend', ['number' => $quote->number]) }}</h1>
                    <p>{!! __('<strong>:name</strong> heeft de offerte digitaal ondertekend. Het akkoord staat zwart-op-wit — met handtekening en bewijsdossier bij de offerte in :brand.', ['name' => e($quote->signed_name ?: $quote->customer_name), 'brand' => e(brand('name'))]) !!}</p>
                    <table class="facts" role="presentation">
                        <tr><td class="k">{{ __('Klant') }}</td><td class="v">{{ $quote->customer_name }}</td></tr>
                        <tr><td class="k">{{ __('Ondertekend door') }}</td><td class="v">{{ $quote->signed_name ?: '—' }}@if($quote->signed_email) <span style="font-weight:400;color:#78716C;">({{ $quote->signed_email }})</span>@endif</td></tr>
                        <tr><td class="k">{{ __('Op') }}</td><td class="v">{{ $stamp($quote->signed_at ?? $quote->accepted_at) }}</td></tr>
                        <tr><td class="k">{{ __('Totaal incl. btw') }}</td><td class="v">{{ $eur($quote->total) }}</td></tr>
                    </table>
                    <div class="tip">
                        {!! __('<strong>Volgende stap:</strong> zet de offerte met één klik om naar een conceptfactuur — of factureer in termijnen — via de offertepagina.') !!}
                        @if($confirmationSent) {{ __('Je klant heeft automatisch een bevestiging ontvangen met de ondertekende offerte als PDF.') }}@endif
                    </div>
                @else
                    <h1>{{ __('Offerte :number is afgewezen', ['number' => $quote->number]) }}</h1>
                    <p>{!! __(':who heeft de offerte in het klantenportaal afgewezen.', ['who' => '<strong>' . e($quote->customer_name) . '</strong>' . ($quote->signed_email ? ' (' . e($quote->signed_email) . ')' : '')]) !!}</p>
                    @if($quote->decline_reason)
                        <div class="reason"><strong>{{ __('Toelichting van de klant:') }}</strong><br>{{ $quote->decline_reason }}</div>
                    @endif
                    <table class="facts" role="presentation">
                        <tr><td class="k">{{ __('Klant') }}</td><td class="v">{{ $quote->customer_name }}</td></tr>
                        <tr><td class="k">{{ __('Afgewezen op') }}</td><td class="v">{{ $stamp($quote->rejected_at ?? now()) }}</td></tr>
                        <tr><td class="k">{{ __('Totaal incl. btw') }}</td><td class="v">{{ $eur($quote->total) }}</td></tr>
                    </table>
                    <p>{{ __('Wellicht is een aangepast voorstel op zijn plaats — je kunt de offerte bewerken en opnieuw versturen.') }}</p>
                @endif

                <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 8px 0 4px;">
                    <tr>
                        <td class="btn-td">
                            <a href="{{ route('quotes.show', $quote->id) }}" class="btn">{{ __('Open de offerte in :brand', ['brand' => brand('name')]) }}&nbsp;&nbsp;→</a>
                        </td>
                    </tr>
                </table>

                <div class="meta">
                    {{ __('Je ontvangt dit bericht omdat een klant in het klantenportaal een beslissing heeft genomen over een offerte van :company.', ['company' => $quote->company?->name ?? __('je administratie')]) }}
                </div>
            </div>
            <div class="footer">
                © {{ date('Y') }} {{ brand('name') }} · {{ brand('positioning') }}
            </div>
        </div>
    </div>
</body>
</html>
