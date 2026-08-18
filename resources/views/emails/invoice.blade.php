@php
    $open = number_format((float) $invoice->total - (float) $invoice->paid_total, 2, ',', '.');
    $total = number_format((float) $invoice->total, 2, ',', '.');
    $terms = (int) ($invoice->payment_terms ?? $company->default_payment_terms ?? 14);
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
          <div style="font-weight:700;font-size:18px;color:{{ $company->brand_color ?: '#E8231F' }};">{{ $company->name }}</div>
        @endif
      </div>
      <div style="padding:24px;font-size:14px;line-height:1.7;">
        <p style="margin:0 0 14px;">Beste {{ $invoice->customer_name }},</p>
        <p style="margin:0 0 14px;">
          Hierbij ontvangt u factuur <strong>{{ $invoice->number }}</strong> van {{ optional($invoice->invoice_date)->format('d-m-Y') }}
          voor een bedrag van <strong>€ {{ $total }}</strong>. De factuur vindt u als PDF in de bijlage.
        </p>
        <p style="margin:0 0 14px;">
          Wij verzoeken u het bedrag
          @if($invoice->due_date)
            uiterlijk <strong>{{ $invoice->due_date->translatedFormat('j F Y') }}</strong>
          @else
            binnen <strong>{{ $terms }} dagen</strong>
          @endif
          te voldoen
          @if($company->iban)op <strong>{{ $company->iban }}</strong> t.n.v. {{ $company->name }}@endif
          onder vermelding van factuurnummer <strong>{{ $invoice->number }}</strong>.
        </p>
        @if($invoice->portal_token)
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 6px;">
            <tr>
              <td style="border-radius:8px;background:{{ $company->brand_color ?: '#E8231F' }};">
                <a href="{{ route('portal.invoice', $invoice->portal_token) }}"
                   style="display:inline-block;padding:12px 22px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">
                  Bekijk factuur online&nbsp;&nbsp;→
                </a>
              </td>
            </tr>
          </table>
          <p style="margin:0 0 14px;color:#78716c;font-size:12.5px;line-height:1.6;">
            In de beveiligde online omgeving ziet u de factuur, de betaalstatus en kunt u de PDF opnieuw downloaden.
            Voor uw veiligheid bevestigt u eerst uw e-mailadres met een eenmalige code.
          </p>
        @endif
        @if($company->invoice_footer)
          <p style="margin:16px 0 0;color:#78716c;font-size:13px;line-height:1.6;">{{ $company->invoice_footer }}</p>
        @endif
        <p style="margin:18px 0 0;">Met vriendelijke groet,<br>{{ $company->name }}</p>
      </div>
    </div>
    <p style="text-align:center;color:#a8a29e;font-size:11px;margin:14px 0 0;">
      Verzonden via EasyInvoice namens {{ $company->name }}.
    </p>
  </div>
</body>
</html>
