@php
    $logo = $company->logoBinary();
@endphp
<!DOCTYPE html>
<html lang="nl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f5f5f4;font-family:Arial,Helvetica,sans-serif;color:#1c1917;">
  <div style="max-width:600px;margin:0 auto;padding:24px;">
    <div style="background:#fff;border:1px solid #e7e5e4;border-radius:12px;overflow:hidden;">
      <div style="padding:20px 24px;border-bottom:1px solid #e7e5e4;">
        @if($logo && isset($message))
          <img src="{{ $message->embedData($logo['data'], $logo['name'], $logo['mime']) }}"
               alt="{{ $company->name }}" style="max-height:44px;max-width:220px;display:block;border:0;">
        @else
          <div style="font-family:Arial,sans-serif;font-weight:700;font-size:18px;color:{{ $company->brand_color ?: brand('color') }};">{{ $company->name }}</div>
        @endif
      </div>
      <div style="padding:24px;font-size:14px;line-height:1.7;color:#1c1917;white-space:pre-wrap;">{{ $bodyText }}</div>
      <div style="padding:0 24px 22px;">
        @if($invoice->portal_token)
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 14px;">
            <tr>
              <td style="border-radius:8px;background:{{ $company->brand_color ?: brand('color') }};">
                <a href="{{ route('portal.invoice', $invoice->portal_token) }}"
                   style="display:inline-block;padding:12px 22px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">
                  Bekijk factuur online&nbsp;&nbsp;→
                </a>
              </td>
            </tr>
          </table>
        @endif
        <p style="font-size:12.5px;color:#78716c;margin:0;">De betreffende factuur ({{ $invoice->number }}) is als PDF bijgevoegd.</p>
      </div>
    </div>
    <p style="text-align:center;color:#a8a29e;font-size:11px;margin:14px 0 0;">
      Verzonden via {{ brand('name') }} namens {{ $company->name }}.
    </p>
  </div>
</body>
</html>
