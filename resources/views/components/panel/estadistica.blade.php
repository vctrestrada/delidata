@props([
    'titulo',
    'valor',
    'detalle' => null,
    'icono' => 'chart-bar',
    'destacado' => false,
])

<div {{ $attributes->class([
    'tarjeta-interactiva flex items-start justify-between gap-4 rounded-xl border p-5',
    'border-orange-200 bg-orange-50' => $destacado,
    'border-zinc-200 bg-white' => ! $destacado,
]) }}>
    <div class="space-y-1">
        <flux:text class="text-sm">{{ $titulo }}</flux:text>
        <div class="text-3xl font-semibold tracking-tight text-zinc-900 tabular-nums" data-contador>{{ $valor }}</div>
        @if ($detalle)
            <flux:text class="text-xs">{{ $detalle }}</flux:text>
        @endif
    </div>

    <div @class([
        'flex size-10 items-center justify-center rounded-lg',
        'bg-orange-500 text-white' => $destacado,
        'bg-orange-50 text-orange-600' => ! $destacado,
    ])>
        <flux:icon :name="$icono" variant="outline" class="size-5" />
    </div>
</div>
