@php
    // Dezelfde mail als platte tekst, in één blok.
    $name = $subcontractor?->contact_name ?: $subcontractor?->name;
    $start = $item->starts_on?->translatedFormat('l j F Y');
    $end = $item->ends_on?->translatedFormat('l j F Y');
    $proposed = $item->request_start?->translatedFormat('l j F Y');

    $lines = [__('Beste :name,', ['name' => $name]), ''];
    $lines[] = match ($kind) {
        'headsup' => __('Volgende week staat uw werk op project :project gepland. Wij rekenen op u vanaf :date. Klopt dat nog? Bevestig het met één klik, of laat het ons weten als er iets in de weg zit.', ['project' => $project->name, 'date' => $start]),
        'earlier' => __('Het werk op project :project loopt voor op schema. Daarom de vraag: kunt u eerder beginnen met :title, namelijk op :proposed in plaats van :date?', ['project' => $project->name, 'title' => $item->title, 'proposed' => $proposed, 'date' => $start]),
        default => __('Deze week begint uw werk op project :project: :date. Wij zien u dan graag op de locatie. Bevestig het met één klik, of laat het ons weten als er iets in de weg zit.', ['project' => $project->name, 'date' => $start]),
    };
    if ($kind === 'earlier' && $item->request_message) {
        array_push($lines, '', $item->request_message);
    }
    $lines[] = '';
    foreach (array_filter([
        __('Onderdeel') => $item->title,
        __('Project') => $project->name,
        __('Locatie') => $location,
        __('Geplande start') => $start,
        __('Gepland tot en met') => $end,
        __('Voorstel') => $kind === 'earlier' ? $proposed : null,
    ]) as $label => $value) {
        $lines[] = $label . ': ' . $value;
    }
    if ($item->notes) {
        array_push($lines, '', mb_strtoupper(__('Bijzonderheden')), $item->notes);
    }
    array_push($lines, '', ($kind === 'earlier' ? __('Antwoord geven') : __('Bevestigen of iets doorgeven')) . ':', $url, '',
        __('Antwoorden op deze mail kan ook; uw bericht komt rechtstreeks bij ons.'), '',
        __('Met vriendelijke groet,'), $company->name);
    foreach (array_filter([$company->phone, $company->email]) as $line) {
        $lines[] = $line;
    }
@endphp
{{ implode("\n", $lines) }}
