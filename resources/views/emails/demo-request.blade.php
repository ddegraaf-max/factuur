<!DOCTYPE html>
<html lang="nl">
<head><meta charset="utf-8"><title>Demo-aanvraag {{ brand('name') }}</title></head>
<body style="margin:0;padding:24px;background:#f5f5f4;font-family:Inter,Arial,Helvetica,sans-serif;color:#1c1917;">
  <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;padding:28px;border:1px solid #e7e5e4;">
    <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#78716c;">{{ brand('name') }} · demo op aanvraag</p>
    <h1 style="margin:0 0 18px;font-size:20px;">{{ $demoRequest->displayName() }} wil de demo zien</h1>
    <table style="border-collapse:collapse;font-size:14px;width:100%;">
      @if($demoRequest->name)<tr><td style="padding:6px 0;color:#78716c;width:120px;">Naam</td><td style="padding:6px 0;">{{ $demoRequest->name }}</td></tr>@endif
      <tr><td style="padding:6px 0;color:#78716c;width:120px;">E-mail</td><td style="padding:6px 0;"><a href="mailto:{{ $demoRequest->email }}" style="color:{{ brand('color') }};">{{ $demoRequest->email }}</a></td></tr>
      @if($demoRequest->company)<tr><td style="padding:6px 0;color:#78716c;">Bedrijf</td><td style="padding:6px 0;">{{ $demoRequest->company }}</td></tr>@endif
      <tr><td style="padding:6px 0;color:#78716c;">Taal</td><td style="padding:6px 0;">{{ strtoupper((string) $demoRequest->locale) }}</td></tr>
      <tr><td style="padding:6px 0;color:#78716c;">Aangevraagd</td><td style="padding:6px 0;">{{ $demoRequest->created_at?->translatedFormat('j F Y H:i') }}@if($demoRequest->ip) · {{ $demoRequest->ip }}@endif</td></tr>
    </table>
    @if($demoRequest->message)
      <div style="margin-top:16px;padding:14px 16px;background:#fafaf9;border-radius:10px;font-size:15px;line-height:1.6;white-space:pre-line;">{{ $demoRequest->message }}</div>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0 8px;">
      <tr>
        <td style="border-radius:8px;background:{{ brand('color') }};">
          <a href="{{ $reviewUrl }}" style="display:inline-block;padding:13px 26px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">Beoordelen: goedkeuren of afwijzen&nbsp;&nbsp;→</a>
        </td>
      </tr>
    </table>

    <p style="margin:20px 0 0;font-size:13px;color:#78716c;line-height:1.6;">
      Zonder jouw goedkeuring start er geen demo. Keur je goed, dan krijgt de aanvrager een persoonlijke link die {{ \App\Models\DemoRequest::VALID_DAYS }} dagen werkt.
      Alle aanvragen staan ook in de app onder <a href="{{ $ownerUrl }}" style="color:{{ brand('color') }};">Eigenaar → Demo-aanvragen</a>. Beantwoord deze mail om de aanvrager direct te schrijven.
    </p>
  </div>
</body>
</html>
