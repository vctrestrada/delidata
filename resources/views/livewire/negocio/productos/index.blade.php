<div class="flex flex-col gap-6">
    <x-panel.encabezado
        titulo="Productos"
        descripcion="El catálogo de tu negocio. Defines cada producto una vez y eliges en qué restaurantes se vende."
    >
        <x-slot:acciones>
            <x-recorrido.boton />
            <flux:button icon="tag" :href="route('negocio.productos.categorias')" wire:navigate data-tour="categorias">Categorías</flux:button>
            <flux:button variant="primary" icon="plus" :href="route('negocio.productos.crear')" wire:navigate data-tour="nuevo">Nuevo producto</flux:button>
        </x-slot:acciones>
    </x-panel.encabezado>

    <div class="flex flex-col gap-3 lg:flex-row lg:items-center" data-tour="filtros">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.400ms="buscar" icon="magnifying-glass" placeholder="Buscar producto…" clearable />
        </div>
        <div class="lg:w-52">
            <flux:select wire:model.live="categoria">
                <flux:select.option value="">Todas las categorías</flux:select.option>
                @foreach ($this->categorias as $opcion)
                    <flux:select.option :value="$opcion->id">{{ $opcion->nombre }}</flux:select.option>
                @endforeach
                <flux:select.option value="sin">Sin categoría</flux:select.option>
            </flux:select>
        </div>
        <div class="lg:w-56">
            <flux:select wire:model.live="restaurante">
                <flux:select.option value="">Todos los restaurantes</flux:select.option>
                @foreach ($this->restaurantesDisponibles() as $opcion)
                    <flux:select.option :value="$opcion->id">{{ $opcion->nombre }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        @if ($buscar !== '' || $categoria !== '' || $restaurante !== '')
            <flux:button variant="ghost" icon="x-mark" wire:click="limpiarFiltros">Limpiar</flux:button>
        @endif
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white px-5" data-tour="tabla">
        <flux:table :paginate="$this->productos">
            <flux:table.columns>
                <flux:table.column>Producto</flux:table.column>
                <flux:table.column>Categoría</flux:table.column>
                <flux:table.column align="end">Precio</flux:table.column>
                <flux:table.column align="end">Margen</flux:table.column>
                <flux:table.column>Se vende en</flux:table.column>
                <flux:table.column>Activo</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->productos as $producto)
                    @php($margen = $producto->margen())
                    <flux:table.row :key="$producto->id">
                        <flux:table.cell>
                            <a href="{{ route('negocio.productos.editar', $producto) }}" wire:navigate class="group flex items-center gap-3">
                                <x-panel.miniatura-producto :producto="$producto" />
                                <div class="min-w-0">
                                    <div @class(['truncate font-medium group-hover:text-orange-600', 'text-zinc-900' => $producto->activo, 'text-zinc-400' => ! $producto->activo])>{{ $producto->nombre }}</div>
                                    <div class="max-w-64 truncate text-xs text-zinc-500">{{ $producto->descripcion }}</div>
                                </div>
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($producto->categoria)
                                <flux:badge size="sm" color="zinc">{{ $producto->categoria->nombre }}</flux:badge>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end" class="font-medium text-zinc-900">${{ number_format($producto->precio, 2) }}</flux:table.cell>
                        <flux:table.cell align="end">
                            @if ($margen === null)
                                <span class="text-zinc-400">—</span>
                            @else
                                <span @class(['font-medium', 'text-green-600' => $margen >= 50, 'text-orange-600' => $margen >= 20 && $margen < 50, 'text-red-600' => $margen < 20])>{{ $margen }}%</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @php($en = $producto->restaurantes->count())
                            @if ($en === 0)
                                <span class="text-orange-600">Ningún restaurante</span>
                            @elseif ($en === $this->totalRestaurantes)
                                <span class="text-zinc-700">Todos ({{ $en }})</span>
                            @else
                                <flux:tooltip :content="$producto->restaurantes->pluck('nombre')->join(', ')">
                                    <span class="cursor-help text-zinc-700 underline decoration-dotted">{{ $en }} de {{ $this->totalRestaurantes }}</span>
                                </flux:tooltip>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:switch :checked="$producto->activo" wire:click="alternarActivo({{ $producto->id }})" aria-label="Activar o desactivar {{ $producto->nombre }}" />
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Acciones" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" :href="route('negocio.productos.editar', $producto)" wire:navigate>Editar</flux:menu.item>
                                    <flux:menu.item icon="document-duplicate" wire:click="duplicar({{ $producto->id }})">Duplicar</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="eliminar({{ $producto->id }})"
                                        wire:confirm="¿Eliminar «{{ $producto->nombre }}» de todo tu catálogo? Si solo quieres ocultarlo, apaga su interruptor.">
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
                                <flux:icon name="book-open" variant="outline" class="size-8 text-orange-500" />
                                @if ($buscar !== '' || $categoria !== '' || $restaurante !== '')
                                    <flux:text>Ningún producto coincide con esos filtros.</flux:text>
                                @else
                                    <flux:text>Tu catálogo está vacío. Te recomendamos crear primero tus categorías.</flux:text>
                                    <flux:button size="sm" variant="primary" icon="plus" :href="route('negocio.productos.crear')" wire:navigate>Crear el primer producto</flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <x-recorrido clave="negocio.productos" :pasos="[
        ['titulo' => 'Tu catálogo de productos', 'descripcion' => 'Aquí armas el menú de tu negocio. Cada producto se crea una sola vez y tú decides en qué restaurantes se vende y a qué precio.'],
        ['elemento' => '[data-tour=categorias]', 'titulo' => '1. Primero, las categorías', 'descripcion' => 'Crea grupos como Tacos, Bebidas o Postres. Ordenan el menú que verán tus vendedores.', 'lado' => 'left'],
        ['elemento' => '[data-tour=nuevo]', 'titulo' => '2. Nuevo producto', 'descripcion' => 'Captura nombre, precio, costo, foto y en qué restaurantes se vende.', 'lado' => 'left'],
        ['elemento' => '[data-tour=filtros]', 'titulo' => '3. Buscar y filtrar', 'descripcion' => 'Filtra por categoría o por restaurante para revisar un menú en particular.'],
        ['elemento' => '[data-tour=tabla]', 'titulo' => '4. El listado', 'descripcion' => 'El margen te dice cuánto ganas de cada venta (verde 50% o más, naranja entre 20% y 50%, rojo menos de 20%). «Se vende en» indica en cuántos restaurantes está. Con el interruptor lo ocultas de todos los menús sin borrarlo.', 'lado' => 'top'],
    ]" />
</div>
