@php
    /*
     * Bedankmail na betaling. Bewust anders van toon dan de factuurmail en de
     * herinnering: geen betaalverzoek, wél een feestje. Tabellen en inline
     * stijlen zodat Gmail, Outlook en Apple Mail het allemaal netjes tonen.
     */
    $brand = $company->brand_color ?: '#E8231F';
    $logo = $company->logoBinary();
    $total = number_format((float) $invoice->total, 2, ',', '.');
    $paidOn = $payment?->paid_on ?? $invoice->paid_at;
    $paidOnLabel = $paidOn ? $paidOn->translatedFormat('j F Y') : null;
    $portalUrl = ! empty($preview)
        ? '#'
        : ($invoice->portal_token ? route('portal.invoice', $invoice->portal_token) : null);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('doc.mail_thanks_title') }}</title>
</head>
<body style="margin:0;background:#f5f5f4;font-family:Arial,Helvetica,sans-serif;color:#1c1917;">
  <div style="max-width:600px;margin:0 auto;padding:24px;">
    <div style="background:#fff;border:1px solid #e7e5e4;border-radius:12px;overflow:hidden;">

      {{-- Kop: logo of naam van de (handels)naam --}}
      <div style="padding:20px 24px;border-bottom:1px solid #e7e5e4;">
        {{-- Voorbeeld in de browser: render() zet ook een $message klaar, maar een
             cid:-bijlage kan een browser niet tonen — dus eerst op preview testen. --}}
        @if($logo && ! empty($preview))
          <img src="{{ $company->logo_data }}" alt="{{ $company->name }}" style="max-height:44px;max-width:220px;display:block;border:0;">
        @elseif($logo && isset($message))
          <img src="{{ $message->embedData($logo['data'], $logo['name'], $logo['mime']) }}"
               alt="{{ $company->name }}" style="max-height:44px;max-width:220px;display:block;border:0;">
        @else
          <div style="font-weight:700;font-size:18px;color:{{ $brand }};">{{ $company->name }}</div>
        @endif
      </div>

      {{-- Held: groot vinkje in de merkkleur --}}
      <div style="padding:34px 24px 6px;text-align:center;">
        <table role="presentation" align="center" cellpadding="0" cellspacing="0" style="margin:0 auto 18px;">
          <tr>
            <td style="width:68px;height:68px;border-radius:34px;background:{{ $brand }};text-align:center;vertical-align:middle;color:#ffffff;font-size:34px;line-height:68px;font-weight:700;font-family:Arial,Helvetica,sans-serif;">&#10003;</td>
          </tr>
        </table>
        <h1 style="margin:0;font-size:24px;line-height:1.3;letter-spacing:-0.01em;color:#1c1917;">{{ __('doc.mail_thanks_title') }}</h1>
      </div>

      <div style="padding:14px 24px 26px;font-size:14px;line-height:1.7;">
        @if(!empty($customBody))
          {{-- Eigen e-mailtekst (Instellingen → E-mailteksten): vervangt aanhef en intro. --}}
          <p style="margin:0 0 14px;">{!! nl2br(e($customBody)) !!}</p>
        @else
          <p style="margin:0 0 14px;">{{ __('doc.mail_greeting', ['name' => $invoice->customer_name]) }}</p>
          <p style="margin:0 0 14px;">{!! __('doc.mail_thanks_intro', ['number' => e($invoice->number)]) !!}</p>
        @endif

        {{-- Betaaloverzicht --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 22px;border:1px solid #e7e5e4;border-radius:10px;background:#fafaf9;">
          <tr>
            <td style="padding:14px 18px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13.5px;line-height:1.5;">
                <tr>
                  <td style="padding:6px 0;color:#78716c;">{{ __('doc.mail_thanks_invoice') }}</td>
                  <td style="padding:6px 0;text-align:right;font-weight:600;">{{ $invoice->number }}</td>
                </tr>
                <tr>
                  <td style="padding:6px 0;color:#78716c;border-top:1px solid #ebe9e6;">{{ __('doc.mail_thanks_amount') }}</td>
                  <td style="padding:6px 0;text-align:right;font-weight:700;font-size:15px;border-top:1px solid #ebe9e6;">
                    € {{ $total }}
                    <span style="display:inline-block;margin-left:6px;padding:2px 8px;border-radius:999px;background:#DCFCE7;color:#166534;font-size:11px;font-weight:700;letter-spacing:0.02em;vertical-align:middle;">{{ __('doc.mail_thanks_settled') }}</span>
                  </td>
                </tr>
                @if($paidOnLabel)
                <tr>
                  <td style="padding:6px 0;color:#78716c;border-top:1px solid #ebe9e6;">{{ __('doc.mail_thanks_received_on') }}</td>
                  <td style="padding:6px 0;text-align:right;font-weight:600;border-top:1px solid #ebe9e6;">{{ $paidOnLabel }}</td>
                </tr>
                @endif
                @if(!empty($methodLabel))
                <tr>
                  <td style="padding:6px 0;color:#78716c;border-top:1px solid #ebe9e6;">{{ __('doc.mail_thanks_method') }}</td>
                  <td style="padding:6px 0;text-align:right;font-weight:600;border-top:1px solid #ebe9e6;">{{ $methodLabel }}</td>
                </tr>
                @endif
              </table>
            </td>
          </tr>
        </table>

        {{-- Portaalknop: daar staat de factuur nu als "betaald", met de PDF --}}
        @if($portalUrl)
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 8px;">
            <tr>
              <td style="border-radius:8px;background:{{ $brand }};">
                <a href="{{ $portalUrl }}"
                   style="display:inline-block;padding:12px 22px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:8px;">
                  {{ __('doc.mail_view_invoice') }}&nbsp;&nbsp;→
                </a>
              </td>
            </tr>
          </table>
        @endif
        <p style="margin:6px 0 0;color:#78716c;font-size:12.5px;line-height:1.6;">
          {{ __('doc.mail_thanks_attachment') }}
        </p>

        {{-- Reviewverzoek: het beste moment is precies nu --}}
        @if(!empty($reviewUrl))
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0 4px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;">
            <tr>
              <td style="padding:16px 18px;">
                <div style="font-size:15px;font-weight:700;color:#78350F;margin:0 0 4px;">
                  <span style="color:#F59E0B;letter-spacing:1px;">&#9733;&#9733;&#9733;&#9733;&#9733;</span>&nbsp; {{ __('doc.mail_thanks_review_title') }}
                </div>
                <div style="font-size:13.5px;color:#92400E;line-height:1.6;margin:0 0 12px;">{{ __('doc.mail_thanks_review_text') }}</div>
                <a href="{{ $reviewUrl }}"
                   style="display:inline-block;padding:10px 18px;font-size:13.5px;font-weight:600;color:#78350F;background:#ffffff;border:1px solid #F59E0B;border-radius:8px;text-decoration:none;">
                  {{ __('doc.mail_thanks_review_cta') }}&nbsp;&nbsp;→
                </a>
              </td>
            </tr>
          </table>
        @endif

        <p style="margin:22px 0 0;">{{ __('doc.mail_regards') }}<br>{{ $company->name }}</p>
      </div>
    </div>
    <p style="text-align:center;color:#a8a29e;font-size:11px;margin:14px 0 0;">
      {{ __('doc.mail_sent_via', ['name' => $company->name]) }}
    </p>
  </div>
</body>
</html>
