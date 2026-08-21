{{--
    Statusstempel voor de factuur-PDF (dompdf).
    Geen afbeeldingen nodig — de kleuren zijn al met wit versneden,
    zodat 'opacity' (dat dompdf onbetrouwbaar rendert) niet nodig is.

    Plaats direct na <body> in de factuursjablonen:
        @include('pdf.partials.watermerk')
--}}
@php
    $wmRuw = strtolower(trim((string) (
        $watermarkStatus
        ?? ($invoice->watermark_status ?? null)
        ?? ($invoice->status ?? null)
        ?? ''
    )));

    // Zowel Engelse als Nederlandse statuswaarden.
    $wmMap = [
        'draft'        => 'concept',      'concept'      => 'concept',
        'paid'         => 'betaald',      'betaald'      => 'betaald',
        'overdue'      => 'vervallen',    'expired'      => 'vervallen',
        'vervallen'    => 'vervallen',
        'reminder'     => 'herinnering',  'reminded'     => 'herinnering',
        'herinnering'  => 'herinnering',
        'dunning'      => 'aanmaning',    'final_notice' => 'aanmaning',
        'aanmaning'    => 'aanmaning',
        'credit'       => 'gecrediteerd', 'credit_note'  => 'gecrediteerd',
        'credited'     => 'gecrediteerd', 'gecrediteerd' => 'gecrediteerd',
    ];

    // Tekst + kleur. De kleur is de merkkleur op 48% over wit.
    $wmStijlen = [
        'concept'      => ['CONCEPT',      '#B8BABD'],
        'betaald'      => ['BETAALD',      '#93D0B0'],
        'vervallen'    => ['VERVALLEN',    '#E2AA85'],
        'herinnering'  => ['HERINNERING',  '#FFBF90'],
        'aanmaning'    => ['AANMANING',    '#E39892'],
        'gecrediteerd' => ['GECREDITEERD', '#B8BABD'],
    ];

    // Geen stempel bij sent / verzonden / open: een gewone openstaande
    // factuur hoort schoon de deur uit te gaan.
    $wmSleutel = $wmMap[$wmRuw] ?? null;
    $wmAan     = $wmSleutel && (bool) config('watermerk.stempel', true);
    [$wmTekst, $wmKleur] = $wmAan ? $wmStijlen[$wmSleutel] : [null, null];
@endphp

@if ($wmAan)
    <style>
        /* Rechtsonder op elke pagina, als een echt gezet stempel. */
        .wm-vel {
            position: fixed;
            bottom: 10mm;
            right: 4mm;
            text-align: right;
            z-index: 900;
        }
        .wm-stempel {
            display: inline-block;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 22pt;
            font-weight: bold;
            letter-spacing: 2.5pt;
            padding: 4mm 7mm;
            border: 3px solid {{ $wmKleur }};
            border-radius: 5px;
            color: {{ $wmKleur }};
            transform: rotate(-12deg);
        }
    </style>

    <div class="wm-vel"><span class="wm-stempel">{{ $wmTekst }}</span></div>
@endif
