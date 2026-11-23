@props(['disponible' => true])

{{-- Botón "Nuevo restaurante" que se bloquea cuando el plan llegó a su límite. --}}
@if ($disponible)
    <flux:button variant="primary" icon="plus" :href="route('negocio.restaurantes.crear')" wire:navigate data-tour="nuevo-restaurante">
        Nuevo restaurante
    </flux:button>
@else
    <flux:tooltip content="Llegaste al límite de restaurantes de tu plan">
        <div data-tour="nuevo-restaurante">
            <flux:button variant="primary" icon="lock-closed" disabled>Nuevo restaurante</flux:button>
        </div>
    </flux:tooltip>
@endif
