<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
@auth
    @php
        $rolTiempoReal = auth()->user()->rolPrincipal();
        $canalTiempoReal = match ($rolTiempoReal) {
            \App\Enums\Rol::Dueno => 'negocio.'.auth()->user()->negocio_id,
            \App\Enums\Rol::Encargado, \App\Enums\Rol::Vendedor => auth()->user()->restaurante_id ? 'restaurante.'.auth()->user()->restaurante_id : null,
            default => null,
        };
    @endphp
    <meta name="delidata-tiempo-real" content="{{ json_encode([
        'canal' => $canalTiempoReal,
        'notificar' => in_array($rolTiempoReal, [\App\Enums\Rol::Dueno, \App\Enums\Rol::Encargado], true),
    ]) }}" />
@endauth

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
{{-- Delidata usa solo tema claro (blanco y naranja), por eso no se incluye @fluxAppearance --}}
