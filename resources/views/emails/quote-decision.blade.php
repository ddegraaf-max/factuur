<!DOCTYPE html>
<html lang="nl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f5f5f4;font-family:Arial,Helvetica,sans-serif;color:#1c1917;">
  <div style="max-width:600px;margin:0 auto;padding:24px;">
    <div style="background:#fff;border:1px solid #e7e5e4;border-radius:12px;padding:26px;">
      @if($accepted)
        <h1 style="font-size:19px;margin:0 0 14px;">🎉 Offerte {{ $quote->number }} is ondertekend</h1>
        <p style="font-size:14px;line-height:1.7;margin:0 0 14px;">
          <strong>{{ $quote->signed_name }}</strong> ({{ $quote->signed_email }}) heeft offerte
          <strong>{{ $quote->number }}</strong> voor <strong>{{ $quote->customer_name }}</strong>
          digitaal ondertekend op {{ $quote->signed_at?->translatedFormat('j F Y \o\m H:i') }}.
        </p>
        <p style="font-size:14px;line-height:1.7;margin:0 0 14px;">
          Totaalbedrag: <strong>€ {{ number_format((float) $quote->total, 2, ',', '.') }}</strong> incl. btw.
          De handtekening en het bewijsdossier (naam, e-mailadres, tijdstip en IP-adres) staan bij de offerte in EasyInvoice.
        </p>
        <p style="font-size:14px;line-height:1.7;margin:0;">
          Tip: zet de offerte met één klik om naar een conceptfactuur via de offertepagina.
        </p>
      @else
        <h1 style="font-size:19px;margin:0 0 14px;">Offerte {{ $quote->number }} is afgewezen</h1>
        <p style="font-size:14px;line-height:1.7;margin:0 0 14px;">
          {{ $quote->customer_name }} ({{ $quote->signed_email }}) heeft offerte
          <strong>{{ $quote->number }}</strong> in het portaal afgewezen.
        </p>
        @if($quote->decline_reason)
          <p style="font-size:14px;line-height:1.7;margin:0 0 14px;background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:12px 16px;">
            <strong>Toelichting van de klant:</strong><br>{{ $quote->decline_reason }}
          </p>
        @endif
        <p style="font-size:14px;line-height:1.7;margin:0;">
          Wellicht is een aangepast voorstel op zijn plaats — je kunt de offerte bewerken en opnieuw versturen.
        </p>
      @endif
      <p style="margin:20px 0 0;">
        <a href="{{ route('quotes.show', $quote->id) }}"
           style="display:inline-block;padding:11px 20px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;background:#1c1917;">
          Open de offerte in EasyInvoice →
        </a>
      </p>
    </div>
    <p style="text-align:center;color:#a8a29e;font-size:11px;margin:14px 0 0;">EasyInvoice</p>
  </div>
</body>
</html>
