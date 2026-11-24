{{--
    Interruptor para acciones inmediatas (activar, desactivar, agotado…).

    A diferencia de <flux:switch>, NO cambia de posición en el navegador:
    solo refleja lo que dice el servidor. Así, si la persona cancela la
    confirmación (wire:confirm), el interruptor se queda exactamente igual.

    Uso:
    <x-panel.interruptor :activo="$x->activo" wire:click="alternar({{ $x->id }})"
        wire:confirm="¿Seguro?" accesible="Activar o desactivar X" />

    Con texto a un lado:
    <x-panel.interruptor ... etiqueta="Negocio activo" descripcion="..." />
--}}
@props([
    'activo' => false,
    'etiqueta' => null,
    'descripcion' => null,
    'accesible' => null,
])

<div class="flex items-start justify-between gap-4">
    @if ($etiqueta)
        <div class="min-w-0">
            <div class="text-sm font-medium text-zinc-800">{{ $etiqueta }}</div>
            @if ($descripcion)
                <div class="mt-0.5 text-sm text-zinc-500">{{ $descripcion }}</div>
            @endif
        </div>
    @endif

    <button
        type="button"
        role="switch"
        aria-checked="{{ $activo ? 'true' : 'false' }}"
        aria-label="{{ $accesible ?? $etiqueta }}"
        wire:loading.attr="disabled"
        {{ $attributes->class([
            'relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition-colors duration-200',
            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2',
            'disabled:cursor-wait disabled:opacity-60',
            'bg-orange-500 hover:bg-orange-600' => $activo,
            'bg-zinc-200 hover:bg-zinc-300' => ! $activo,
        ]) }}
    >
        <span @class([
            'pointer-events-none inline-block size-4 rounded-full bg-white shadow-sm ring-1 ring-black/5 transition-transform duration-200',
            'translate-x-[18px]' => $activo,
            'translate-x-0.5' => ! $activo,
        ])></span>
    </button>
</div>
