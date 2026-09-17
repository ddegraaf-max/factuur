@php
    $color = $company->brand_color ?: brand('color');
    $name = $subcontractor?->contact_name ?: $subcontractor?->name;
    $deadline = $round->deadline?->translatedFormat('j F Y');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; background: #FAFAF9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .wrapper { width: 100%; background: #FAFAF9; padding: 40px 16px; }
        .container { max-width: 560px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(28,25,23,0.08); }
        .header { background: {{ $color }}; padding: 24px 36px; color: white; font-size: 18px; font-weight: 700; letter-spacing: -0.01em; }
        .body { padding: 32px 36px 28px; }
        h1 { font-size: 21px; font-weight: 600; letter-spacing: -0.015em; margin: 0 0 12px; color: #1C1917; }
        p { font-size: 15px; line-height: 1.6; color: #44403C; margin: 0 0 14px; }
        .box { background: #F5F5F4; border: 1px solid #E7E5E4; border-radius: 10px; padding: 14px 18px; margin: 18px 0; font-size: 14px; color: #1C1917; }
        .box table { border-collapse: collapse; width: 100%; }
        .box td { padding: 4px 0; vertical-align: top; font-size: 14px; }
        .box td.k { color: #78716C; width: 150px; }
        .btn-td { border-radius: 8px; background: {{ $color }}; }
        .btn { display: inline-block; padding: 13px 26px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px; }
        .meta { font-size: 13px; color: #78716C; margin-top: 22px; padding-top: 18px; border-top: 1px solid #E7E5E4; }
        .footer { padding: 18px 36px 26px; font-size: 12px; color: #A8A29E; text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">{{ $company->name }}</div>
            <div class="body">
                @if($kind === 'award')
                    <h1>{{ __('Wij gunnen u de opdracht') }} 🎉</h1>
                    <p>{{ __('Beste :name,', ['name' => $name]) }}</p>
                    <p>{{ __('Bedankt voor uw prijsopgave voor :package. Wij gunnen u de opdracht en nemen binnenkort contact met u op over de planning en de opdrachtbevestiging.', ['package' => $round->title]) }}</p>
                @elseif($kind === 'reject')
                    <h1>{{ __('Prijsaanvraag :package', ['package' => $round->title]) }}</h1>
                    <p>{{ __('Beste :name,', ['name' => $name]) }}</p>
                    <p>{{ __('Bedankt voor uw prijsopgave. Voor dit project hebben wij een andere partij gekozen. Wij houden u graag in beeld voor volgende projecten.') }}</p>
                @elseif($kind === 'reminder')
                    <h1>{{ __('Herinnering: prijsaanvraag :package', ['package' => $round->title]) }}</h1>
                    <p>{{ __('Beste :name,', ['name' => $name]) }}</p>
                    <p>{{ __('Wij hebben nog geen reactie van u ontvangen op onze prijsaanvraag. Kunt u uiterlijk :deadline uw prijs en beschikbaarheid doorgeven? Dat kan in een minuut via de knop hieronder.', ['deadline' => $deadline]) }}</p>
                @else
                    <h1>{{ __('Prijsaanvraag: :package', ['package' => $round->title]) }}</h1>
                    <p>{{ __('Beste :name,', ['name' => $name]) }}</p>
                    <p>{{ __(':company vraagt u om een prijs en uw beschikbaarheid voor onderstaand onderdeel van een project. Reageren kan in een minuut via de knop hieronder.', ['company' => $company->name]) }}</p>
                @endif

                @if(in_array($kind, ['request', 'reminder'], true))
                    <div class="box">
                        <table>
                            <tr><td class="k">{{ __('Onderdeel') }}</td><td><strong>{{ $round->title }}</strong></td></tr>
                            @if($round->location)<tr><td class="k">{{ __('Locatie') }}</td><td>{{ $round->location }}</td></tr>@endif
                            @if($round->start_week)<tr><td class="k">{{ __('Gewenste start') }}</td><td>{{ __('week :week', ['week' => $round->start_week]) }}</td></tr>@endif
                            <tr><td class="k">{{ __('Reageren vóór') }}</td><td><strong>{{ $deadline }}</strong></td></tr>
                        </table>
                        @if($round->description)
                            <p style="margin:12px 0 0;font-size:14px;line-height:1.6;color:#44403C;">{!! nl2br(e($round->description)) !!}</p>
                        @endif
                    </div>
                    <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 20px 0 6px;">
                        <tr>
                            <td class="btn-td">
                                <a href="{{ $url }}" class="btn">{{ __('Prijs en beschikbaarheid doorgeven') }}&nbsp;&nbsp;→</a>
                            </td>
                        </tr>
                    </table>
                    <p style="font-size:13px;color:#78716C;">{{ __('Liever uw eigen offerte sturen? Die kunt u op dezelfde pagina als PDF toevoegen. Antwoorden op deze mail kan ook.') }}</p>
                @endif

                <div class="meta">
                    {{ __('Met vriendelijke groet,') }}<br>
                    <strong>{{ $company->name }}</strong>
                    @if($company->phone)<br>{{ $company->phone }}@endif
                    @if($company->email)<br>{{ $company->email }}@endif
                </div>
            </div>
            <div class="footer">{{ __('doc.mail_sent_via', ['name' => $company->name, 'brand' => brand('name')]) }}</div>
        </div>
    </div>
</body>
</html>
