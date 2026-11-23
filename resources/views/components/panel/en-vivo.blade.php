{{-- Indicador del estado de la conexión en tiempo real (Reverb). --}}
<div
    x-data="{ estado: window.delidataConexion ?? 'connecting' }"
    x-on:delidata:conexion.window="estado = $event.detail"
    class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium"
    :class="estado === 'connected' ? 'border-green-200 bg-green-50 text-green-700' : 'border-zinc-200 bg-white text-zinc-500'"
    data-tour="en-vivo"
>
    <span class="relative flex size-2">
        <span x-show="estado === 'connected'" class="absolute inline-flex size-full animate-ping rounded-full bg-green-400 opacity-75"></span>
        <span class="relative inline-flex size-2 rounded-full" :class="estado === 'connected' ? 'bg-green-500' : 'bg-zinc-300'"></span>
    </span>
    <span x-text="estado === 'connected' ? 'En vivo' : (estado === 'connecting' ? 'Conectando…' : 'Sin conexión en vivo')"></span>
</div>
