<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="utf-8">
<title>Wykup wierzytelności — faktura {{ $invoice->number }}</title>
</head>
<body style="margin:0;padding:24px;background:#F4F2EE;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#2A2724;">
  <div style="max-width:640px;margin:0 auto;background:#fff;border-radius:14px;padding:28px 32px;border:1px solid #D6D3CE;">
    <div style="display:flex;align-items:center;gap:10px;font-size:18px;font-weight:700;color:#132F49;">
      <img src="{{ \App\Support\Brand::asset('email_mark') }}" alt="{{ brand('name') }}" style="width:30px;height:30px;border-radius:7px;vertical-align:middle;margin-right:8px;">{{ brand('name') }} · wniosek o wykup wierzytelności
    </div>

    <p style="margin:22px 0 6px;font-size:15px;line-height:1.6;">Dzień dobry,</p>
    <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
      firma <strong>{{ $company->name }}</strong> (NIP {{ $company->vat_number ?: '—' }}) chce sprzedać wierzytelność z faktury <strong>{{ $invoice->number }}</strong>.
      Prosimy o ofertę wykupu (cesja) lub kontakt w celu ustalenia warunków.
    </p>

    <table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:18px;">
      <tr><td style="padding:7px 0;color:#8A8681;border-top:1px solid #E4E0D9;">Dłużnik</td><td style="padding:7px 0;border-top:1px solid #E4E0D9;text-align:right;"><strong>{{ $invoice->customer_name }}</strong>@if($invoice->customer_vat_number) · NIP {{ $invoice->customer_vat_number }}@endif</td></tr>
      <tr><td style="padding:7px 0;color:#8A8681;border-top:1px solid #E4E0D9;">Faktura</td><td style="padding:7px 0;border-top:1px solid #E4E0D9;text-align:right;">{{ $invoice->number }} z dnia {{ $invoice->invoice_date?->format('d.m.Y') }}</td></tr>
      <tr><td style="padding:7px 0;color:#8A8681;border-top:1px solid #E4E0D9;">Termin płatności</td><td style="padding:7px 0;border-top:1px solid #E4E0D9;text-align:right;">{{ $invoice->due_date?->format('d.m.Y') }} ({{ $claim['days'] }} dni po terminie)</td></tr>
      <tr><td style="padding:7px 0;color:#8A8681;border-top:1px solid #E4E0D9;">Należność główna</td><td style="padding:7px 0;border-top:1px solid #E4E0D9;text-align:right;"><strong>{{ money($claim['principal']) }}</strong></td></tr>
      <tr><td style="padding:7px 0;color:#8A8681;border-top:1px solid #E4E0D9;">Odsetki ustawowe ({{ rtrim(rtrim(number_format($claim['rate'] * 100, 2, ',', ''), '0'), ',') }}% rocznie)</td><td style="padding:7px 0;border-top:1px solid #E4E0D9;text-align:right;">{{ money($claim['interest']) }}</td></tr>
      <tr><td style="padding:7px 0;color:#8A8681;border-top:1px solid #E4E0D9;">Rekompensata (art. 10 — {{ $claim['compensation_eur'] }} EUR{{ ! empty($claim['eur_pln_date']) ? ', kurs NBP z ' . \Carbon\Carbon::parse($claim['eur_pln_date'])->format('d.m.Y') : '' }})</td><td style="padding:7px 0;border-top:1px solid #E4E0D9;text-align:right;">{{ money($claim['compensation']) }} ({{ $claim['compensation_eur'] }} EUR)</td></tr>
      <tr><td style="padding:9px 0;border-top:2px solid #132F49;font-weight:700;">Razem</td><td style="padding:9px 0;border-top:2px solid #132F49;text-align:right;font-weight:700;">{{ money($claim['total']) }}</td></tr>
    </table>

    @if($note)
    <p style="margin:0 0 18px;font-size:14px;line-height:1.6;background:#FBF1E7;border-radius:10px;padding:12px 14px;"><strong>Uwagi wierzyciela:</strong><br>{!! nl2br(e($note)) !!}</p>
    @endif

    <p style="margin:0 0 6px;font-size:14px;line-height:1.6;"><strong>Kontakt:</strong> {{ $user->name }} · <a href="mailto:{{ $user->email }}" style="color:#1C4E7A;">{{ $user->email }}</a>@if($company->phone) · {{ $company->phone }}@endif</p>
    <p style="margin:0;font-size:12px;color:#8A8681;">Faktura w PDF jest dostępna w {{ brand('name') }}; wierzyciel może ją przesłać na życzenie. Wysłano przez {{ brand('name') }} w imieniu {{ $company->name }}.</p>
  </div>
</body>
</html>
