@php $s = $dossier->stats; $appUrl = rtrim(config('app.url'), '/'); @endphp
<!DOCTYPE html>
<html lang="nl">
<head><meta charset="UTF-8"><title>Merkgebruik-dossier {{ $dossier->month }}</title></head>
<body style="margin:0;background:#FAFAF9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#1C1917;">
  <div style="max-width:560px;margin:0 auto;padding:40px 16px;">
    <div style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(28,25,23,.08);">
      <div style="background:linear-gradient(135deg,#E8231F 0%,#B81814 100%);padding:28px 36px;color:#fff;">
        <div style="display:flex;align-items:center;gap:10px;font-size:20px;font-weight:700;"><img src="{{ $appUrl }}/images/easyinvoice-favicon-180.png" style="width:34px;height:34px;border:0;" alt="EasyInvoice"><span>EasyInvoice</span></div>
        <div style="font-size:13px;opacity:.9;margin-top:6px;">Merkgebruik-dossier · {{ $dossier->month }}</div>
      </div>
      <div style="padding:30px 36px 26px;font-size:15px;line-height:1.6;color:#44403C;">
        <h1 style="font-size:21px;font-weight:600;margin:0 0 10px;color:#1C1917;">Bewijs van normaal gebruik — {{ $dossier->month }}</h1>
        <p style="margin:0 0 16px;">Automatisch opgesteld op {{ $dossier->generated_at->translatedFormat('j F Y, H:i') }}. Bewaar deze mail: de bijlagen zijn het dossier, het manifest bevat de SHA-256-hashes.</p>
        <table style="width:100%;border-collapse:collapse;font-size:14px;background:#F5F5F4;border:1px solid #E7E5E4;border-radius:10px;margin:0 0 18px;">
          <tr><td style="padding:8px 14px;color:#78716C;">Administraties (excl. demo)</td><td style="padding:8px 14px;font-weight:600;text-align:right;">{{ $s['administraties_totaal'] }} <span style="color:#78716C;font-weight:400;">(+{{ $s['administraties_nieuw'] }})</span></td></tr>
          <tr><td style="padding:8px 14px;color:#78716C;border-top:1px solid #EBE9E6;">Facturen verstuurd</td><td style="padding:8px 14px;font-weight:600;text-align:right;border-top:1px solid #EBE9E6;">{{ $s['facturen_verstuurd'] }}</td></tr>
          <tr><td style="padding:8px 14px;color:#78716C;border-top:1px solid #EBE9E6;">Offertes verstuurd</td><td style="padding:8px 14px;font-weight:600;text-align:right;border-top:1px solid #EBE9E6;">{{ $s['offertes_verstuurd'] }}</td></tr>
          <tr><td style="padding:8px 14px;color:#78716C;border-top:1px solid #EBE9E6;">Mails aan derden met merkvermelding</td><td style="padding:8px 14px;font-weight:600;text-align:right;border-top:1px solid #EBE9E6;">{{ $s['documentmails_met_merk'] }}</td></tr>
          @if(($s['bezoekers_website'] ?? null) !== null)
          <tr><td style="padding:8px 14px;color:#78716C;border-top:1px solid #EBE9E6;">Websitebezoekers</td><td style="padding:8px 14px;font-weight:600;text-align:right;border-top:1px solid #EBE9E6;">{{ $s['bezoekers_website'] }}</td></tr>
          @endif
        </table>
        <p style="margin:0 0 6px;font-size:13px;color:#78716C;">Bijlagen: gebruiksrapport, factuurexport, homepage met logo{{ isset($s['voorbeeldfactuur']) ? ', voorbeeldmail en -factuur ' . $s['voorbeeldfactuur'] : '' }}, manifest.</p>
        <p style="margin:18px 0 0;"><a href="{{ $appUrl }}/merkbewaking" style="display:inline-block;background:#E8231F;color:#fff;text-decoration:none;font-weight:600;padding:12px 22px;border-radius:8px;">Open Merkbewaking</a></p>
      </div>
    </div>
    <p style="text-align:center;color:#A8A29E;font-size:11px;margin:14px 0 0;">EasyInvoice · interne eigenaarsmail</p>
  </div>
</body>
</html>
