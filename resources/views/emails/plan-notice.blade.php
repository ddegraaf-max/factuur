@php
    $name = $item->subcontractor?->name;
    $answer = $item->request_answer;
    $headline = match (true) {
        $kind === 'problem' => __(':name meldt een probleem', ['name' => $name]),
        $answer === 'accepted' => __(':name kan eerder beginnen', ['name' => $name]),
        $answer === 'counter' => __(':name stelt een andere dag voor', ['name' => $name]),
        default => __(':name kan niet eerder beginnen', ['name' => $name]),
    };
    $body = match (true) {
        $kind === 'problem' => __('Over :title op :project kwam via de planningslink dit bericht binnen:', ['title' => $item->title, 'project' => $project->label()]),
        $answer === 'accepted' => __(':title op :project is verschoven naar :date. De vooraankondiging en herinnering gaan opnieuw op tijd uit.', ['title' => $item->title, 'project' => $project->label(), 'date' => $item->starts_on?->translatedFormat('l j F')]),
        $answer === 'counter' => __('Gevraagd was :asked; :name kan op :date. De planning van :title op :project is daarop gezet.', ['asked' => $item->request_start?->translatedFormat('l j F'), 'name' => $name, 'date' => $item->starts_on?->translatedFormat('l j F'), 'title' => $item->title, 'project' => $project->label()]),
        default => __('Gevraagd was :asked. De geplande start van :title op :project blijft :date.', ['asked' => $item->request_start?->translatedFormat('l j F'), 'title' => $item->title, 'project' => $project->label(), 'date' => $item->starts_on?->translatedFormat('l j F')]),
    };
    $message = $kind === 'problem' ? $item->problem : $item->request_message;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $headline }}</title>
</head>
<body style="margin:0;padding:0;background:#FAFAF9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#1C1917;">
<div style="width:100%;background:#FAFAF9;padding:40px 16px;">
    <div style="max-width:560px;margin:0 auto;background:#FFFFFF;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(28,25,23,0.08);">
        <div style="background:linear-gradient(135deg, {{ brand('color') }} 0%, {{ brand('color_dark') }} 100%);padding:28px 36px;color:#fff;">
            <div style="font-size:20px;font-weight:700;letter-spacing:-0.01em;">{{ brand('name') }}</div>
            <div style="font-size:13px;opacity:0.9;margin-top:6px;">{{ __('Projectplanning') }} · {{ $project->label() }}</div>
        </div>
        <div style="padding:32px 36px 28px;">
            <h1 style="font-size:21px;font-weight:600;letter-spacing:-0.015em;margin:0 0 6px;">{{ $headline }}</h1>
            <p style="font-size:15px;line-height:1.6;color:#44403C;margin:0 0 16px;">{{ $body }}</p>
            @if($message)
                <div style="background:#F5F5F4;border-left:3px solid {{ brand('color') }};border-radius:8px;padding:12px 16px;font-size:14.5px;line-height:1.6;color:#292524;white-space:pre-wrap;margin:0 0 20px;">{{ $message }}</div>
            @endif
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #E7E5E4;border-radius:10px;border-collapse:separate;overflow:hidden;font-size:14px;">
                <tr><td style="padding:10px 14px;color:#78716C;background:#FAFAF9;width:150px;border-bottom:1px solid #E7E5E4;">{{ __('Onderdeel') }}</td><td style="padding:10px 14px;border-bottom:1px solid #E7E5E4;font-weight:600;">{{ $item->title }}</td></tr>
                <tr><td style="padding:10px 14px;color:#78716C;background:#FAFAF9;border-bottom:1px solid #E7E5E4;">{{ __('Onderaannemer') }}</td><td style="padding:10px 14px;border-bottom:1px solid #E7E5E4;">{{ $name }}@if($item->subcontractor?->phone) · {{ $item->subcontractor->phone }}@endif</td></tr>
                <tr><td style="padding:10px 14px;color:#78716C;background:#FAFAF9;">{{ __('Geplande start') }}</td><td style="padding:10px 14px;">{{ $item->starts_on?->translatedFormat('l j F Y') ?? '—' }}</td></tr>
            </table>
            <div style="text-align:center;margin:28px 0 4px;">
                <a href="{{ $url }}" style="display:inline-block;background:{{ brand('color') }};color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;padding:12px 24px;border-radius:8px;">{{ __('Open de planning') }}</a>
            </div>
        </div>
        <div style="padding:20px 36px 28px;font-size:12px;color:#A8A29E;text-align:center;line-height:1.6;">
            {{ __('Je ontvangt dit bericht omdat een onderaannemer via de planningslink van :brand heeft gereageerd.', ['brand' => brand('name')]) }}
        </div>
    </div>
</div>
</body>
</html>
