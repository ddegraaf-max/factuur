{{-- Lijn-icoon als inline SVG voor marketingpagina's van alle merken; de set staat in fachora/partials/icon.blade.php.
     Gebruik: @include('partials.icon', ['name' => 'invoice', 'size' => 24]) --}}
@include('fachora.partials.icon', ['name' => $name ?? 'tools', 'size' => $size ?? 24])
