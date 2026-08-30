@php
    $open = money((float) $invoice->total - (float) $invoice->paid_total);
    $total = money($invoice->total);
    $paid = money($invoice->paid_total);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f5f5f4;font-family:Arial,Helvetica,sans-serif;color:#1c1917;">
  <div style="max-width:640px;margin:0 auto;padding:24px;">
    <div style="background:#fff;border:1px solid #e7e5e4;border-radius:12px;overflow:hidden;">
      <div style="background:#0f172a;color:#fff;padding:20px 24px;">
        <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#fcd34d;">{{ __('Incasso-opdracht') }}</div>
        <div style="font-size:20px;font-weight:700;margin-top:2px;">{{ __('Dossier :reference', ['reference' => $invoice->incasso_reference]) }}</div>
      </div>

      <div style="padding:22px 24px;">
        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
          {{ __('Beste :partner,', ['partner' => \App\Support\Market::incasso('partner_name')]) }}<br><br>
          {{ __('Hierbij dragen wij namens onze klant een achterstallige vordering ter incasso aan u over. De opdrachtgever heeft de opdracht bevestigd en gaat daarmee akkoord met overdracht aan :partner. Het volledige dossier vindt u hieronder en in de bijlagen.', ['partner' => \App\Support\Market::incasso('partner_name')]) }}
        </p>

        <h3 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#78716c;margin:20px 0 8px;">{{ __('Opdrachtgever (crediteur)') }}</h3>
        <table style="width:100%;font-size:14px;line-height:1.6;border-collapse:collapse;">
          <tr><td style="padding:2px 0;"><strong>{{ $company->name }}</strong></td></tr>
          @if($company->address_line)<tr><td style="padding:2px 0;">{{ $company->address_line }}, {{ $company->postal_code }} {{ $company->city }}</td></tr>@endif
          <tr><td style="padding:2px 0;">{{ __('KvK :kvk · BTW :vat', ['kvk' => $company->kvk_number, 'vat' => $company->vat_number]) }}</td></tr>
          @if($company->iban)<tr><td style="padding:2px 0;">IBAN {{ $company->iban }}</td></tr>@endif
          @if($company->email)<tr><td style="padding:2px 0;">{{ $company->email }}</td></tr>@endif
        </table>

        <h3 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#78716c;margin:20px 0 8px;">{{ __('Debiteur') }}</h3>
        <table style="width:100%;font-size:14px;line-height:1.6;border-collapse:collapse;">
          <tr><td style="padding:2px 0;"><strong>{{ $invoice->customer_name }}</strong></td></tr>
          @if($invoice->customer_address_line)<tr><td style="padding:2px 0;">{{ $invoice->customer_address_line }}, {{ $invoice->customer_postal_code }} {{ $invoice->customer_city }}</td></tr>@endif
          @if($invoice->customer_kvk_number)<tr><td style="padding:2px 0;">{{ __('KvK :kvk', ['kvk' => $invoice->customer_kvk_number]) }}</td></tr>@endif
          @if($invoice->customer_email)<tr><td style="padding:2px 0;">{{ $invoice->customer_email }}</td></tr>@endif
        </table>

        <h3 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#78716c;margin:20px 0 8px;">{{ __('Vordering') }}</h3>
        <table style="width:100%;font-size:14px;line-height:1.7;border-collapse:collapse;">
          <tr><td style="color:#78716c;">{{ __('Factuurnummer') }}</td><td style="text-align:right;font-weight:600;">{{ $invoice->number }}</td></tr>
          <tr><td style="color:#78716c;">{{ __('Factuurdatum') }}</td><td style="text-align:right;">{{ optional($invoice->invoice_date)->format(market('date_format')) }}</td></tr>
          <tr><td style="color:#78716c;">{{ __('Vervaldatum') }}</td><td style="text-align:right;">{{ optional($invoice->due_date)->format(market('date_format')) }}</td></tr>
          <tr><td style="color:#78716c;">{{ __('Factuurbedrag') }}</td><td style="text-align:right;">{{ $total }}</td></tr>
          <tr><td style="color:#78716c;">{{ __('Reeds betaald') }}</td><td style="text-align:right;">{{ $paid }}</td></tr>
          <tr><td style="border-top:2px solid #1c1917;padding-top:6px;font-weight:700;">{{ __('Openstaand bedrag') }}</td><td style="border-top:2px solid #1c1917;padding-top:6px;text-align:right;font-weight:700;">{{ $open }}</td></tr>
        </table>

        <h3 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#78716c;margin:20px 0 8px;">{{ __('Verloop') }}</h3>
        @if($invoice->reminderLogs->isEmpty() && $invoice->payments->isEmpty())
          <p style="font-size:13px;color:#78716c;margin:0;">{{ __('Geen eerdere herinneringen/aanmaningen of betalingen geregistreerd.') }}</p>
        @else
          <table style="width:100%;font-size:13px;line-height:1.6;border-collapse:collapse;">
            @foreach($invoice->reminderLogs as $log)
              <tr>
                <td style="padding:3px 0;color:#78716c;white-space:nowrap;">{{ optional($log->sent_at)->format(market('date_format')) }}</td>
                <td style="padding:3px 0;">{{ $log->sent_to ? __(':type verstuurd aan :to', ['type' => $log->type, 'to' => $log->sent_to]) : __(':type verstuurd', ['type' => $log->type]) }}</td>
              </tr>
            @endforeach
            @foreach($invoice->payments as $p)
              <tr>
                <td style="padding:3px 0;color:#78716c;white-space:nowrap;">{{ optional($p->paid_on)->format(market('date_format')) }}</td>
                <td style="padding:3px 0;">{{ __('Betaling ontvangen: :amount', ['amount' => money((float) $p->amount)]) }}</td>
              </tr>
            @endforeach
          </table>
        @endif

        <h3 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#78716c;margin:20px 0 8px;">{{ __('Bijlagen') }}</h3>
        <p style="font-size:13px;color:#44403c;margin:0;line-height:1.6;">
          @php
              $extra = $invoice->attachments->count();
          @endphp
          {{ $extra
              ? __('Factuur (:file) en :count meegestuurde bijlage(n) uit het originele dossier zijn als bijlage toegevoegd.', ['file' => $invoice->number . '.pdf', 'count' => $extra])
              : __('Factuur (:file) is als bijlage toegevoegd.', ['file' => $invoice->number . '.pdf']) }}
        </p>

        <p style="margin:22px 0 0;font-size:13px;color:#78716c;line-height:1.6;">
          {{ __('doc.mail_regards') }}<br>
          {{ $company->name }} · {{ __('via :brand', ['brand' => brand('name')]) }}
        </p>
      </div>
    </div>
    <p style="text-align:center;color:#a8a29e;font-size:11px;margin:14px 0 0;">
      {{ __('Automatisch gegenereerd door :brand namens :company.', ['brand' => brand('name'), 'company' => $company->name]) }}
    </p>
  </div>
</body>
</html>
