{{--
    Recorrido guiado de una pantalla (Driver.js).

    Uso:
    <x-recorrido clave="admin.restaurantes" :pasos="[
        ['elemento' => '[data-tour=buscar]', 'titulo' => 'Buscar', 'descripcion' => '...'],
        ['titulo' => 'Paso sin elemento', 'descripcion' => 'Se muestra al centro de la pantalla'],
    ]" />

    Cada paso acepta: elemento (selector CSS), titulo, descripcion y lado (top|bottom|left|right).
--}}
@props([
    'clave',
    'pasos' => [],
])

<div
    hidden
    data-recorrido="{{ $clave }}"
    data-auto="{{ auth()->user()?->haVistoRecorrido($clave) ? '0' : '1' }}"
    wire:key="recorrido-{{ $clave }}"
>
    <script type="application/json">@json(array_values($pasos))</script>
</div>
