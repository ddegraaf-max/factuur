@php
    $openRaw = (float) $invoice->total - (float) $invoice->paid_total;
    $open = number_format($openRaw, 2, ',', '.');
    $total = number_format((float) $invoice->total, 2, ',', '.');
    $settled = number_format((float) $invoice->paid_total, 2, ',', '.');
    $terms = (int) ($invoice->payment_terms ?? $company->default_payment_terms ?? 14);
    $logo = $company->logoBinary();
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f5f5f4;font-family:Arial,Helvetica,sans-serif;color:#1c1917;">
  <div style="max-width:600px;margin:0 auto;padding:24px;">
    <div style="background:#fff;border:1px solid #e7e5e4;border-radius:12px;overflow:hidden;">
      <div style="padding:20px 24px;border-bottom:1px solid #e7e5e4;">
        @if($logo && isset($message))
          <img src="{{ $message->embedData($logo['data'], $logo['name'], $logo['mime']) }}"
               alt="{{ $company->name }}" style="max-height:44px;max-width:220px;display:block;border:0;">
        @else
          <div style="font-weight:700;font-size:18px;color:{{ $company->brand_color ?: '#E8231F' }};">{{ $company->name }}</div>
        @endif
      </div>
      <div style="padding:24px;font-size:14px;line-height:1.7;">
        <p style="margin:0 0 14px;">{{ __('doc.mail_greeting', ['name' => $invoice->customer_name]) }}</p>
        <p style="margin:0 0 14px;">
          {!! __('doc.mail_invoice_intro', [
              'number' => e($invoice->number),
              'date' => e(optional($invoice->invoice_date)->translatedFormat('j F Y')),
              'total' => e($total),
          ]) !!}
        </p>
        @if($invoice->paid_total > 0 && $openRaw > 0.009)
          <p style="margin:0 0 14px;">
            {!! __('doc.mail_settled_partial', ['settled' => e($settled), 'open' => e($open)]) !!}
          </p>
        @elseif($invoice->paid_total > 0)
          <p style="margin:0 0 14px;">{{ __('doc.mail_settled_full') }}</p>
        @endif
        @if($openRaw > 0.009)
        <p style="margin:0 0 14px;">
          {!! __('doc.mail_pay_request', [
              'amount' => $invoice->paid_total > 0 ? __('doc.mail_remaining_amount') : __('doc.mail_the_amount'),
              'deadline' => $invoice->due_date
                  ? __('doc.mail_by_date', ['date' => e($invoice->due_date->translatedFormat('j F Y'))])
                  : __('doc.mail_within_days', ['days' => $terms]),
              'account' => $company->iban
                  ? __('doc.mail_to_account', ['iban' => e($company->iban), 'name' => e($company->name)])
                  : '',
              'number' => e($invoice->number),
          ]) !!}
        </p>
        @endif
        @if($invoice->portal_token)
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 6px;">
            <tr>
              <td style="border-radius:8px;background:{{ $company->brand_color ?: '#E8231F' }};">
                <a href="{{ route('portal.invoice', $invoice->portal_token) }}"
                   style="display:inline-block;padding:12px 22px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">
                  {{ (filled($company->mollie_api_key ?? null) && $openRaw > 0.009) ? __('doc.mail_view_pay_invoice') : __('doc.mail_view_invoice') }}&nbsp;&nbsp;→
                </a>
              </td>
            </tr>
          </table>
          <p style="margin:0 0 14px;color:#78716c;font-size:12.5px;line-height:1.6;">
            {{ __('doc.mail_portal_hint') }}
          </p>
        @endif
        @if($company->invoice_footer)
          <p style="margin:16px 0 0;color:#78716c;font-size:13px;line-height:1.6;">{{ $company->invoice_footer }}</p>
        @endif
        <p style="margin:18px 0 0;">{{ __('doc.mail_regards') }}<br>{{ $company->name }}</p>
      </div>
    </div>
    <p style="text-align:center;color:#a8a29e;font-size:11px;margin:14px 0 0;">
      {{ __('doc.mail_sent_via', ['name' => $company->name]) }}
    </p>
  </div>
</body>
</html>
