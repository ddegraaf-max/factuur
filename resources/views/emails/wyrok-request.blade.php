<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="utf-8">
<title>Skup wyroku — {{ $data['sygnatura'] ?? '' }}</title>
</head>
<body style="margin:0;padding:24px;background:#F4F2EE;font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#2A2724;">
  <div style="max-width:640px;margin:0 auto;background:#fff;border-radius:14px;padding:28px 32px;border:1px solid #D6D3CE;">
    <div style="font-size:18px;font-weight:700;color:#132F49;">
      <img src="{{ \App\Support\Brand::asset('email_mark') }}" alt="{{ brand('name') }}" style="width:30px;height:30px;border-radius:7px;vertical-align:middle;margin-right:8px;">{{ brand('name') }} · Skup starych wyroków
    </div>

    <p style="margin:22px 0 18px;font-size:15px;line-height:1.6;">
      Dzień dobry,<br>
      nowe zgłoszenie tytułu wykonawczego do wykupu{{ $company ? ' od klienta ' . $company->name : ' przez formularz na stronie' }}.
    </p>

    @php
      $row = fn ($label, $value) => $value
          ? '<tr><td style="padding:7px 0;color:#8A8681;border-top:1px solid #E4E0D9;">' . e($label) . '</td><td style="padding:7px 0;border-top:1px solid #E4E0D9;text-align:right;"><strong>' . e($value) . '</strong></td></tr>'
          : '';
    @endphp
    <table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:18px;">
      {!! $row('Sygnatura akt', $data['sygnatura'] ?? null) !!}
      {!! $row('Sąd', $data['sad'] ?? null) !!}
      {!! $row('Data wyroku', $data['data_wyroku'] ?? null) !!}
      {!! $row('Kwota nominalna', ($data['kwota'] ?? null) ? $data['kwota'] . ' zł' : null) !!}
      {!! $row('Dłużnik', $data['dluznik'] ?? null) !!}
      {!! $row('NIP dłużnika', $data['dluznik_nip'] ?? null) !!}
      {!! $row('Forma prawna dłużnika', $formaLabels[$data['forma'] ?? ''] ?? null) !!}
      {!! $row('Wcześniejsza egzekucja', $egzekucjaLabels[$data['egzekucja'] ?? ''] ?? null) !!}
      {!! $row('Rok ostatniej egzekucji', $data['egzekucja_rok'] ?? null) !!}
    </table>

    @if(! empty($data['uwagi']))
    <p style="margin:0 0 18px;font-size:14px;line-height:1.6;background:#FBF1E7;border-radius:10px;padding:12px 14px;"><strong>Uwagi zgłaszającego:</strong><br>{!! nl2br(e($data['uwagi'])) !!}</p>
    @endif

    <p style="margin:0 0 6px;font-size:14px;line-height:1.6;">
      <strong>Kontakt:</strong>
      @if($user)
        {{ $user->name }} · <a href="mailto:{{ $user->email }}" style="color:#1C4E7A;">{{ $user->email }}</a>@if($company?->phone) · {{ $company->phone }}@endif
        @if($company) <br><strong>Firma:</strong> {{ $company->name }}@if($company->vat_number) · NIP {{ $company->vat_number }}@endif @endif
      @else
        {{ $data['name'] ?? '' }} · <a href="mailto:{{ $data['email'] ?? '' }}" style="color:#1C4E7A;">{{ $data['email'] ?? '' }}</a>@if(! empty($data['phone'])) · {{ $data['phone'] }}@endif
        @if(! empty($data['firm'])) <br><strong>Firma:</strong> {{ $data['firm'] }}@endif
      @endif
    </p>

    <p style="margin:12px 0 0;font-size:12px;color:#8A8681;">
      Uwaga przy wycenie: powód umorzenia poprzedniej egzekucji decyduje o biegu przedawnienia (bezskuteczność = termin biegnie od nowa; bezczynność wierzyciela = przerwanie upada). Odsetki przedawniają się po 3 latach. Wysłano przez {{ brand('name') }}.
    </p>
  </div>
</body>
</html>
