@php
    $color = $company->brand_color ?: brand('color');
    $name = $subcontractor?->contact_name ?: $subcontractor?->name;
    $deadline = $round->deadline?->translatedFormat('j F Y');
    $logo = $company->logoBinary();
    $asks = in_array($kind, ['request', 'reminder'], true);
    $eyebrow = match ($kind) {
        'reminder' => __('Herinnering'),
        'award' => __('Opdracht'),
        'reject' => __('Prijsaanvraag'),
        default => __('Prijsaanvraag'),
    };
    $details = array_filter([
        __('Onderdeel') => $round->title,
        __('Locatie') => $round->location,
        __('Gewenste start') => $startWeek ? __('week :week', ['week' => $startWeek]) : null,
        __('Reageren vóór') => $asks ? $deadline : null,
    ]);
    $website = $company->website ? preg_replace('#^https?://#', '', rtrim($company->website, '/')) : null;
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
            @if($logo && isset($message))
                <img src="{{ $message->embedData($logo['data'], $logo['name'], $logo['mime']) }}" alt="{{ $company->name }}" style="max-height:46px;max-width:230px;display:block;border:0;">
            @else
                <div style="font-size:19px;font-weight:700;letter-spacing:-0.01em;color:{{ $color }};">{{ $company->name }}</div>
            @endif
        </div>

        <div style="padding:28px 32px 30px;">
            <div style="font-size:11.5px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:{{ $color }};margin:0 0 6px;">{{ $eyebrow }}</div>
            <h1 style="font-size:22px;line-height:1.3;font-weight:700;letter-spacing:-0.015em;margin:0 0 18px;color:#1C1917;">{{ $round->title }}</h1>

            <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ __('Beste :name,', ['name' => $name]) }}</p>
            @if($kind === 'award')
                <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ __('Bedankt voor uw prijsopgave voor :package. Wij gunnen u de opdracht en nemen binnenkort contact met u op over de planning en de opdrachtbevestiging.', ['package' => $round->title]) }}</p>
            @elseif($kind === 'reject')
                <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ __('Bedankt voor uw prijsopgave. Voor dit project hebben wij een andere partij gekozen. Wij houden u graag in beeld voor volgende projecten.') }}</p>
            @elseif($kind === 'reminder')
                <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ __('Wij hebben nog geen reactie van u ontvangen op onze prijsaanvraag. Kunt u uiterlijk :deadline uw prijs en beschikbaarheid doorgeven? Dat kan in een minuut via de knop hieronder.', ['deadline' => $deadline]) }}</p>
            @else
                <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ __(':company vraagt u om een prijs en uw beschikbaarheid voor onderstaand onderdeel van een project. Reageren kan in een minuut via de knop hieronder.', ['company' => $company->name]) }}</p>
            @endif

            @if($kind !== 'reject')
                {{-- Kerngegevens --}}
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:20px 0 0;border:1px solid #E7E5E4;border-radius:10px;border-collapse:separate;overflow:hidden;">
                    @foreach($details as $label => $value)
                        <tr>
                            <td style="padding:11px 16px;width:150px;font-size:13px;color:#78716C;background:#FAFAF9;vertical-align:top;{{ $loop->last ? '' : 'border-bottom:1px solid #E7E5E4;' }}">{{ $label }}</td>
                            <td style="padding:11px 16px;font-size:14px;color:#1C1917;font-weight:{{ $loop->first || $label === __('Reageren vóór') ? '700' : '400' }};vertical-align:top;{{ $loop->last ? '' : 'border-bottom:1px solid #E7E5E4;' }}">{{ $value }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @if($asks && $blocks)
                <div style="font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#78716C;margin:26px 0 8px;">{{ __('Omschrijving') }}</div>
                @foreach($blocks as $block)
                    @if($block['type'] === 'ul')
                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 10px;">
                            @foreach($block['lines'] as $line)
                                <tr>
                                    <td style="width:18px;padding:3px 0;font-size:14.5px;line-height:1.6;color:{{ $color }};vertical-align:top;">•</td>
                                    <td style="padding:3px 0;font-size:14.5px;line-height:1.6;color:#292524;vertical-align:top;">{{ $line }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <p style="font-size:14.5px;line-height:1.65;color:#292524;margin:0 0 10px;">{!! implode('<br>', array_map('e', $block['lines'])) !!}</p>
                    @endif
                @endforeach
            @endif

            @if($files)
                <div style="font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#78716C;margin:24px 0 8px;">{{ __('Bijlagen') }}</div>
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                    @foreach($files as $file)
                        <tr>
                            <td style="padding:0 0 6px;">
                                <a href="{{ $file['url'] }}" style="display:block;padding:10px 14px;border:1px solid #E7E5E4;border-radius:8px;text-decoration:none;color:#1C1917;font-size:14px;">
                                    <span style="font-weight:600;">📎 {{ $file['name'] }}</span>
                                    <span style="color:#78716C;font-size:12.5px;">&nbsp;· {{ $file['size'] }} · {{ $file['attached'] ? __('ook als bijlage bij deze mail') : __('te groot voor de mail, klik om te openen') }}</span>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @if($asks)
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0 10px;">
                    <tr>
                        <td style="border-radius:9px;background:{{ $color }};">
                            <a href="{{ $url }}" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:9px;">{{ __('Prijs en beschikbaarheid doorgeven') }}&nbsp;&nbsp;→</a>
                        </td>
                    </tr>
                </table>
                <p style="font-size:13px;line-height:1.6;color:#78716C;margin:0;">{{ __('Liever uw eigen offerte sturen? Die kunt u op dezelfde pagina als PDF toevoegen. Antwoorden op deze mail kan ook.') }}</p>
                <p style="font-size:13px;line-height:1.6;color:#78716C;margin:6px 0 0;">{{ __('Geen tijd of past het niet? Laat het ons via dezelfde knop weten, dan sturen wij geen herinnering.') }}</p>
            @endif

            {{-- Afsluiting: eigen ondertekening uit de tekst, anders de bedrijfsgegevens --}}
            <div style="margin-top:26px;padding-top:20px;border-top:1px solid #E7E5E4;">
                @if($signature)
                    <p style="font-size:13px;line-height:1.6;color:#57534E;margin:0;">{!! nl2br(e($signature)) !!}</p>
                @else
                    <p style="font-size:14px;line-height:1.6;color:#44403C;margin:0 0 8px;">{{ __('Met vriendelijke groet,') }}</p>
                    <p style="font-size:14px;line-height:1.6;color:#1C1917;font-weight:700;margin:0;">{{ $company->name }}</p>
                    <p style="font-size:13px;line-height:1.7;color:#57534E;margin:2px 0 0;">
                        @if($company->phone){{ $company->phone }}<br>@endif
                        @if($company->email)<a href="mailto:{{ $company->email }}" style="color:#57534E;text-decoration:none;">{{ $company->email }}</a><br>@endif
                        @if($website)<a href="{{ $company->website }}" style="color:#57534E;text-decoration:none;">{{ $website }}</a><br>@endif
                        @if($company->kvk_number){{ market('registry.short') }} {{ $company->kvk_number }}@endif
                    </p>
                @endif
            </div>
        </div>
    </div>
    <div style="padding:16px 8px 0;font-size:12px;line-height:1.5;color:#A8A29E;text-align:center;">{{ __('doc.mail_sent_via', ['name' => $company->name, 'brand' => brand('name')]) }}</div>
</div>
</body>
</html>
