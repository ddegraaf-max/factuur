@php
    $color = $company->brand_color ?: brand('color');
    $name = $subcontractor?->contact_name ?: $subcontractor?->name;
    $logoUrl = $company->logoUrl();
    $start = $item->starts_on?->translatedFormat('l j F Y');
    $end = $item->ends_on?->translatedFormat('l j F Y');
    $proposed = $item->request_start?->translatedFormat('l j F Y');
    $eyebrow = match ($kind) {
        'headsup' => __('Volgende week'),
        'earlier' => __('Vraag over de planning'),
        default => __('Deze week'),
    };
    $details = array_filter([
        __('Onderdeel') => $item->title,
        __('Project') => $project->name,
        __('Locatie') => $location,
        __('Geplande start') => $start,
        __('Gepland tot en met') => $end,
        __('Voorstel') => $kind === 'earlier' ? $proposed : null,
    ]);
    $button = match ($kind) {
        'earlier' => __('Antwoord geven'),
        default => __('Bevestigen of iets doorgeven'),
    };
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

        <div style="padding:22px 32px;border-bottom:3px solid {{ $color }};">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $company->name }}" style="max-height:46px;max-width:230px;display:block;border:0;font-size:19px;font-weight:700;color:{{ $color }};">
            @else
                <div style="font-size:19px;font-weight:700;letter-spacing:-0.01em;color:{{ $color }};">{{ $company->name }}</div>
            @endif
        </div>

        <div style="padding:28px 32px 30px;">
            <div style="font-size:11.5px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:{{ $color }};margin:0 0 6px;">{{ $eyebrow }}</div>
            <h1 style="font-size:22px;line-height:1.3;font-weight:700;letter-spacing:-0.015em;margin:0 0 18px;color:#1C1917;">{{ $item->title }}</h1>

            <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ __('Beste :name,', ['name' => $name]) }}</p>
            @if($kind === 'headsup')
                <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ __('Volgende week staat uw werk op project :project gepland. Wij rekenen op u vanaf :date. Klopt dat nog? Bevestig het met één klik, of laat het ons weten als er iets in de weg zit.', ['project' => $project->name, 'date' => $start]) }}</p>
            @elseif($kind === 'earlier')
                <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ __('Het werk op project :project loopt voor op schema. Daarom de vraag: kunt u eerder beginnen met :title, namelijk op :proposed in plaats van :date?', ['project' => $project->name, 'title' => $item->title, 'proposed' => $proposed, 'date' => $start]) }}</p>
                @if($item->request_message)
                    <p style="font-size:14px;line-height:1.6;color:#57534E;margin:0 0 12px;">{{ $item->request_message }}</p>
                @endif
                <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ __('Via de knop hieronder geeft u in een paar seconden antwoord: ja, een andere dag, of nee — dan blijft de oorspronkelijke planning staan.') }}</p>
            @else
                <p style="font-size:15px;line-height:1.65;color:#44403C;margin:0 0 12px;">{{ __('Deze week begint uw werk op project :project: :date. Wij zien u dan graag op de locatie. Bevestig het met één klik, of laat het ons weten als er iets in de weg zit.', ['project' => $project->name, 'date' => $start]) }}</p>
            @endif

            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:20px 0 0;border:1px solid #E7E5E4;border-radius:10px;border-collapse:separate;overflow:hidden;">
                @foreach($details as $label => $value)
                    <tr>
                        <td style="padding:11px 16px;width:150px;font-size:13px;color:#78716C;background:#FAFAF9;vertical-align:top;{{ $loop->last ? '' : 'border-bottom:1px solid #E7E5E4;' }}">{{ $label }}</td>
                        <td style="padding:11px 16px;font-size:14px;color:#1C1917;font-weight:{{ $loop->first || $label === __('Voorstel') ? '700' : '400' }};vertical-align:top;{{ $loop->last ? '' : 'border-bottom:1px solid #E7E5E4;' }}">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>

            @if($item->notes)
                <div style="font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#78716C;margin:22px 0 8px;">{{ __('Bijzonderheden') }}</div>
                <p style="font-size:14.5px;line-height:1.6;color:#292524;margin:0;white-space:pre-wrap;">{{ $item->notes }}</p>
            @endif

            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0 10px;">
                <tr>
                    <td style="border-radius:9px;background:{{ $color }};">
                        <a href="{{ $url }}" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:9px;">{{ $button }}&nbsp;&nbsp;→</a>
                    </td>
                </tr>
            </table>
            <p style="font-size:13px;line-height:1.6;color:#78716C;margin:0;">{{ __('Antwoorden op deze mail kan ook; uw bericht komt rechtstreeks bij ons.') }}</p>
            <p style="font-size:12px;line-height:1.55;color:#A8A29E;margin:14px 0 0;word-break:break-all;">{{ __('Werkt de knop niet? Open dan dit adres:') }} <a href="{{ $url }}" style="color:#78716C;">{{ $url }}</a></p>

            <div style="margin-top:26px;padding-top:20px;border-top:1px solid #E7E5E4;">
                <p style="font-size:14px;line-height:1.6;color:#44403C;margin:0 0 8px;">{{ __('Met vriendelijke groet,') }}</p>
                <p style="font-size:14px;line-height:1.6;color:#1C1917;font-weight:700;margin:0;">{{ $company->name }}</p>
                <p style="font-size:13px;line-height:1.7;color:#57534E;margin:2px 0 0;">
                    @if($company->phone){{ $company->phone }}<br>@endif
                    @if($company->email)<a href="mailto:{{ $company->email }}" style="color:#57534E;text-decoration:none;">{{ $company->email }}</a><br>@endif
                    @if($website)<a href="{{ $company->website }}" style="color:#57534E;text-decoration:none;">{{ $website }}</a><br>@endif
                    @if($company->kvk_number){{ market('registry.short') }} {{ $company->kvk_number }}@endif
                </p>
            </div>
        </div>
    </div>
    <p style="font-size:11.5px;line-height:1.5;color:#A8A29E;text-align:center;margin:16px 0 0;">{{ __('Verstuurd met :brand namens :company.', ['brand' => brand('name'), 'company' => $company->name]) }}</p>
</div>
</body>
</html>
