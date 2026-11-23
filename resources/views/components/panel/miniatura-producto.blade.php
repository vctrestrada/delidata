@props(['producto', 'tamano' => 'size-11'])

@if ($url = $producto->urlImagen())
    <img src="{{ $url }}" alt="{{ $producto->nombre }}" {{ $attributes->class([$tamano, 'shrink-0 rounded-lg object-cover']) }}>
@else
    <div {{ $attributes->class([$tamano, 'flex shrink-0 items-center justify-center rounded-lg bg-orange-50 text-sm font-semibold text-orange-600']) }}>
        {{ $producto->iniciales() }}
    </div>
@endif
