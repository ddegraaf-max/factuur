@php
    $eur = fn ($n) => '€ ' . number_format((float) $n, 2, ',', '.');
    $logo = $company->logoBinary();
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('doc.quote_tc') }} {{ $quote->number }}</title>
    <style>
        body { margin: 0; padding: 0; background: #FAFAF9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1C1917; }
        .wrapper { width: 100%; background: #FAFAF9; padding: 40px 16px; }
        .container { max-width: 520px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(28,25,23,0.08); }
        .header { background: {{ $company->brand_color ?: brand('color') }}; padding: 26px 36px; color: white; }
        .header .co { font-size: 19px; font-weight: 700; letter-spacing: -0.01em; }
        .header .kind { font-size: 13px; opacity: 0.9; margin-top: 3px; }
        .body { padding: 32px 36px 28px; }
        h1 { font-size: 21px; font-weight: 600; margin: 0 0 12px; }
        p { font-size: 15px; line-height: 1.6; color: #44403C; margin: 0 0 16px; }
        .amount-box { background: #FAFAF9; border: 1px solid #E7E5E4; border-radius: 10px; padding: 20px; margin: 22px 0; text-align: center; }
        .amount { font-size: 30px; font-weight: 800; letter-spacing: -0.02em; }
        .amount-lbl { font-size: 12px; color: #78716C; margin-top: 4px; }
        .meta { width: 100%; font-size: 14px; margin: 4px 0 8px; }
        .meta td { padding: 6px 0; border-bottom: 1px solid #F5F5F4; color: #44403C; }
        .meta td:last-child { text-align: right; font-weight: 600; color: #1C1917; }
        .meta tr:last-child td { border-bottom: none; }
        .valid { background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 8px; padding: 12px 16px; font-size: 14px; color: #92400E; margin: 20px 0 0; }
        .footer { padding: 20px 36px 28px; font-size: 12px; color: #A8A29E; text-align: center; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="header">
            @if($logo && isset($message))
                <img src="{{ $message->embedData($logo['data'], $logo['name'], $logo['mime']) }}"
                     alt="{{ $company->name }}" style="max-height:40px;max-width:200px;display:block;border:0;margin-bottom:8px;background:#fff;padding:4px 8px;border-radius:6px;">
            @endif
            <div class="co">{{ $company->name }}</div>
            <div class="kind">{{ __('doc.quote_tc') }} {{ $quote->number }}</div>
        </div>

        <div class="body">
            <h1>{{ __('doc.mail_greeting', ['name' => $quote->customer_name]) }}</h1>

            @if ($quote->intro)
                <p>{!! nl2br(e($quote->intro)) !!}</p>
            @elseif (!empty($customBody))
                {{-- Eigen standaardtekst (Instellingen → E-mailteksten). --}}
                <p>{!! nl2br(e($customBody)) !!}</p>
            @else
                <p>{{ __('doc.mail_quote_intro_default') }}</p>
            @endif

            <div class="amount-box">
                <div class="amount">{{ $eur($quote->total) }}</div>
                <div class="amount-lbl">{{ __('doc.mail_quote_total_lbl') }}</div>
            </div>

            <table class="meta">
                <tr><td>{{ __('doc.mail_quote_number') }}</td><td>{{ $quote->number }}</td></tr>
                <tr><td>{{ __('doc.mail_date') }}</td><td>{{ $quote->quote_date->translatedFormat('j F Y') }}</td></tr>
                <tr><td>{{ __('doc.valid_until') }}</td><td>{{ $quote->valid_until->translatedFormat('j F Y') }}</td></tr>
                @if ($quote->reference)
                    <tr><td>{{ __('doc.reference') }}</td><td>{{ $quote->reference }}</td></tr>
                @endif
            </table>

            @if ($quote->portal_token)
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px auto 4px;">
                    <tr>
                        <td style="border-radius:8px;background:{{ $company->brand_color ?: brand('color') }};">
                            <a href="{{ route('portal.quote', $quote->portal_token) }}"
                               style="display:inline-block;padding:13px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">
                                {{ __('doc.mail_view_quote') }}&nbsp;&nbsp;→
                            </a>
                        </td>
                    </tr>
                </table>
            @endif

            <div class="valid">
                {!! __('doc.mail_quote_agree') !!}
            </div>

            @if ($quote->notes)
                <p style="margin-top:20px;font-size:14px;color:#78716C;">{!! nl2br(e($quote->notes)) !!}</p>
            @endif
        </div>

        <div class="footer">
            {{ $company->name }}
            @if ($company->kvk_number) · {{ __('doc.coc') }} {{ $company->kvk_number }} @endif
            @if ($company->email) · {{ $company->email }} @endif
        </div>
    </div>
</div>
</body>
</html>
