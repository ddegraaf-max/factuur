@php
    // 1.107: mail aan een van de twee ondertekenaars van een offerte (uitnodiging, beurt of herinnering).
    $color = $company->brand_color ?: brand('color');
    $eur = fn ($n) => money($n);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Offerte :number', ['number' => $quote->number]) }}</title>
</head>
<body style="margin:0;padding:0;background:#FAFAF9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#1C1917;">
<div style="max-width:560px;margin:0 auto;padding:40px 16px;">
    <div style="background:#FFFFFF;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(28,25,23,0.08);">
        <div style="padding:24px 32px;background:{{ $color }};color:#fff;">
            @if($company->logo_data)
                <img src="{{ $company->logo_data }}" alt="{{ $company->name }}" style="max-height:40px;max-width:180px;display:block;">
            @else
                <div style="font-size:20px;font-weight:700;">{{ $company->name }}</div>
            @endif
            <div style="font-size:13px;opacity:0.9;margin-top:6px;">{{ __('Offerte :number', ['number' => $quote->number]) }} · {{ $eur($quote->total) }}</div>
        </div>
        <div style="padding:28px 32px 26px;font-size:15px;line-height:1.6;color:#44403C;">
            <p style="margin:0 0 14px;">{{ __('Beste :name,', ['name' => $name]) }}</p>
            @if($mode === 'turn')
                <p style="margin:0 0 14px;">{{ __(':other heeft offerte :number zojuist digitaal ondertekend. Nu is uw handtekening nog nodig om de offerte definitief te maken.', ['other' => $other, 'number' => $quote->number]) }}</p>
            @elseif($mode === 'reminder')
                <p style="margin:0 0 14px;">{{ __('Offerte :number (:amount) wacht nog op uw handtekening. De offerte is geldig tot :date.', ['number' => $quote->number, 'amount' => $eur($quote->total), 'date' => $quote->valid_until?->translatedFormat('j F Y')]) }}</p>
            @else
                <p style="margin:0 0 14px;">{{ __(':company heeft offerte :number (:amount) gestuurd aan :customer. Deze offerte moet door twee personen worden ondertekend; u bent opgegeven als tweede ondertekenaar.', ['company' => $company->name, 'number' => $quote->number, 'amount' => $eur($quote->total), 'customer' => $quote->customer_name]) }}</p>
                <p style="margin:0 0 14px;">{{ __('U kunt tekenen wanneer het u uitkomt; de offerte is definitief zodra beide handtekeningen zijn gezet.') }}</p>
            @endif
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:18px 0 10px;">
                <tr><td style="border-radius:8px;background:{{ $color }};">
                    <a href="{{ $url }}" style="display:inline-block;padding:13px 26px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">{{ __('Bekijk en onderteken de offerte') }} →</a>
                </td></tr>
            </table>
            <p style="margin:14px 0 0;font-size:13px;color:#78716C;">{{ __('Deze link is persoonlijk: u bevestigt eerst uw e-mailadres met een code.') }}</p>
        </div>
        <div style="padding:18px 32px 26px;font-size:12px;color:#A8A29E;text-align:center;border-top:1px solid #E7E5E4;">
            {{ $company->name }}@if($company->email) · {{ $company->email }}@endif
        </div>
    </div>
</div>
</body>
</html>
