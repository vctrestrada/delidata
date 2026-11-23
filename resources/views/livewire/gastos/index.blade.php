@php($r = $this->resumen)

<div class="flex flex-col gap-6">
    <x-panel.encabezado
        titulo="Gastos"
        :descripcion="$this->esEncargado()
            ? 'Lo que gasta '.auth()->user()->restaurante?->nombre.'. Registra cada gasto el mismo día para que tus números cuadren.'
            : 'Los gastos de tus restaurantes y cuánto representan de tus ventas.'"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
            <flux:button variant="primary" icon="plus" :href="$this->ruta('gastos.crear')" wire:navigate data-tour="nuevo">Registrar gasto</flux:button>
        </x-slot:acciones>
    </x-panel.encabezado>

    {{-- Filtros --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4" data-tour="filtros">
        <div class="flex flex-wrap gap-2">
            @foreach (['hoy' => 'Hoy', 'semana' => 'Esta semana', 'mes' => 'Este mes', 'mes-pasado' => 'Mes pasado'] as $clave => $texto)
                <flux:button size="sm" variant="ghost" wire:click="periodo('{{ $clave }}')">{{ $texto }}</flux:button>
            @endforeach
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
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
            <flux:select wire:model.live="categoria" label="Categoría" size="sm">
                <flux:select.option value="">Todas</flux:select.option>
                @foreach (\App\Enums\CategoriaGasto::cases() as $opcion)
                    <flux:select.option :value="$opcion->value">{{ $opcion->etiqueta() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model.live.debounce.400ms="buscar" label="Buscar" size="sm" placeholder="Concepto o proveedor" icon="magnifying-glass" />
        </div>
    </div>

    {{-- Resumen --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="grid gap-4 sm:grid-cols-3 lg:col-span-3 xl:col-span-1 xl:grid-cols-1" data-tour="resumen">
            <x-panel.estadistica titulo="Total de gastos" :valor="'$'.number_format($r['total'], 2)" icono="banknotes" destacado
                :detalle="$r['registros'].' registros'" />
            <x-panel.estadistica titulo="Ventas del periodo" :valor="'$'.number_format($r['ventas'], 2)" icono="currency-dollar" />
            <div @class([
                'rounded-xl border p-5',
                'border-green-200 bg-green-50' => $r['utilidad'] >= 0,
                'border-red-200 bg-red-50' => $r['utilidad'] < 0,
            ])>
                <flux:text class="text-sm">{{ $r['utilidad'] >= 0 ? 'Utilidad' : 'Pérdida' }} (ventas − gastos)</flux:text>
                <div @class(['text-3xl font-semibold tracking-tight', 'text-green-700' => $r['utilidad'] >= 0, 'text-red-700' => $r['utilidad'] < 0])>
                    ${{ number_format(abs($r['utilidad']), 2) }}
                </div>
                @if ($r['proporcion'] !== null)
                    <flux:text class="text-xs">Los gastos son el {{ $r['proporcion'] }}% de las ventas.</flux:text>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 lg:col-span-3 xl:col-span-2" data-tour="categorias">
            <flux:heading size="lg">¿En qué se va el dinero?</flux:heading>
            <flux:text class="mb-4 text-sm">Gastos del periodo por categoría.</flux:text>

            @forelse ($r['porCategoria'] as $fila)
                @php($porcentaje = $r['total'] > 0 ? round($fila['monto'] / $r['total'] * 100) : 0)
                <button type="button" wire:click="$set('categoria', '{{ $fila['categoria']->value }}')" class="group mb-3 block w-full text-left last:mb-0">
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-zinc-700 group-hover:text-orange-600">
                            <flux:icon :name="$fila['categoria']->icono()" variant="micro" />
                            {{ $fila['categoria']->etiqueta() }}
                        </span>
                        <span class="font-medium text-zinc-900">${{ number_format($fila['monto'], 2) }} <span class="text-xs font-normal text-zinc-500">({{ $porcentaje }}%)</span></span>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-zinc-100">
                        <div class="h-full rounded-full {{ $fila['categoria']->colorBarra() }}" style="width: {{ max(2, $porcentaje) }}%"></div>
                    </div>
                </button>
            @empty
                <div class="py-8 text-center text-sm text-zinc-500">No hay gastos en este periodo.</div>
            @endforelse
        </div>
    </div>

    {{-- Lista --}}
    <div class="rounded-xl border border-zinc-200 bg-white px-5" data-tour="tabla">
        <flux:table :paginate="$this->gastos">
            <flux:table.columns>
                <flux:table.column>Fecha</flux:table.column>
                <flux:table.column>Concepto</flux:table.column>
                <flux:table.column>Categoría</flux:table.column>
                @unless ($this->esEncargado())
                    <flux:table.column>Restaurante</flux:table.column>
                @endunless
                <flux:table.column>Pago</flux:table.column>
                <flux:table.column align="end">Monto</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->gastos as $gasto)
                    <flux:table.row :key="$gasto->id">
                        <flux:table.cell class="text-zinc-600">{{ $gasto->fecha->translatedFormat('d M') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <div class="min-w-0">
                                    <div class="max-w-64 truncate font-medium text-zinc-900">{{ $gasto->concepto }}</div>
                                    <div class="max-w-64 truncate text-xs text-zinc-500">
                                        {{ $gasto->proveedor ?? 'Sin proveedor' }} · {{ $gasto->registradoPor?->name ?? '—' }}
                                    </div>
                                </div>
                                @if ($gasto->comprobante)
                                    <flux:tooltip content="Ver comprobante">
                                        <a href="{{ route('gastos.comprobante', $gasto) }}" target="_blank" class="text-orange-600 hover:text-orange-700">
                                            <flux:icon :name="$gasto->comprobanteEsPdf() ? 'document-text' : 'photo'" variant="micro" />
                                        </a>
                                    </flux:tooltip>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="orange" :icon="$gasto->categoria->icono()">{{ $gasto->categoria->etiqueta() }}</flux:badge>
                        </flux:table.cell>
                        @unless ($this->esEncargado())
                            <flux:table.cell>{{ $gasto->restaurante->nombre }}</flux:table.cell>
                        @endunless
                        <flux:table.cell class="text-zinc-600">{{ $gasto->metodo_pago->etiqueta() }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-semibold text-zinc-900">${{ number_format($gasto->monto, 2) }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Acciones" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" :href="$this->ruta('gastos.editar', $gasto)" wire:navigate>Editar</flux:menu.item>
                                    @if ($gasto->comprobante)
                                        <flux:menu.item icon="paper-clip" :href="route('gastos.comprobante', $gasto)" target="_blank">Ver comprobante</flux:menu.item>
                                    @endif
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="eliminar({{ $gasto->id }})"
                                        wire:confirm="¿Eliminar el gasto «{{ $gasto->concepto }}» por ${{ number_format($gasto->monto, 2) }}?">
                                        Eliminar
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <div class="flex flex-col items-center gap-2 py-10 text-center">
                                <flux:icon name="banknotes" variant="outline" class="size-8 text-orange-500" />
                                <flux:text>No hay gastos registrados con estos filtros.</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <x-recorrido :clave="$this->prefijo().'.gastos'" :pasos="[
        ['titulo' => 'Control de gastos', 'descripcion' => 'Registrar los gastos te dice si de verdad estás ganando. Aquí ves cuánto gastas, en qué y cuánto te queda después de tus ventas.'],
        ['elemento' => '[data-tour=nuevo]', 'titulo' => '1. Registrar un gasto', 'descripcion' => 'Captura cada compra o pago: fecha, categoría, concepto, monto y, si quieres, una foto del ticket o la factura.', 'lado' => 'left'],
        ['elemento' => '[data-tour=filtros]', 'titulo' => '2. Elige el periodo', 'descripcion' => 'Por defecto ves el mes actual. Usa los atajos o las fechas, y filtra por categoría o busca por concepto o proveedor.'],
        ['elemento' => '[data-tour=resumen]', 'titulo' => '3. ¿Estás ganando?', 'descripcion' => 'Compara tus gastos contra las ventas del mismo periodo. Verde es utilidad; rojo, pérdida.', 'lado' => 'right'],
        ['elemento' => '[data-tour=categorias]', 'titulo' => '4. ¿En qué se va el dinero?', 'descripcion' => 'Cada barra es una categoría. Tócala para ver solo esos gastos en la lista.', 'lado' => 'left'],
        ['elemento' => '[data-tour=tabla]', 'titulo' => '5. La lista', 'descripcion' => 'El clip naranja indica que el gasto tiene comprobante. En el menú «···» lo editas, ves el comprobante o lo eliminas.', 'lado' => 'top'],
    ]" />
</div>
