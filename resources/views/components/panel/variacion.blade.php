@props(['valor', 'inverso' => false])

{{-- Muestra "▲ 12% vs periodo anterior". Con "inverso", subir es malo (p. ej. gastos). --}}
@if ($valor !== null)
    @php($bueno = $inverso ? $valor <= 0 : $valor >= 0)
    <span {{ $attributes->class(['inline-flex items-center gap-0.5 text-xs font-medium', 'text-green-600' => $bueno, 'text-red-600' => ! $bueno]) }}>
        <flux:icon :name="$valor >= 0 ? 'arrow-trending-up' : 'arrow-trending-down'" variant="micro" />
        {{ $valor > 0 ? '+' : '' }}{{ $valor }}%
        <span class="font-normal text-zinc-400">vs periodo anterior</span>
    </span>
@else
    <span {{ $attributes->class(['text-xs text-zinc-400']) }}>Sin datos del periodo anterior</span>
@endif
