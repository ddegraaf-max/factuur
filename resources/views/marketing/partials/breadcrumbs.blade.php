{{-- Kruimelpad: zichtbaar én als BreadcrumbList voor zoekmachines.
     $crumbs = [[label, url|null], ...]; de laatste is de huidige pagina. Home komt er automatisch voor. --}}
@php
  $all = array_merge([['Home', url('/')]], $crumbs);
  $items = [];
  foreach ($all as $i => [$label, $href]) {
      $item = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $label];
      if ($href) {
          $item['item'] = $href;
      }
      $items[] = $item;
  }
@endphp
<div class="breadcrumb" style="text-align:center;">
  @foreach ($crumbs as $i => [$label, $href])
    @if ($i > 0)&nbsp;›&nbsp;@endif
    @if ($href)<a href="{{ $href }}">{{ $label }}</a>@else{{ $label }}@endif
  @endforeach
</div>
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
