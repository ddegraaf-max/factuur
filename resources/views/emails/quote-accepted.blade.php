@php
    /* Bevestiging na akkoord: vinkje, bevestiging met datum/ondertekenaar,
       overzicht, eventueel termijnplan, "hoe nu verder", PDF-bijlage. */
    $brand = $company->brand_color ?: '#E8231F';
    $logo = $company->logoBinary();
    $total = number_format((float) $quote->total, 2, ',', '.');
    $acceptedAt = $quote->signed_at ?? $quote->accepted_at ?? now();
    $signed = (bool) $quote->signed_at;
    $installments = $quote->relationLoaded('installments') ? $quote->installments : collect();
    $portalUrl = ! empty($preview) ? '#' : $quote->portalUrl();
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('doc.mail_accept_title') }}</title>
</head>
<body style="margin:0;background:#f5f5f4;font-family:Arial,Helvetica,sans-serif;color:#1c1917;">
  <div style="max-width:600px;margin:0 auto;padding:24px;">
    <div style="background:#fff;border:1px solid #e7e5e4;border-radius:12px;overflow:hidden;">
      <div style="padding:20px 24px;border-bottom:1px solid #e7e5e4;">
        @if($logo && isset($message))
          <img src="{{ $message->embedData($logo['data'], $logo['name'], $logo['mime']) }}" alt="{{ $company->name }}" style="max-height:44px;max-width:220px;display:block;border:0;">
        @elseif($logo && ! empty($preview))
          <img src="{{ $company->logo_data }}" alt="{{ $company->name }}" style="max-height:44px;max-width:220px;display:block;border:0;">
        @else
          <div style="font-weight:700;font-size:18px;color:{{ $brand }};">{{ $company->name }}</div>
        @endif
      </div>

      <div style="padding:34px 24px 6px;text-align:center;">
        <table role="presentation" align="center" cellpadding="0" cellspacing="0" style="margin:0 auto 18px;">
          <tr><td style="width:68px;height:68px;border-radius:34px;background:{{ $brand }};text-align:center;vertical-align:middle;color:#ffffff;font-size:34px;line-height:68px;font-weight:700;">&#10003;</td></tr>
        </table>
        <h1 style="margin:0;font-size:24px;line-height:1.3;letter-spacing:-0.01em;color:#1c1917;">{{ __('doc.mail_accept_title') }}</h1>
      </div>

      <div style="padding:14px 24px 26px;font-size:14px;line-height:1.7;">
        @if(!empty($customBody))
          <p style="margin:0 0 14px;">{!! nl2br(e($customBody)) !!}</p>
        @else
          <p style="margin:0 0 14px;">{{ __('doc.mail_greeting', ['name' => $quote->signed_name ?: $quote->customer_name]) }}</p>
          <p style="margin:0 0 14px;">{!! __('doc.mail_accept_intro', [
              'number' => e($quote->number),
              'company' => e($company->name),
              'date' => e($acceptedAt->translatedFormat('j F Y')),
              'signed' => $signed ? __('doc.mail_accept_signed_suffix') : '',
          ]) !!}</p>
          <p style="margin:0 0 14px;">{{ __('doc.mail_accept_next') }}</p>
        @endif

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 22px;border:1px solid #e7e5e4;border-radius:10px;background:#fafaf9;">
          <tr><td style="padding:14px 18px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13.5px;line-height:1.5;">
              <tr><td style="padding:6px 0;color:#78716c;">{{ __('doc.mail_accept_quote') }}</td><td style="padding:6px 0;text-align:right;font-weight:600;">{{ $quote->number }}</td></tr>
              <tr><td style="padding:6px 0;color:#78716c;border-top:1px solid #ebe9e6;">{{ __('doc.mail_accept_date') }}</td><td style="padding:6px 0;text-align:right;font-weight:600;border-top:1px solid #ebe9e6;">{{ $acceptedAt->translatedFormat('j F Y') }}</td></tr>
              @if($signed)
              <tr><td style="padding:6px 0;color:#78716c;border-top:1px solid #ebe9e6;">{{ __('doc.mail_accept_signed_by') }}</td><td style="padding:6px 0;text-align:right;font-weight:600;border-top:1px solid #ebe9e6;">{{ $quote->signed_name }}</td></tr>
              @endif
              <tr><td style="padding:6px 0;color:#78716c;border-top:1px solid #ebe9e6;">{{ __('doc.mail_accept_total') }}</td><td style="padding:6px 0;text-align:right;font-weight:700;font-size:15px;border-top:1px solid #ebe9e6;">€ {{ $total }}</td></tr>
            </table>
            @if($installments->isNotEmpty())
              <div style="margin:12px 0 4px;font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;color:#78716c;">{{ __('doc.mail_accept_installments') }}</div>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;line-height:1.5;">
                @foreach($installments as $i)
                  <tr><td style="padding:4px 0;color:#44403c;">{{ $i->description }}@if($i->percentage) <span style="color:#a8a29e;">({{ rtrim(rtrim(number_format((float) $i->percentage, 2, ',', '.'), '0'), ',') }}%)</span>@endif</td><td style="padding:4px 0;text-align:right;font-weight:600;">€ {{ number_format((float) $i->amount, 2, ',', '.') }}</td></tr>
                @endforeach
              </table>
            @endif
          </td></tr>
        </table>

        @if($portalUrl)
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 8px;">
            <tr><td style="border-radius:8px;background:{{ $brand }};"><a href="{{ $portalUrl }}" style="display:inline-block;padding:12px 22px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">{{ __('doc.mail_view_quote_online') }}&nbsp;&nbsp;→</a></td></tr>
          </table>
        @endif
        <p style="margin:6px 0 0;color:#78716c;font-size:12.5px;line-height:1.6;">{{ $signed ? __('doc.mail_accept_attachment_signed') : __('doc.mail_accept_attachment') }}</p>

        <p style="margin:22px 0 0;">{{ __('doc.mail_regards') }}<br>{{ $company->name }}</p>
      </div>
    </div>
    <p style="text-align:center;color:#a8a29e;font-size:11px;margin:14px 0 0;">{{ __('doc.mail_sent_via', ['name' => $company->name]) }}</p>
  </div>
</body>
</html>
