@php
    $appUrl = rtrim(config('app.url'), '/');
    $eur = fn ($n) => '€ ' . number_format((float) $n, 2, ',', '.');
    $confirmationSent = $accepted && (bool) ($quote->company?->quote_accept_mail_enabled ?? false) && filled($quote->customer_email);
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $accepted ? 'Offerte ondertekend' : 'Offerte afgewezen' }}</title>
    <style>
        body { margin: 0; padding: 0; background: #FAFAF9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1C1917; }
        .wrapper { width: 100%; background: #FAFAF9; padding: 40px 16px; }
        .container { max-width: 560px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(28,25,23,0.08); }
        .header { background: linear-gradient(135deg, #E8231F 0%, #B81814 100%); padding: 28px 36px; color: white; }
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
        .btn-td { border-radius: 8px; background: #E8231F; }
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
                    <img src="{{ $appUrl }}/images/easyinvoice-icon-512.png" class="logo-mark" alt="EasyInvoice">
                    <span>EasyInvoice</span>
                </div>
                <div class="header-sub">Offerte {{ $quote->number }} · {{ $quote->customer_name }}</div>
            </div>
            <div class="body">
                @if($accepted)
                    <h1>🎉 Offerte {{ $quote->number }} is ondertekend</h1>
                    <p><strong>{{ $quote->signed_name ?: $quote->customer_name }}</strong> heeft de offerte digitaal ondertekend. Het akkoord staat zwart-op-wit — met handtekening en bewijsdossier bij de offerte in EasyInvoice.</p>
                    <table class="facts" role="presentation">
                        <tr><td class="k">Klant</td><td class="v">{{ $quote->customer_name }}</td></tr>
                        <tr><td class="k">Ondertekend door</td><td class="v">{{ $quote->signed_name ?: '—' }}@if($quote->signed_email) <span style="font-weight:400;color:#78716C;">({{ $quote->signed_email }})</span>@endif</td></tr>
                        <tr><td class="k">Op</td><td class="v">{{ ($quote->signed_at ?? $quote->accepted_at)?->translatedFormat('j F Y \o\m H:i') }}</td></tr>
                        <tr><td class="k">Totaal incl. btw</td><td class="v">{{ $eur($quote->total) }}</td></tr>
                    </table>
                    <div class="tip">
                        <strong>Volgende stap:</strong> zet de offerte met één klik om naar een conceptfactuur — of factureer in termijnen — via de offertepagina.
                        @if($confirmationSent) Je klant heeft automatisch een bevestiging ontvangen met de ondertekende offerte als PDF.@endif
                    </div>
                @else
                    <h1>Offerte {{ $quote->number }} is afgewezen</h1>
                    <p><strong>{{ $quote->customer_name }}</strong>@if($quote->signed_email) ({{ $quote->signed_email }})@endif heeft de offerte in het klantenportaal afgewezen.</p>
                    @if($quote->decline_reason)
                        <div class="reason"><strong>Toelichting van de klant:</strong><br>{{ $quote->decline_reason }}</div>
                    @endif
                    <table class="facts" role="presentation">
                        <tr><td class="k">Klant</td><td class="v">{{ $quote->customer_name }}</td></tr>
                        <tr><td class="k">Afgewezen op</td><td class="v">{{ $quote->rejected_at?->translatedFormat('j F Y \o\m H:i') ?? now()->translatedFormat('j F Y \o\m H:i') }}</td></tr>
                        <tr><td class="k">Totaal incl. btw</td><td class="v">{{ $eur($quote->total) }}</td></tr>
                    </table>
                    <p>Wellicht is een aangepast voorstel op zijn plaats — je kunt de offerte bewerken en opnieuw versturen.</p>
                @endif

                <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 8px 0 4px;">
                    <tr>
                        <td class="btn-td">
                            <a href="{{ route('quotes.show', $quote->id) }}" class="btn">Open de offerte in EasyInvoice&nbsp;&nbsp;→</a>
                        </td>
                    </tr>
                </table>

                <div class="meta">
                    Je ontvangt dit bericht omdat een klant in het klantenportaal een beslissing heeft genomen over een offerte van {{ $quote->company?->name ?? 'je administratie' }}.
                </div>
            </div>
            <div class="footer">
                © {{ date('Y') }} EasyInvoice · Nederlandse facturatie voor MKB en ZZP
            </div>
        </div>
    </div>
</body>
</html>
