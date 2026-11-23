<div class="flex flex-col gap-6">
    <x-panel.encabezado titulo="Mis ventas" descripcion="Lo que has cobrado. Útil para cuadrar tu caja al final del turno.">
        <x-slot:acciones>
            <x-recorrido.boton />
            <flux:button variant="primary" icon="shopping-cart" :href="route('ventas.inicio')" wire:navigate>Punto de venta</flux:button>
        </x-slot:acciones>
    </x-panel.encabezado>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end" data-tour="fecha">
        <div class="sm:w-56">
            <flux:input type="date" wire:model.live="fecha" label="Día" :max="today()->toDateString()" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3" data-tour="resumen">
        <x-panel.estadistica titulo="Total cobrado" :valor="'$'.number_format($this->resumen['total'], 2)" icono="currency-dollar" destacado />
        <x-panel.estadistica titulo="Ventas" :valor="$this->resumen['ventas']" icono="receipt-percent"
            :detalle="$this->resumen['canceladas'] ? $this->resumen['canceladas'].' canceladas (no cuentan)' : null" />
        <x-panel.estadistica titulo="Efectivo en caja" :valor="'$'.number_format($this->resumen['efectivo'], 2)" icono="banknotes"
            detalle="Lo que debes entregar en efectivo" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white" data-tour="lista">
        @forelse ($this->ventas as $venta)
            <div wire:key="v-{{ $venta->id }}" class="border-b border-zinc-100 last:border-0">
                <button type="button" wire:click="alternar({{ $venta->id }})" class="flex w-full items-center gap-4 px-5 py-3 text-left hover:bg-orange-50/40">
                    <div class="w-16 text-sm font-semibold text-zinc-900">#{{ $venta->folioFormateado() }}</div>
                    <div class="w-14 text-sm text-zinc-500">{{ $venta->created_at->format('H:i') }}</div>
                    <div class="flex-1 truncate text-sm text-zinc-600">{{ $venta->detalles->map(fn ($d) => $d->cantidad.' '.$d->nombre)->join(', ') }}</div>
                    <flux:badge size="sm" color="zinc" :icon="$venta->metodo_pago->icono()">{{ $venta->metodo_pago->etiqueta() }}</flux:badge>
                    <div @class(['w-24 text-right font-semibold', 'text-zinc-900' => ! $venta->estaCancelada(), 'text-zinc-400 line-through' => $venta->estaCancelada()])>
                        ${{ number_format($venta->total, 2) }}
                    </div>
                    <flux:icon :name="$abiertaId === $venta->id ? 'chevron-up' : 'chevron-down'" variant="micro" class="text-zinc-400" />
                </button>

                @if ($abiertaId === $venta->id)
                    <div class="space-y-3 bg-zinc-50 px-5 py-4 text-sm">
                        @if ($venta->estaCancelada())
                            <flux:callout icon="x-circle" color="red" :heading="'Cancelada: '.$venta->motivo_cancelacion" />
                        @endif
                        <table class="w-full">
                            @foreach ($venta->detalles as $detalle)
                                <tr>
                                    <td class="py-0.5 text-zinc-700">{{ $detalle->cantidad }} × {{ $detalle->nombre }}</td>
                                    <td class="py-0.5 text-right text-zinc-900">${{ number_format($detalle->importe, 2) }}</td>
                                </tr>
                            @endforeach
                        </table>
                        <div class="flex justify-end">
                            <flux:button size="sm" icon="printer" :href="route('tickets.ver', $venta)" target="_blank">Ver ticket</flux:button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="flex flex-col items-center gap-2 px-5 py-12 text-center">
                <flux:icon name="receipt-percent" variant="outline" class="size-8 text-orange-500" />
                <flux:text>No registraste ventas este día.</flux:text>
            </div>
        @endforelse
    </div>

    <x-recorrido clave="ventas.historial" :pasos="[
        ['titulo' => 'Tus ventas', 'descripcion' => 'Aquí revisas todo lo que cobraste en un día.'],
        ['elemento' => '[data-tour=fecha]', 'titulo' => 'Elige el día', 'descripcion' => 'Por defecto ves las ventas de hoy. Cambia la fecha para consultar otro día.'],
        ['elemento' => '[data-tour=resumen]', 'titulo' => 'Resumen para tu corte', 'descripcion' => '«Efectivo en caja» es lo que debes entregar al cerrar tu turno. Las ventas canceladas no cuentan.'],
        ['elemento' => '[data-tour=lista]', 'titulo' => 'Detalle de cada venta', 'descripcion' => 'Toca una venta para ver sus productos y volver a imprimir el ticket. Si te equivocaste en una venta, pide al encargado que la cancele.', 'lado' => 'top'],
    ]" />
</div>
