<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><title>{{ __('Nieuw bericht via je website') }}</title></head>
<body style="margin:0;padding:24px;background:#f5f5f4;font-family:Inter,Arial,Helvetica,sans-serif;color:#1c1917;">
  <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;padding:28px;border:1px solid #e7e5e4;">
    <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#78716c;">{{ __('Website van :name', ['name' => $company->publicName()]) }}</p>
    <h1 style="margin:0 0 18px;font-size:20px;">{{ __('Nieuw bericht van :name', ['name' => $lead->name]) }}</h1>
    <table style="border-collapse:collapse;font-size:14px;width:100%;">
      <tr><td style="padding:6px 0;color:#78716c;width:110px;">{{ __('Naam') }}</td><td style="padding:6px 0;">{{ $lead->name }}</td></tr>
      <tr><td style="padding:6px 0;color:#78716c;">{{ __('E-mail') }}</td><td style="padding:6px 0;"><a href="mailto:{{ $lead->email }}" style="color:{{ brand('color') }};">{{ $lead->email }}</a></td></tr>
      @if($lead->phone)<tr><td style="padding:6px 0;color:#78716c;">{{ __('Telefoon') }}</td><td style="padding:6px 0;">{{ $lead->phone }}</td></tr>@endif
    </table>
    <div style="margin-top:16px;padding:14px 16px;background:#fafaf9;border-radius:10px;font-size:15px;line-height:1.6;white-space:pre-line;">{{ $lead->message }}</div>
    <p style="margin:20px 0 0;font-size:13px;color:#78716c;">{{ __('Beantwoord deze mail om direct te reageren. Alle berichten staan ook onder Instellingen → Website in :brand.', ['brand' => brand('name')]) }}</p>
  </div>
</body>
</html>
