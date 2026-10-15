@php
    $color = (string) brand('color', '#0B2545');
    $accent = (string) brand('accent', '#F4781F');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#F3F5F9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#0F172A;">
<div style="max-width:600px;margin:0 auto;padding:28px 16px;">
    <div style="background:#FFFFFF;border:1px solid #E2E8F0;border-radius:16px;overflow:hidden;">
        <div style="padding:22px 32px;background:{{ $color }};color:#fff;">
            <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr>
                <td style="vertical-align:middle;padding-right:10px;"><img src="{{ \App\Support\Brand::asset('email_mark') }}" alt="" width="34" height="34" style="display:block;border:0;border-radius:8px;"></td>
                <td style="vertical-align:middle;font-size:20px;font-weight:800;letter-spacing:0.02em;">FACH<span style="color:{{ $accent }};">ORA</span></td>
            </tr></table>
            <div style="font-size:12.5px;opacity:0.85;margin-top:6px;">{{ brand('tagline') }}</div>
        </div>

        <div style="padding:28px 32px 30px;">
            <div style="font-size:11.5px;font-weight:700;letter-spacing:0.09em;text-transform:uppercase;color:{{ $accent }};margin:0 0 6px;">{{ $eyebrow }}</div>
            <h1 style="font-size:22px;line-height:1.3;font-weight:700;letter-spacing:-0.015em;margin:0 0 18px;color:#0F172A;">{{ $title }}</h1>

            @foreach($paragraphs as $paragraph)
                <p style="font-size:15px;line-height:1.65;color:#334155;margin:0 0 12px;white-space:pre-line;">{{ $paragraph }}</p>
            @endforeach

            @if(!empty($quote))
                <div style="margin:14px 0 18px;padding:14px 16px;border-left:4px solid {{ $accent }};background:#F8FAFC;border-radius:0 10px 10px 0;font-size:14.5px;line-height:1.6;color:#334155;white-space:pre-line;">{{ $quote }}</div>
            @endif

            @if(!empty($facts))
                <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:14px 0 20px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;">
                    @foreach($facts as $k => $v)
                        <tr>
                            <td style="padding:9px 14px;border-bottom:1px solid #E2E8F0;color:#64748B;width:40%;vertical-align:top;">{{ $k }}</td>
                            <td style="padding:9px 14px;border-bottom:1px solid #E2E8F0;font-weight:600;color:#0F172A;vertical-align:top;">{{ $v }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            @if($button && !empty($button['url']))
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 18px;"><tr>
                    <td style="border-radius:10px;background:{{ $accent }};">
                        <a href="{{ $button['url'] }}" style="display:inline-block;padding:13px 26px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">{{ $button['label'] }}</a>
                    </td>
                </tr></table>
                <p style="font-size:12.5px;line-height:1.6;color:#94A3B8;margin:0 0 8px;word-break:break-all;">{{ $button['url'] }}</p>
            @endif

            @if(!empty($footnote))
                <p style="font-size:12.5px;line-height:1.6;color:#94A3B8;margin:22px 0 0;padding-top:16px;border-top:1px solid #E2E8F0;word-break:break-word;">{{ $footnote }}</p>
            @endif
        </div>
    </div>
    <div style="padding:18px 8px 0;font-size:12px;color:#94A3B8;text-align:center;line-height:1.6;">
        Fachora · {{ brand('domain') }} · {{ brand('email') }}
    </div>
</div>
</body>
</html>
