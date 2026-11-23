@props([
    'titulo',
    'descripcion' => null,
])

@php($rol = auth()->user()->rolPrincipal())

<div class="flex flex-col gap-3 border-b border-zinc-200 pb-6 sm:flex-row sm:items-end sm:justify-between dark:border-zinc-700">
    <div class="space-y-1">
        <flux:text class="text-xs font-medium uppercase tracking-widest">
            {{ now()->locale(app()->getLocale())->translatedFormat('l j \d\e F, Y') }}
        </flux:text>
        <flux:heading size="xl" level="1">{{ $titulo }}</flux:heading>
        @if ($descripcion)
            <flux:text>{{ $descripcion }}</flux:text>
        @endif
    </div>

    @if ($rol)
        <flux:badge size="sm" color="zinc" icon="shield-check">{{ $rol->etiqueta() }}</flux:badge>
    @endif
</div>
