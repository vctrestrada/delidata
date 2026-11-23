{{-- Botón para repetir el recorrido guiado de la pantalla actual. --}}
<flux:button
    size="sm"
    variant="ghost"
    icon="academic-cap"
    data-recorrido-iniciar
    {{ $attributes }}
>
    {{ $slot->isEmpty() ? 'Ver recorrido' : $slot }}
</flux:button>
