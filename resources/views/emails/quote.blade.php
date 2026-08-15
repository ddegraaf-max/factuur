@php
    $eur = fn ($n) => '€ ' . number_format((float) $n, 2, ',', '.');
    $logo = $company->logoBinary();
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offerte {{ $quote->number }}</title>
    <style>
        body { margin: 0; padding: 0; background: #FAFAF9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1C1917; }
        .wrapper { width: 100%; background: #FAFAF9; padding: 40px 16px; }
        .container { max-width: 520px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(28,25,23,0.08); }
        .header { background: {{ $company->brand_color ?: '#E8231F' }}; padding: 26px 36px; color: white; }
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
            <div class="kind">Offerte {{ $quote->number }}</div>
        </div>

        <div class="body">
            <h1>Beste {{ $quote->customer_name }},</h1>

            @if ($quote->intro)
                <p>{!! nl2br(e($quote->intro)) !!}</p>
            @else
                <p>Hierbij ontvang je onze offerte. In de bijlage vind je het volledige overzicht als PDF.</p>
            @endif

            <div class="amount-box">
                <div class="amount">{{ $eur($quote->total) }}</div>
                <div class="amount-lbl">totaal incl. btw</div>
            </div>

            <table class="meta">
                <tr><td>Offertenummer</td><td>{{ $quote->number }}</td></tr>
                <tr><td>Datum</td><td>{{ $quote->quote_date->translatedFormat('j F Y') }}</td></tr>
                <tr><td>Geldig tot</td><td>{{ $quote->valid_until->translatedFormat('j F Y') }}</td></tr>
                @if ($quote->reference)
                    <tr><td>Referentie</td><td>{{ $quote->reference }}</td></tr>
                @endif
            </table>

            <div class="valid">
                <strong>Akkoord?</strong> Beantwoord deze e-mail — dan zetten we de offerte om in een opdracht.
                Vragen of iets aanpassen kan natuurlijk ook.
            </div>

            @if ($quote->notes)
                <p style="margin-top:20px;font-size:14px;color:#78716C;">{!! nl2br(e($quote->notes)) !!}</p>
            @endif
        </div>

        <div class="footer">
            {{ $company->name }}
            @if ($company->kvk_number) · KVK {{ $company->kvk_number }} @endif
            @if ($company->email) · {{ $company->email }} @endif
        </div>
    </div>
</div>
</body>
</html>
