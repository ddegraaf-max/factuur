@php
    /* Offerte vervallen: korte, nette mededeling. Geen PDF — de oude offerte
       hoeft niet nog eens mee. Wel de vervanger, als die er is. */
    $brand = $company->brand_color ?: brand('color');
    $logo = $company->logoBinary();
    $withdrawnAt = $quote->withdrawn_at ?? now();
    $replacementNumber = $replacement?->number;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('doc.mail_withdrawn_title', ['number' => $quote->number]) }}</title>
</head>
<body style="margin:0;background:#f5f5f4;font-family:Arial,Helvetica,sans-serif;color:#1c1917;">
  <div style="max-width:600px;margin:0 auto;padding:24px;">
    <div style="background:#fff;border:1px solid #e7e5e4;border-radius:12px;overflow:hidden;">
      <div style="padding:20px 24px;border-bottom:1px solid #e7e5e4;">
        @if($logo && isset($message))
          <img src="{{ $message->embedData($logo['data'], $logo['name'], $logo['mime']) }}" alt="{{ $company->name }}" style="max-height:44px;max-width:220px;display:block;border:0;">
        @else
          <div style="font-weight:700;font-size:18px;color:{{ $brand }};">{{ $company->name }}</div>
        @endif
      </div>

      <div style="padding:34px 24px 6px;text-align:center;">
        <table role="presentation" align="center" cellpadding="0" cellspacing="0" style="margin:0 auto 18px;">
          <tr><td style="width:68px;height:68px;border-radius:34px;background:#78716c;text-align:center;vertical-align:middle;color:#ffffff;font-size:34px;line-height:68px;font-weight:700;font-family:Georgia,'Times New Roman',serif;">i</td></tr>
        </table>
        <h1 style="margin:0;font-size:24px;line-height:1.3;letter-spacing:-0.01em;color:#1c1917;">{{ __('doc.mail_withdrawn_title', ['number' => $quote->number]) }}</h1>
      </div>

      <div style="padding:14px 24px 26px;font-size:14px;line-height:1.7;">
        <p style="margin:0 0 14px;">{{ __('doc.mail_greeting', ['name' => $quote->customer_name]) }}</p>
        @if($quote->accepted_at)
          <p style="margin:0 0 14px;">{!! __('doc.mail_withdrawn_intro_accepted', [
              'number' => e($quote->number),
              'company' => e($company->name),
              'date' => e($quote->quote_date->translatedFormat('j F Y')),
              'accepted' => e(($quote->signed_at ?? $quote->accepted_at)->translatedFormat('j F Y')),
          ]) !!}</p>
        @else
          <p style="margin:0 0 14px;">{!! __('doc.mail_withdrawn_intro', [
              'number' => e($quote->number),
              'company' => e($company->name),
              'date' => e($quote->quote_date->translatedFormat('j F Y')),
          ]) !!}</p>
        @endif
        @if($replacementNumber)
          <p style="margin:0 0 14px;">{!! __('doc.mail_withdrawn_replaced', ['replacement' => e($replacementNumber)]) !!} {{ $replacementUrl ? __('doc.mail_withdrawn_replaced_received') : '' }}</p>
        @elseif($replacement)
          <p style="margin:0 0 14px;">{{ __('doc.mail_withdrawn_replaced_soon') }}</p>
        @endif
        @if($quote->accepted_at && $replacement && $replacement->status === 'sent')
          <p style="margin:0 0 14px;">{{ __('doc.mail_withdrawn_reconfirm') }}</p>
        @endif

        @if(filled($quote->withdrawal_note))
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 18px;">
            <tr><td style="padding:12px 16px;border-left:3px solid {{ $brand }};background:#fafaf9;border-radius:0 8px 8px 0;font-size:13.5px;line-height:1.6;color:#44403c;">
              <div style="font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;color:#78716c;margin-bottom:4px;">{{ __('doc.mail_withdrawn_note') }}</div>
              {!! nl2br(e($quote->withdrawal_note)) !!}
            </td></tr>
          </table>
        @endif

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:14px 0 22px;border:1px solid #e7e5e4;border-radius:10px;background:#fafaf9;">
          <tr><td style="padding:14px 18px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13.5px;line-height:1.5;">
              <tr><td style="padding:6px 0;color:#78716c;">{{ __('doc.mail_withdrawn_quote') }}</td><td style="padding:6px 0;text-align:right;font-weight:600;text-decoration:line-through;color:#78716c;">{{ $quote->number }}</td></tr>
              <tr><td style="padding:6px 0;color:#78716c;border-top:1px solid #ebe9e6;">{{ __('doc.mail_withdrawn_date') }}</td><td style="padding:6px 0;text-align:right;font-weight:600;border-top:1px solid #ebe9e6;">{{ $withdrawnAt->translatedFormat('j F Y') }}</td></tr>
              @if($replacementNumber)
              <tr><td style="padding:6px 0;color:#78716c;border-top:1px solid #ebe9e6;">{{ __('doc.mail_withdrawn_replacement') }}</td><td style="padding:6px 0;text-align:right;font-weight:700;border-top:1px solid #ebe9e6;">{{ $replacementNumber }}</td></tr>
              @endif
            </table>
          </td></tr>
        </table>

        @if($replacementUrl)
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 8px;">
            <tr><td style="border-radius:8px;background:{{ $brand }};"><a href="{{ $replacementUrl }}" style="display:inline-block;padding:12px 22px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">{{ __('doc.mail_withdrawn_view_replacement') }}&nbsp;&nbsp;→</a></td></tr>
          </table>
        @endif
        <p style="margin:6px 0 0;color:#78716c;font-size:12.5px;line-height:1.6;">{{ __('doc.mail_withdrawn_questions') }}</p>

        <p style="margin:22px 0 0;">{{ __('doc.mail_regards') }}<br>{{ $company->name }}</p>
      </div>
    </div>
    <p style="text-align:center;color:#a8a29e;font-size:11px;margin:14px 0 0;">{{ __('doc.mail_sent_via', ['name' => $company->name, 'brand' => brand('name')]) }}</p>
  </div>
</body>
</html>
