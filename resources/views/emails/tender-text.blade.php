@php
    // Dezelfde mail als platte tekst. Alles staat in één blok, zodat er geen
    // lege regels of inspringingen van het sjabloon in de tekst belanden.
    $name = $subcontractor?->contact_name ?: $subcontractor?->name;
    $deadline = $round->deadline?->translatedFormat('j F Y');
    $asks = in_array($kind, ['request', 'reminder', 'questions'], true);
    $plain = fn ($blocks) => collect($blocks)->map(fn ($block) => $block['type'] === 'ul'
        ? collect($block['lines'])->map(fn ($line) => '- ' . $line)->implode("\n")
        : implode("\n", $block['lines']))->implode("\n\n");

    $lines = [__('Beste :name,', ['name' => $name]), ''];

    $lines[] = match (true) {
        $kind === 'award' => __('Bedankt voor uw prijsopgave voor :package. Wij gunnen u de opdracht en nemen binnenkort contact met u op over de planning en de opdrachtbevestiging.', ['package' => $round->title]),
        $kind === 'reject' && filled($rejection) => $plain($rejection),
        $kind === 'reject' => __('Bedankt voor uw prijsopgave. Voor dit project hebben wij een andere partij gekozen. Wij houden u graag in beeld voor volgende projecten.'),
        $kind === 'revoke' => __('Helaas moeten wij de opdracht voor :package, die wij u op :date gunden, intrekken. Onze excuses voor het ongemak.', ['package' => $round->title, 'date' => $round->awarded_at?->translatedFormat('j F Y')])
            . (filled($note) ? "\n\n" . trim($note) : '')
            . "\n\n" . __('Uw prijsopgave blijft bij ons bekend; wij houden u graag in beeld voor volgende projecten. Vragen? Antwoord op deze mail.'),
        $kind === 'questions' => __('Bedankt voor uw prijsopgave voor :package. Om de offertes goed te kunnen vergelijken hebben wij nog een paar vragen:', ['package' => $round->title])
            . "\n\n" . collect($questions)->map(fn ($q, $i) => ($i + 1) . '. ' . $q)->implode("\n")
            . "\n\n" . __('Antwoorden kan door op deze mail te reageren, of via de knop hieronder: daar kunt u uw prijs, opmerkingen en offerte aanvullen.'),
        $kind === 'reminder' => __('Wij hebben nog geen reactie van u ontvangen op onze prijsaanvraag. Kunt u uiterlijk :deadline uw prijs en beschikbaarheid doorgeven? Dat kan in een minuut via de knop hieronder.', ['deadline' => $deadline]),
        $firstContact => __(':company zoekt een vakman voor onderstaand werk en vraagt u om een prijs en uw beschikbaarheid. Dit is een persoonlijke aanvraag, geen reclame. Reageren kan in een minuut via de knop hieronder.', ['company' => $company->name]),
        default => __(':company vraagt u om een prijs en uw beschikbaarheid voor onderstaand onderdeel van een project. Reageren kan in een minuut via de knop hieronder.', ['company' => $company->name]),
    };

    if ($kind !== 'reject') {
        $lines[] = '';
        foreach (array_filter([
            __('Onderdeel') => $round->title,
            __('Locatie') => $round->location,
            __('Gewenste start') => $startWeek ? __('week :week', ['week' => $startWeek]) : null,
            __('Reageren vóór') => $asks && $kind !== 'questions' ? $deadline : null,
        ]) as $label => $value) {
            $lines[] = $label . ': ' . $value;
        }
    }

    if ($asks && $kind !== 'questions' && $blocks) {
        array_push($lines, '', mb_strtoupper(__('Omschrijving')), $plain($blocks));
    }

    if ($files) {
        array_push($lines, '', mb_strtoupper(__('Bijlagen')));
        foreach ($files as $file) {
            $lines[] = '- ' . $file['name'] . ' (' . $file['size'] . '): ' . $file['url'];
        }
    }

    if ($kind === 'questions') {
        array_push($lines, '', __('Prijsopgave aanvullen') . ':', $url);
    } elseif ($asks) {
        array_push($lines, '', __('Prijs en beschikbaarheid doorgeven') . ':', $url, '',
            __('Liever uw eigen offerte sturen? Die kunt u op dezelfde pagina als PDF toevoegen. Antwoorden op deze mail kan ook.'),
            __('Geen tijd of past het niet? Laat het ons via dezelfde knop weten, dan sturen wij geen herinnering.'));
    }

    if ($asks && $firstContact) {
        array_push($lines, '', mb_strtoupper(__('Waarom krijgt u deze mail?')),
            __(':company kwam bij u uit omdat u dit werk in de regio doet. Wij willen u niets verkopen; wij willen u een opdracht geven. Deze mail is dus geen reclame en geen spam.', ['company' => $company->name]),
            __('Wij werken volledig digitaal: onze prijsaanvragen versturen wij met :brand. Achter de knop staat de aanvraag met de bijlagen en vult u uw prijs in. U maakt geen account aan, het kost niets en u zit nergens aan vast.', ['brand' => brand('name')]),
            $company->phone
                ? __('Twijfelt u, of overlegt u liever eerst? Bel ons op :phone.', ['phone' => $company->phone])
                : __('Twijfelt u, of overlegt u liever eerst? Antwoord op deze mail; uw bericht komt rechtstreeks bij ons.'));
    }

    $lines[] = '';
    if ($signature) {
        $lines[] = $signature;
    } else {
        array_push($lines, __('Met vriendelijke groet,'), $company->name, ...array_filter([
            $company->phone,
            $company->email,
            $company->website,
            $company->kvk_number ? market('registry.short') . ' ' . $company->kvk_number : null,
        ]));
    }

    array_push($lines, '', '-- ', __('doc.mail_sent_via', ['name' => $company->name, 'brand' => brand('name')]));
@endphp
{!! implode("\n", $lines) !!}
