@php
    $color = $company->brand_color ?: brand('color');
    // Onder een handelsnaam geen logo van het hoofdbedrijf: dan staat de naam in de kop.
    $logoUrl = $invoice->brand_profile_id ? null : $invoice->company?->logoUrl();
    $text = \App\Support\DemandText::letter($demand, $claim);
    $website = $company->website ? preg_replace('#^https?://#', '', rtrim($company->website, '/')) : null;
    $rows = array_filter([
        $text['l_principal'] => money($claim['principal']),
        $text['l_interest'] => $claim['with_interest'] ? money($claim['interest']) : null,
    ], fn ($value) => $value !== null);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#F5F5F4;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#1C1917;">
<div style="max-width:600px;margin:0 auto;padding:28px 16px;">
    <div style="background:#FFFFFF;border:1px solid #E7E5E4;border-radius:14px;overflow:hidden;">

        {{-- Kop: logo of bedrijfsnaam, met een streep in de huiskleur --}}
        <div style="padding:22px 32px;border-bottom:3px solid {{ $color }};">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $company->name }}" style="max-height:46px;max-width:230px;display:block;border:0;font-size:19px;font-weight:700;color:{{ $color }};">
            @else
                <div style="font-size:19px;font-weight:700;letter-spacing:-0.01em;color:{{ $color }};">{{ $company->name }}</div>
            @endif
        </div>

        <div style="padding:28px 32px 30px;">
            <div style="font-size:11.5px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:{{ $color }};margin:0 0 6px;">{{ $text['title'] }}</div>
            <h1 style="font-size:22px;line-height:1.3;font-weight:700;letter-spacing:-0.015em;margin:0 0 18px;color:#1C1917;">{{ $text['subtitle'] }}</h1>

            <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ $text['salutation'] }}</p>
            <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ $text['intro'] }}</p>

            {{-- De vordering op de dag van verzenden --}}
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:18px 0;border:1px solid #E7E5E4;border-radius:10px;border-collapse:separate;overflow:hidden;">
                @foreach($rows as $label => $value)
                    <tr>
                        <td style="padding:11px 16px;font-size:13.5px;color:#57534E;background:#FAFAF9;border-bottom:1px solid #E7E5E4;">{{ $label }}</td>
                        <td style="padding:11px 16px;font-size:14px;color:#1C1917;text-align:right;white-space:nowrap;background:#FAFAF9;border-bottom:1px solid #E7E5E4;">{{ $value }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td style="padding:12px 16px;font-size:14.5px;font-weight:700;color:#1C1917;">{{ $text['l_total'] }}</td>
                    <td style="padding:12px 16px;font-size:16px;font-weight:700;color:#1C1917;text-align:right;white-space:nowrap;">{{ money($claim['total']) }}</td>
                </tr>
            </table>

            <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ $text['term'] }}@if($text['interest']) {{ $text['interest'] }}@endif</p>
            @if($text['bank'])
                <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ $text['bank'] }}</p>
            @endif

            <div style="margin:18px 0;padding:14px 16px;border:1px solid #FCD34D;background:#FFFBEB;border-radius:10px;font-size:14.5px;line-height:1.65;color:#44403C;">{{ $text['consequence'] }}</div>

            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0 12px;">
                <tr>
                    <td style="border-radius:9px;background:{{ $color }};">
                        <a href="{{ $url }}" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:9px;">{{ $text['button'] }}&nbsp;&nbsp;→</a>
                    </td>
                </tr>
            </table>
            <p style="font-size:13px;line-height:1.6;color:#78716C;margin:0;">{{ $text['respond'] }} {{ $text['live'] }}</p>

            <div style="margin-top:26px;padding-top:20px;border-top:1px solid #E7E5E4;">
                <p style="font-size:14px;line-height:1.6;color:#44403C;margin:0 0 8px;">{{ $text['regards'] }}</p>
                <p style="font-size:14px;line-height:1.6;color:#1C1917;font-weight:700;margin:0;">{{ $company->name }}</p>
                <p style="font-size:13px;line-height:1.7;color:#57534E;margin:2px 0 0;">
                    @if($company->phone){{ $company->phone }}<br>@endif
                    @if($company->email)<a href="mailto:{{ $company->email }}" style="color:#57534E;text-decoration:none;">{{ $company->email }}</a><br>@endif
                    @if($website)<a href="{{ $company->website }}" style="color:#57534E;text-decoration:none;">{{ $website }}</a><br>@endif
                    @if($company->kvk_number){{ market('registry.short') }} {{ $company->kvk_number }}@endif
                </p>
            </div>

            <p style="font-size:11.5px;line-height:1.6;color:#A8A29E;margin:18px 0 0;">{{ $text['legal'] }}</p>
        </div>
    </div>
    <div style="padding:16px 8px 0;font-size:12px;line-height:1.5;color:#A8A29E;text-align:center;">{{ __('doc.mail_sent_via', ['name' => $company->name, 'brand' => brand('name')]) }}</div>
</div>
</body>
</html>
