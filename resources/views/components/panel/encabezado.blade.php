@props([
    'titulo',
    'descripcion' => null,
    'regresar' => null,
])

<div class="flex flex-col gap-4 border-b border-zinc-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
    <div class="space-y-1">
        @if ($regresar)
            <flux:link :href="$regresar" wire:navigate variant="subtle" class="inline-flex items-center gap-1 text-sm">
                <flux:icon name="arrow-left" variant="micro" /> Regresar
            </flux:link>
        @else
            <flux:text class="text-xs font-medium uppercase tracking-widest text-orange-600">
                {{ now()->locale(app()->getLocale())->translatedFormat('l j \d\e F, Y') }}
            </flux:text>
        @endif
        <flux:heading size="xl" level="1">{{ $titulo }}</flux:heading>
        @if ($descripcion)
            <flux:text>{{ $descripcion }}</flux:text>
        @endif
    </div>

    @isset($acciones)
        <div class="flex flex-wrap items-center gap-2">
            {{ $acciones }}
        </div>
    @endisset
</div>
