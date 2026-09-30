@php
    $appUrl = rtrim(config('app.url'), '/');
    $section = function (string $title, array $rows, string $empty = '') {
        return ['title' => $title, 'rows' => $rows, 'empty' => $empty];
    };
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Je projectplanning') }}</title>
    <style>
        body { margin: 0; padding: 0; background: #FAFAF9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1C1917; }
        .wrapper { width: 100%; background: #FAFAF9; padding: 40px 16px; }
        .container { max-width: 560px; margin: 0 auto; background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(28,25,23,0.08); }
        .header { background: linear-gradient(135deg, {{ brand('color') }} 0%, {{ brand('color_dark') }} 100%); padding: 28px 36px; color: white; }
        .logo { font-size: 20px; font-weight: 700; letter-spacing: -0.01em; }
        .header-sub { font-size: 13px; opacity: 0.9; margin-top: 6px; }
        .body { padding: 32px 36px 28px; }
        h1 { font-size: 21px; font-weight: 600; letter-spacing: -0.015em; margin: 0 0 6px; }
        p { font-size: 15px; line-height: 1.6; color: #44403C; margin: 0 0 16px; }
        h2 { font-size: 14px; font-weight: 700; margin: 26px 0 10px; color: #1C1917; }
        .list { width: 100%; border-collapse: collapse; font-size: 14px; }
        .list td { padding: 9px 0; border-bottom: 1px solid #E7E5E4; color: #44403C; vertical-align: top; }
        .list tr:last-child td { border-bottom: none; }
        .list .who { font-weight: 600; color: #1C1917; }
        .list .nr { font-size: 12px; color: #A8A29E; }
        .list .when { text-align: right; white-space: nowrap; }
        .ok { color: #15803D; font-size: 12px; }
        .warn { color: #B81814; font-size: 12px; }
        .muted { color: #78716C; font-size: 12px; }
        .btn { display: inline-block; background: {{ brand('color') }}; color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 600; padding: 12px 24px; border-radius: 8px; }
        .btn-wrap { text-align: center; margin: 28px 0 4px; }
        .footer { padding: 20px 36px 28px; font-size: 12px; color: #A8A29E; text-align: center; line-height: 1.6; }
        .footer a { color: #78716C; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="header">
            <div class="logo">{{ brand('name') }}</div>
            <div class="header-sub">{{ __('Projectplanning') }} · {{ __('week :week', ['week' => $d['week']]) }}</div>
        </div>

        <div class="body">
            <h1>{{ __('Goedemorgen') }}</h1>
            <p>{{ __('Dit staat er deze week op de planning van :company.', ['company' => $company->name]) }}</p>

            @foreach ([
                ['title' => '🚧 ' . __('Deze week start'), 'rows' => $d['this_week']],
                ['title' => '📅 ' . __('Volgende week start'), 'rows' => $d['next_week']],
                ['title' => '⚠️ ' . __('Probleem gemeld'), 'rows' => $d['problems']],
                ['title' => '⏳ ' . __('Wacht op antwoord (eerder beginnen)'), 'rows' => $d['pending']],
                ['title' => '📝 ' . __('Gegund, nog in te plannen'), 'rows' => $d['unplanned']],
            ] as $block)
                @if (count($block['rows']))
                    <h2>{{ $block['title'] }}</h2>
                    <table class="list">
                        @foreach ($block['rows'] as $r)
                            <tr>
                                <td>
                                    <span class="who">{{ $r['title'] }}</span>@if($r['who']) · {{ $r['who'] }}@endif<br>
                                    <span class="nr">{{ $r['project'] }}</span>
                                    @if($r['problem'])<br><span class="warn">{{ $r['problem'] }}</span>@endif
                                </td>
                                <td class="when">
                                    @if($r['starts']){{ $r['starts'] }}@if($r['ends']) – {{ $r['ends'] }}@endif @else <span class="muted">{{ __('geen datum') }}</span>@endif
                                    @if($r['confirmed'])<br><span class="ok">{{ __('bevestigd') }}</span>@elseif($r['starts'])<br><span class="muted">{{ __('nog niet bevestigd') }}</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            @endforeach

            <div class="btn-wrap">
                <a href="{{ $appUrl }}/projecten" class="btn">{{ __('Open de projecten') }}</a>
            </div>
        </div>

        <div class="footer">
            {{ __('Je ontvangt dit overzicht op maandag zolang er projecten met een planning open staan.') }}
        </div>
    </div>
</div>
</body>
</html>
