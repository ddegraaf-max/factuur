@php
    $f = $r['facts'] ?? [];
    $verdictColor = match ($r['verdict'] ?? 'unclear') {
        'reasonable' => ['#DCFCE7', '#15803D'],
        'high' => ['#FEF3C7', '#B45309'],
        'low' => ['#FEE2E2', '#B91C1C'],
        default => ['#F5F5F4', '#57534E'],
    };
    $pct = fn ($v) => $v === null ? null : (($v > 0 ? '+' : '') . number_format((float) $v, 1, ',', '.') . '%');
    $rows = array_filter([
        __('Prijs excl. btw') => money($f['price'] ?? $request->price),
        __('T.o.v. jouw calculatie') => isset($f['budget']) && $f['budget'] !== null ? money($f['budget']) . ' (' . $pct($f['vs_budget_pct']) . ')' : null,
        __('Positie in de uitvraag') => ($f['of'] ?? 1) > 1 ? __(':rank van :of prijzen', ['rank' => $f['rank'], 'of' => $f['of']]) . ($f['vs_lowest_pct'] ? ' · ' . $pct($f['vs_lowest_pct']) . ' ' . __('t.o.v. de laagste') : '') : __('eerste prijs'),
        __('T.o.v. eerdere prijzen') => ! empty($f['history']) ? __('mediaan :median (:n eerder)', ['median' => money($f['history']['median']), 'n' => $f['history']['n']]) . ' · ' . $pct($f['vs_history_pct']) : null,
        __('Marktindicatie') => ! empty($r['market_estimate']['low']) && ! empty($r['market_estimate']['high']) ? money($r['market_estimate']['low']) . ' – ' . money($r['market_estimate']['high']) : null,
    ]);
    $lists = array_filter([
        __('Inbegrepen') => $r['included'] ?? [],
        __('Niet inbegrepen of onder voorbehoud') => $r['excluded'] ?? [],
        __('Niet gedekt uit de aanvraag') => $r['scope_gaps'] ?? [],
        __('Vragen aan het bedrijf') => $r['questions'] ?? [],
    ]);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Offertecheck') }}</title>
</head>
<body style="margin:0;padding:0;background:#FAFAF9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#1C1917;">
<div style="width:100%;background:#FAFAF9;padding:40px 16px;">
    <div style="max-width:600px;margin:0 auto;background:#FFFFFF;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(28,25,23,0.08);">
        <div style="background:linear-gradient(135deg, {{ brand('color') }} 0%, {{ brand('color_dark') }} 100%);padding:28px 36px;color:#fff;">
            <div style="font-size:20px;font-weight:700;letter-spacing:-0.01em;">{{ brand('name') }}</div>
            <div style="font-size:13px;opacity:0.9;margin-top:6px;">{{ __('Offertecheck') }} · {{ $round->title }}</div>
        </div>
        <div style="padding:32px 36px 28px;">
            <div style="display:inline-block;padding:5px 12px;border-radius:999px;background:{{ $verdictColor[0] }};color:{{ $verdictColor[1] }};font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;">{{ $verdictLabel }}</div>
            <h1 style="font-size:21px;font-weight:600;letter-spacing:-0.015em;margin:12px 0 6px;">{{ $r['headline'] ?: __('Prijsopgave van :name', ['name' => $request->subcontractor?->name]) }}</h1>
            <p style="font-size:14px;color:#78716C;margin:0 0 16px;">{{ $request->subcontractor?->name }} · {{ money($request->price) }} {{ __('excl. btw') }}@if($request->attachment_name) · 📎 {{ $request->attachment_name }}@endif</p>
            <p style="font-size:15px;line-height:1.6;color:#44403C;margin:0 0 16px;">{{ $r['summary'] }}</p>

            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #E7E5E4;border-radius:10px;border-collapse:separate;overflow:hidden;font-size:14px;margin:0 0 20px;">
                @foreach($rows as $label => $value)
                    <tr>
                        <td style="padding:9px 14px;color:#78716C;background:#FAFAF9;width:170px;vertical-align:top;{{ $loop->last ? '' : 'border-bottom:1px solid #E7E5E4;' }}">{{ $label }}</td>
                        <td style="padding:9px 14px;vertical-align:top;{{ $loop->last ? '' : 'border-bottom:1px solid #E7E5E4;' }}">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>

            @if($r['comparison'])
                <p style="font-size:14px;line-height:1.6;color:#44403C;margin:0 0 16px;">{{ $r['comparison'] }}</p>
            @endif
            @if($r['price_basis'])
                <p style="font-size:13.5px;line-height:1.6;color:#57534E;margin:0 0 16px;"><b>{{ __('Opbouw van de prijs') }}:</b> {{ $r['price_basis'] }}</p>
            @endif

            @foreach($lists as $label => $items)
                <div style="font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#78716C;margin:18px 0 6px;">{{ $label }}</div>
                <ul style="font-size:14px;line-height:1.6;color:#292524;margin:0;padding-left:20px;">
                    @foreach($items as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endforeach

            @if(! empty($r['market_estimate']['basis']))
                <p style="font-size:12.5px;line-height:1.6;color:#78716C;margin:16px 0 0;"><b>{{ __('Marktindicatie') }}:</b> {{ $r['market_estimate']['basis'] }}</p>
            @endif

            <div style="margin:22px 0 0;padding:14px 16px;background:#FAFAF9;border-left:3px solid {{ brand('color') }};border-radius:8px;">
                <div style="font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#78716C;margin:0 0 4px;">{{ __('Advies') }}</div>
                <p style="font-size:14.5px;line-height:1.6;color:#1C1917;margin:0;">{{ $r['advice'] }}</p>
            </div>

            <div style="text-align:center;margin:28px 0 4px;">
                <a href="{{ $url }}" style="display:inline-block;background:{{ brand('color') }};color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;padding:12px 24px;border-radius:8px;">{{ __('Open de uitvraag') }}</a>
            </div>
            <p style="font-size:12px;line-height:1.6;color:#A8A29E;text-align:center;margin:14px 0 0;">{{ __('Vanaf de uitvraag mail je de vragen met één klik aan het bedrijf.') }}</p>
        </div>
        <div style="padding:20px 36px 28px;font-size:12px;color:#A8A29E;text-align:center;line-height:1.6;">
            {{ __('Een beoordeling door AI op basis van de aanvraag, je calculatie, de andere prijzen en de meegestuurde offerte. Een tweede paar ogen — jij beslist.') }}
        </div>
    </div>
</div>
</body>
</html>
