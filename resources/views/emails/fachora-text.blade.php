FACHORA — {{ $eyebrow }}

{{ $title }}

@foreach($paragraphs as $paragraph)
{{ $paragraph }}

@endforeach
@if(!empty($quote))
> {{ str_replace("\n", "\n> ", $quote) }}

@endif
@foreach($facts as $k => $v)
{{ $k }}: {{ $v }}
@endforeach
@if($button && !empty($button['url']))

{{ $button['label'] }}: {{ $button['url'] }}
@endif
@if(!empty($footnote))

{{ $footnote }}
@endif

--
Fachora · {{ brand('domain') }} · {{ brand('email') }}
