@php
    $open = number_format((float) $invoice->total - (float) $invoice->paid_total, 2, ',', '.');
    $total = number_format((float) $invoice->total, 2, ',', '.');
    $paid = number_format((float) $invoice->paid_total, 2, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="nl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f5f5f4;font-family:Arial,Helvetica,sans-serif;color:#1c1917;">
  <div style="max-width:640px;margin:0 auto;padding:24px;">
    <div style="background:#fff;border:1px solid #e7e5e4;border-radius:12px;overflow:hidden;">
      <div style="background:#0f172a;color:#fff;padding:20px 24px;">
        <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#fcd34d;">Incasso-opdracht</div>
        <div style="font-size:20px;font-weight:700;margin-top:2px;">Dossier {{ $invoice->incasso_reference }}</div>
      </div>

      <div style="padding:22px 24px;">
        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
          Beste Armaere,<br><br>
          Hierbij dragen wij namens onze klant een achterstallige vordering ter incasso aan u over.
          De opdrachtgever heeft de opdracht bevestigd en gaat daarmee akkoord met overdracht aan
          {{ \App\Support\Market::incasso('partner_name') }}. Het volledige dossier vindt u hieronder en in de bijlagen.
        </p>

        <h3 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#78716c;margin:20px 0 8px;">Opdrachtgever (crediteur)</h3>
        <table style="width:100%;font-size:14px;line-height:1.6;border-collapse:collapse;">
          <tr><td style="padding:2px 0;"><strong>{{ $company->name }}</strong></td></tr>
          @if($company->address_line)<tr><td style="padding:2px 0;">{{ $company->address_line }}, {{ $company->postal_code }} {{ $company->city }}</td></tr>@endif
          <tr><td style="padding:2px 0;">KvK {{ $company->kvk_number }} · BTW {{ $company->vat_number }}</td></tr>
          @if($company->iban)<tr><td style="padding:2px 0;">IBAN {{ $company->iban }}</td></tr>@endif
          @if($company->email)<tr><td style="padding:2px 0;">{{ $company->email }}</td></tr>@endif
        </table>

        <h3 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#78716c;margin:20px 0 8px;">Debiteur</h3>
        <table style="width:100%;font-size:14px;line-height:1.6;border-collapse:collapse;">
          <tr><td style="padding:2px 0;"><strong>{{ $invoice->customer_name }}</strong></td></tr>
          @if($invoice->customer_address_line)<tr><td style="padding:2px 0;">{{ $invoice->customer_address_line }}, {{ $invoice->customer_postal_code }} {{ $invoice->customer_city }}</td></tr>@endif
          @if($invoice->customer_kvk_number)<tr><td style="padding:2px 0;">KvK {{ $invoice->customer_kvk_number }}</td></tr>@endif
          @if($invoice->customer_email)<tr><td style="padding:2px 0;">{{ $invoice->customer_email }}</td></tr>@endif
        </table>

        <h3 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#78716c;margin:20px 0 8px;">Vordering</h3>
        <table style="width:100%;font-size:14px;line-height:1.7;border-collapse:collapse;">
          <tr><td style="color:#78716c;">Factuurnummer</td><td style="text-align:right;font-weight:600;">{{ $invoice->number }}</td></tr>
          <tr><td style="color:#78716c;">Factuurdatum</td><td style="text-align:right;">{{ optional($invoice->invoice_date)->format('d-m-Y') }}</td></tr>
          <tr><td style="color:#78716c;">Vervaldatum</td><td style="text-align:right;">{{ optional($invoice->due_date)->format('d-m-Y') }}</td></tr>
          <tr><td style="color:#78716c;">Factuurbedrag</td><td style="text-align:right;">€ {{ $total }}</td></tr>
          <tr><td style="color:#78716c;">Reeds betaald</td><td style="text-align:right;">€ {{ $paid }}</td></tr>
          <tr><td style="border-top:2px solid #1c1917;padding-top:6px;font-weight:700;">Openstaand bedrag</td><td style="border-top:2px solid #1c1917;padding-top:6px;text-align:right;font-weight:700;">€ {{ $open }}</td></tr>
        </table>

        <h3 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#78716c;margin:20px 0 8px;">Verloop</h3>
        @if($invoice->reminderLogs->isEmpty() && $invoice->payments->isEmpty())
          <p style="font-size:13px;color:#78716c;margin:0;">Geen eerdere herinneringen/aanmaningen of betalingen geregistreerd.</p>
        @else
          <table style="width:100%;font-size:13px;line-height:1.6;border-collapse:collapse;">
            @foreach($invoice->reminderLogs as $log)
              <tr>
                <td style="padding:3px 0;color:#78716c;white-space:nowrap;">{{ optional($log->sent_at)->format('d-m-Y') }}</td>
                <td style="padding:3px 0;">{{ $log->type }} verstuurd @if($log->sent_to)aan {{ $log->sent_to }}@endif</td>
              </tr>
            @endforeach
            @foreach($invoice->payments as $p)
              <tr>
                <td style="padding:3px 0;color:#78716c;white-space:nowrap;">{{ optional($p->paid_on)->format('d-m-Y') }}</td>
                <td style="padding:3px 0;">Betaling ontvangen: € {{ number_format((float) $p->amount, 2, ',', '.') }}</td>
              </tr>
            @endforeach
          </table>
        @endif

        <h3 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#78716c;margin:20px 0 8px;">Bijlagen</h3>
        <p style="font-size:13px;color:#44403c;margin:0;line-height:1.6;">
          {{-- Geen @endif direct achter een woordteken ("dossier@endif"): Blade
               herkent die directive dan niet en de template parset niet meer. --}}
          @php
              $extra = $invoice->attachments->count();
          @endphp
          Factuur ({{ $invoice->number }}.pdf){{ $extra ? " en {$extra} meegestuurde bijlage(n) uit het originele dossier" : '' }} zijn als bijlage toegevoegd.
        </p>

        <p style="margin:22px 0 0;font-size:13px;color:#78716c;line-height:1.6;">
          Met vriendelijke groet,<br>
          {{ $company->name }} · via {{ brand('name') }}
        </p>
      </div>
    </div>
    <p style="text-align:center;color:#a8a29e;font-size:11px;margin:14px 0 0;">
      Automatisch gegenereerd door {{ brand('name') }} namens {{ $company->name }}.
    </p>
  </div>
</body>
</html>
