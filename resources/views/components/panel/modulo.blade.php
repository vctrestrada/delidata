@props([
    'titulo',
    'descripcion',
    'icono' => 'squares-2x2',
    'entrega' => null,
])

<div {{ $attributes->class('flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900') }}>
    <div class="flex items-start justify-between gap-3">
        <div class="flex size-10 items-center justify-center rounded-lg border border-zinc-200 dark:border-zinc-700">
            <flux:icon :name="$icono" variant="outline" class="size-5 text-zinc-700 dark:text-zinc-300" />
        </div>

        @if ($entrega)
            <flux:badge size="sm" color="zinc">Entrega {{ $entrega }}</flux:badge>
        @endif
    </div>

    <div class="space-y-1">
        <flux:heading size="lg">{{ $titulo }}</flux:heading>
        <flux:text class="text-sm">{{ $descripcion }}</flux:text>
    </div>
</div>
