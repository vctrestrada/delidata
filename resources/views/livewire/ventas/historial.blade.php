@php($r = $this->resumen)

<div class="flex flex-col gap-6">
    <x-panel.encabezado
        titulo="Ventas"
        :descripcion="$this->esEncargado()
            ? 'Ventas de '.auth()->user()->restaurante?->nombre.'. Revisa el detalle o cancela una venta registrada por error.'
            : 'Ventas de todos tus restaurantes. Filtra por periodo, restaurante, vendedor o forma de pago.'"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
        </x-slot:acciones>
    </x-panel.encabezado>

    {{-- Filtros --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4" data-tour="filtros">
        <div class="flex flex-wrap gap-2">
            @foreach (['hoy' => 'Hoy', 'ayer' => 'Ayer', 'semana' => 'Esta semana', 'mes' => 'Este mes'] as $clave => $texto)
                <flux:button size="sm" variant="ghost" wire:click="periodo('{{ $clave }}')">{{ $texto }}</flux:button>
            @endforeach
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <flux:input type="date" wire:model.live="desde" label="Desde" size="sm" :max="today()->toDateString()" />
            <flux:input type="date" wire:model.live="hasta" label="Hasta" size="sm" :max="today()->toDateString()" />
            @unless ($this->esEncargado())
                <flux:select wire:model.live="restaurante" label="Restaurante" size="sm">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($this->restaurantesDisponibles() as $opcion)
                        <flux:select.option :value="$opcion->id">{{ $opcion->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endunless
            <flux:select wire:model.live="vendedor" label="Vendedor" size="sm">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach ($this->vendedores as $opcion)
                    <flux:select.option :value="$opcion->id">{{ $opcion->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="metodo" label="Forma de pago" size="sm">
                <flux:select.option value="">Todas</flux:select.option>
                @foreach (\App\Enums\MetodoPago::cases() as $opcion)
                    <flux:select.option :value="$opcion->value">{{ $opcion->etiqueta() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="estado" label="Estado" size="sm">
                <flux:select.option value="">Todas</flux:select.option>
                @foreach (\App\Enums\EstadoVenta::cases() as $opcion)
                    <flux:select.option :value="$opcion->value">{{ $opcion->etiqueta() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Resumen --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" data-tour="resumen">
        <x-panel.estadistica titulo="Total vendido" :valor="'$'.number_format($r['total'], 2)" icono="currency-dollar" destacado />
        <x-panel.estadistica titulo="Ventas" :valor="$r['ventas']" icono="receipt-percent"
            :detalle="$r['canceladas'] ? $r['canceladas'].' canceladas (no cuentan)' : 'Sin cancelaciones'" />
        <x-panel.estadistica titulo="Ticket promedio" :valor="'$'.number_format($r['promedio'], 2)" icono="calculator" />
        <div class="rounded-xl border border-zinc-200 bg-white p-5">
            <flux:text class="text-sm">Por forma de pago</flux:text>
            <dl class="mt-2 space-y-1 text-sm">
                @foreach (\App\Enums\MetodoPago::cases() as $m)
                    <div class="flex justify-between"><dt class="text-zinc-500">{{ $m->etiqueta() }}</dt><dd class="font-medium text-zinc-900">${{ number_format($r['porMetodo'][$m->value], 2) }}</dd></div>
                @endforeach
            </dl>
        </div>
    </div>

    {{-- Lista --}}
    <div class="rounded-xl border border-zinc-200 bg-white px-5" data-tour="tabla">
        <flux:table :paginate="$this->ventas">
            <flux:table.columns>
                <flux:table.column>Folio</flux:table.column>
                <flux:table.column>Fecha</flux:table.column>
                @unless ($this->esEncargado())
                    <flux:table.column>Restaurante</flux:table.column>
                @endunless
                <flux:table.column>Vendedor</flux:table.column>
                <flux:table.column>Pago</flux:table.column>
                <flux:table.column align="end">Total</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->ventas as $venta)
                    <flux:table.row :key="$venta->id">
                        <flux:table.cell>
                            <button type="button" wire:click="alternar({{ $venta->id }})" class="font-semibold text-zinc-900 hover:text-orange-600">
                                #{{ $venta->folioFormateado() }}
                            </button>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-600">{{ $venta->created_at->format('d/m H:i') }}</flux:table.cell>
                        @unless ($this->esEncargado())
                            <flux:table.cell>{{ $venta->restaurante->nombre }}</flux:table.cell>
                        @endunless
                        <flux:table.cell>{{ $venta->vendedor?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" color="zinc" :icon="$venta->metodo_pago->icono()">{{ $venta->metodo_pago->etiqueta() }}</flux:badge></flux:table.cell>
                        <flux:table.cell align="end" :class="$venta->estaCancelada() ? 'font-semibold text-zinc-400 line-through' : 'font-semibold text-zinc-900'">
                            ${{ number_format($venta->total, 2) }}
                        </flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$venta->estado->color()">{{ $venta->estado->etiqueta() }}</flux:badge></flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Acciones" />
                                <flux:menu>
                                    <flux:menu.item icon="eye" wire:click="alternar({{ $venta->id }})">Ver detalle</flux:menu.item>
                                    <flux:menu.item icon="printer" :href="route('tickets.ver', $venta)" target="_blank">Ver ticket</flux:menu.item>
                                    @unless ($venta->estaCancelada())
                                        <flux:menu.separator />
                                        <flux:menu.item icon="x-circle" variant="danger" wire:click="iniciarCancelacion({{ $venta->id }})">Cancelar venta</flux:menu.item>
                                    @endunless
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>

                    @if ($abiertaId === $venta->id)
                        <flux:table.row :key="'detalle-'.$venta->id">
                            <flux:table.cell colspan="8" class="!whitespace-normal bg-zinc-50">
                                <div class="grid gap-4 py-2 md:grid-cols-2">
                                    <table class="w-full text-sm">
                                        @foreach ($venta->detalles as $detalle)
                                            <tr>
                                                <td class="py-0.5 text-zinc-700">{{ $detalle->cantidad }} × {{ $detalle->nombre }}</td>
                                                <td class="py-0.5 text-right text-zinc-900">${{ number_format($detalle->importe, 2) }}</td>
                                            </tr>
                                        @endforeach
                                        @if ((float) $venta->descuento > 0)
                                            <tr><td class="py-0.5 text-zinc-500">Descuento</td><td class="py-0.5 text-right text-zinc-500">−${{ number_format($venta->descuento, 2) }}</td></tr>
                                        @endif
                                    </table>

                                    <div class="space-y-2 text-sm">
                                        @if ($venta->notas)<div><span class="text-zinc-500">Nota:</span> {{ $venta->notas }}</div>@endif
                                        @if ($venta->estaCancelada())
                                            <flux:callout icon="x-circle" color="red">
                                                <flux:callout.heading>Cancelada por {{ $venta->canceladaPor?->name ?? '—' }} el {{ $venta->cancelada_at?->format('d/m/Y H:i') }}</flux:callout.heading>
                                                <flux:callout.text>{{ $venta->motivo_cancelacion }}</flux:callout.text>
                                            </flux:callout>
                                        @elseif ($cancelandoId === $venta->id)
                                            <form wire:submit="confirmarCancelacion" class="space-y-3 rounded-lg border border-red-200 bg-white p-4">
                                                <flux:textarea wire:model="motivo" label="¿Por qué se cancela?" rows="2" placeholder="Ej. El cliente se arrepintió / se cobró dos veces" autofocus />
                                                <div class="flex justify-end gap-2">
                                                    <flux:button size="sm" variant="ghost" wire:click="$set('cancelandoId', null)">No cancelar</flux:button>
                                                    <flux:button size="sm" variant="danger" type="submit" icon="x-circle">Cancelar venta</flux:button>
                                                </div>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endif
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8">
                            <div class="flex flex-col items-center gap-2 py-10 text-center">
                                <flux:icon name="receipt-percent" variant="outline" class="size-8 text-orange-500" />
                                <flux:text>No hay ventas en este periodo.</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <x-recorrido :clave="$this->prefijo().'.ventas'" :pasos="[
        ['titulo' => 'Historial de ventas', 'descripcion' => $this->esEncargado() ? 'Aquí revisas las ventas de tu restaurante y cancelas las que se registraron por error.' : 'Aquí revisas las ventas de todos tus restaurantes y cancelas las que se registraron por error.'],
        ['elemento' => '[data-tour=filtros]', 'titulo' => '1. Filtra', 'descripcion' => 'Usa los atajos (Hoy, Ayer, Esta semana, Este mes) o elige fechas. También puedes filtrar por vendedor, forma de pago o estado.'],
        ['elemento' => '[data-tour=resumen]', 'titulo' => '2. Resumen', 'descripcion' => 'Total vendido, número de ventas, ticket promedio y cuánto entró por cada forma de pago. Las ventas canceladas no se suman.'],
        ['elemento' => '[data-tour=tabla]', 'titulo' => '3. Detalle y cancelación', 'descripcion' => 'Toca el folio para ver los productos. En el menú «···» reimprimes el ticket o cancelas la venta; al cancelar debes escribir el motivo y quedará registrado quién lo hizo.', 'lado' => 'top'],
    ]" />
</div>
