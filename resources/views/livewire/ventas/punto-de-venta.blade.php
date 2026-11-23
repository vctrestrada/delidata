@php
    $t = $this->totales;
@endphp

<div class="flex flex-col gap-6">
    <x-panel.encabezado
        titulo="Punto de venta"
        :descripcion="$this->restaurante->nombre.' · Hoy llevas '.$this->resumenDelDia['ventas'].' ventas por $'.number_format($this->resumenDelDia['total'], 2)"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
            <flux:button icon="clock" :href="route('ventas.historial')" wire:navigate>Mis ventas</flux:button>
        </x-slot:acciones>
    </x-panel.encabezado>

    <div class="grid gap-6 lg:grid-cols-[1fr_24rem]">
        {{-- Catálogo --}}
        <div class="flex min-w-0 flex-col gap-4">
            <div data-tour="buscar">
                <flux:input wire:model.live.debounce.250ms="buscar" icon="magnifying-glass" placeholder="Buscar producto…" clearable />
            </div>

            <div class="flex gap-2 overflow-x-auto pb-1" data-tour="categorias">
                <button type="button" wire:click="$set('categoria', '')" @class([
                    'shrink-0 rounded-full border px-4 py-1.5 text-sm font-medium transition',
                    'border-orange-500 bg-orange-500 text-white' => $categoria === '',
                    'border-zinc-200 bg-white text-zinc-700 hover:border-orange-300' => $categoria !== '',
                ])>Todo</button>
                @foreach ($this->categorias as $cat)
                    <button type="button" wire:click="$set('categoria', '{{ $cat->id }}')" wire:key="cat-{{ $cat->id }}" @class([
                        'shrink-0 rounded-full border px-4 py-1.5 text-sm font-medium transition',
                        'border-orange-500 bg-orange-500 text-white' => $categoria === (string) $cat->id,
                        'border-zinc-200 bg-white text-zinc-700 hover:border-orange-300' => $categoria !== (string) $cat->id,
                    ])>{{ $cat->nombre }}</button>
                @endforeach
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4" data-tour="productos">
                @forelse ($this->catalogo as $producto)
                    @php($enCarrito = $carrito[$producto->id] ?? 0)
                    <button type="button" wire:key="p-{{ $producto->id }}" wire:click="agregar({{ $producto->id }})"
                        @class([
                            'group relative flex flex-col gap-2 rounded-xl border bg-white p-3 text-left transition active:scale-[0.98]',
                            'border-orange-500 ring-2 ring-orange-200' => $enCarrito > 0,
                            'border-zinc-200 hover:border-orange-300 hover:shadow-sm' => $enCarrito === 0,
                        ])>
                        @if ($enCarrito > 0)
                            <span class="absolute -right-2 -top-2 flex size-6 items-center justify-center rounded-full bg-orange-500 text-xs font-bold text-white shadow">{{ $enCarrito }}</span>
                        @endif
                        <x-panel.miniatura-producto :producto="$producto" tamano="aspect-square w-full" />
                        <div class="min-w-0">
                            <div class="line-clamp-2 text-sm font-medium leading-tight text-zinc-900">{{ $producto->nombre }}</div>
                            <div class="mt-1 text-sm font-semibold text-orange-600">${{ number_format($producto->pivot->precio ?? $producto->precio, 2) }}</div>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full flex flex-col items-center gap-2 rounded-xl border border-dashed border-orange-300 bg-white px-6 py-12 text-center">
                        <flux:icon name="book-open" variant="outline" class="size-8 text-orange-500" />
                        <flux:text>{{ $buscar !== '' ? 'No hay productos con ese nombre.' : 'No hay productos disponibles en este momento.' }}</flux:text>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Ticket --}}
        <aside class="lg:sticky lg:top-6 lg:self-start" data-tour="ticket">
            @if ($this->ultimaVenta)
                {{-- Venta cobrada --}}
                @php($venta = $this->ultimaVenta)
                <div class="space-y-5 rounded-xl border border-green-200 bg-white p-6 text-center">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-green-50 text-green-600">
                        <flux:icon name="check" class="size-7" />
                    </div>
                    <div>
                        <flux:text class="text-sm">Venta #{{ $venta->folioFormateado() }}</flux:text>
                        <div class="text-3xl font-semibold text-zinc-900">${{ number_format($venta->total, 2) }}</div>
                        <flux:text class="text-sm">{{ $venta->metodo_pago->etiqueta() }} · {{ $venta->piezas() }} piezas</flux:text>
                    </div>
                    @if ($venta->cambio !== null)
                        <div class="rounded-xl bg-orange-50 px-4 py-4">
                            <div class="text-xs font-medium uppercase tracking-wider text-orange-700">Cambio a entregar</div>
                            <div class="text-4xl font-bold text-orange-600">${{ number_format($venta->cambio, 2) }}</div>
                        </div>
                    @endif
                    <div class="grid gap-2">
                        <flux:button icon="printer" :href="route('tickets.ver', $venta)" target="_blank">Imprimir ticket</flux:button>
                        <flux:button variant="primary" icon="plus" wire:click="nuevaVenta">Nueva venta</flux:button>
                    </div>
                </div>
            @else
                <div class="flex flex-col rounded-xl border border-zinc-200 bg-white">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
                        <flux:heading size="lg">Venta actual</flux:heading>
                        @if ($carrito)
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="vaciar" wire:confirm="¿Vaciar la venta actual?">Vaciar</flux:button>
                        @endif
                    </div>

                    {{-- Renglones --}}
                    <div class="max-h-80 divide-y divide-zinc-100 overflow-y-auto">
                        @forelse ($this->renglones as $id => $renglon)
                            <div class="flex items-center gap-3 px-5 py-3" wire:key="r-{{ $id }}">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium text-zinc-900">{{ $renglon['producto']->nombre }}</div>
                                    <div class="text-xs text-zinc-500">${{ number_format($renglon['precio'], 2) }} c/u</div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <flux:button size="xs" variant="subtle" icon="minus" wire:click="restar({{ $id }})" aria-label="Uno menos" />
                                    <span class="w-6 text-center text-sm font-semibold">{{ $renglon['cantidad'] }}</span>
                                    <flux:button size="xs" variant="subtle" icon="plus" wire:click="agregar({{ $id }})" aria-label="Uno más" />
                                </div>
                                <div class="w-20 text-right text-sm font-semibold text-zinc-900">${{ number_format($renglon['importe'], 2) }}</div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center gap-2 px-5 py-10 text-center">
                                <flux:icon name="shopping-cart" variant="outline" class="size-8 text-orange-500" />
                                <flux:text class="text-sm">Toca un producto para agregarlo.</flux:text>
                            </div>
                        @endforelse
                    </div>
                    <flux:error name="carrito" class="px-5" />

                    @if ($carrito)
                        <div class="space-y-4 border-t border-zinc-200 px-5 py-4">
                            {{-- Método de pago --}}
                            <div data-tour="metodo">
                                <flux:radio.group wire:model.live="metodo" variant="segmented" size="sm">
                                    @foreach (\App\Enums\MetodoPago::cases() as $m)
                                        <flux:radio :value="$m->value" :icon="$m->icono()" :label="$m->etiqueta()" />
                                    @endforeach
                                </flux:radio.group>
                            </div>

                            @if ($metodo === 'efectivo')
                                <div class="space-y-2" data-tour="efectivo">
                                    <flux:input wire:model.live.debounce.300ms="recibido" label="Efectivo recibido" icon="banknotes" inputmode="decimal" placeholder="0.00" />
                                    <div class="flex flex-wrap gap-2">
                                        <flux:button size="xs" wire:click="recibir">Exacto</flux:button>
                                        @foreach ($this->billetesSugeridos() as $billete)
                                            <flux:button size="xs" wire:click="recibir({{ $billete }})">${{ $billete }}</flux:button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <details class="text-sm">
                                <summary class="cursor-pointer text-zinc-500 hover:text-orange-600">Descuento o nota</summary>
                                <div class="mt-3 grid gap-3">
                                    <flux:input wire:model.live.debounce.300ms="descuento" label="Descuento ($)" inputmode="decimal" placeholder="0.00" size="sm" />
                                    <flux:input wire:model="notas" label="Nota" placeholder="Ej. Para llevar" size="sm" />
                                </div>
                            </details>

                            {{-- Totales --}}
                            <dl class="space-y-1 text-sm" data-tour="totales">
                                <div class="flex justify-between text-zinc-500"><dt>Subtotal ({{ $t['piezas'] }} piezas)</dt><dd>${{ number_format($t['subtotal'], 2) }}</dd></div>
                                @if ($t['descuento'] > 0)
                                    <div class="flex justify-between text-zinc-500"><dt>Descuento</dt><dd>−${{ number_format($t['descuento'], 2) }}</dd></div>
                                @endif
                                <div class="flex justify-between pt-1 text-xl font-semibold text-zinc-900"><dt>Total</dt><dd>${{ number_format($t['total'], 2) }}</dd></div>
                                @if ($t['cambio'] !== null)
                                    <div @class(['flex justify-between font-medium', 'text-orange-600' => $t['cambio'] >= 0, 'text-red-600' => $t['cambio'] < 0])>
                                        <dt>{{ $t['cambio'] >= 0 ? 'Cambio' : 'Falta' }}</dt><dd>${{ number_format(abs($t['cambio']), 2) }}</dd>
                                    </div>
                                @endif
                            </dl>
                            <flux:error name="recibido" />

                            <flux:button variant="primary" class="w-full" icon="check" wire:click="cobrar" wire:loading.attr="disabled" wire:target="cobrar" data-tour="cobrar">
                                Cobrar ${{ number_format($t['total'], 2) }}
                            </flux:button>
                        </div>
                    @endif
                </div>
            @endif
        </aside>
    </div>

    <x-recorrido clave="ventas.punto-de-venta" :pasos="[
        ['titulo' => 'Tu punto de venta', 'descripcion' => 'Aquí registras cada venta en 3 pasos: tocas los productos, eliges cómo te pagan y cobras. Te muestro dónde está cada cosa.'],
        ['elemento' => '[data-tour=buscar]', 'titulo' => 'Buscar', 'descripcion' => 'Escribe parte del nombre para encontrar un producto rápido.'],
        ['elemento' => '[data-tour=categorias]', 'titulo' => 'Categorías', 'descripcion' => 'Toca una categoría para ver solo esos productos. «Todo» los muestra todos.'],
        ['elemento' => '[data-tour=productos]', 'titulo' => '1. Toca para agregar', 'descripcion' => 'Cada toque suma una pieza a la venta; el número naranja indica cuántas llevas. Los productos agotados no aparecen.', 'lado' => 'top'],
        ['elemento' => '[data-tour=ticket]', 'titulo' => '2. Revisa la venta', 'descripcion' => 'Aquí ves lo que llevas. Con − y + ajustas cantidades. Cuando agregues productos aparecerán el método de pago, el efectivo recibido y el botón para cobrar.', 'lado' => 'left'],
        ['titulo' => '3. Cobra', 'descripcion' => 'Elige Efectivo, Tarjeta o Transferencia. En efectivo escribe cuánto te dieron (o usa los botones de billete) y el sistema calcula el cambio. Al cobrar podrás imprimir el ticket. ¡Listo para vender!'],
    ]" />
</div>
