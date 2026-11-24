<div class="flex flex-col gap-6">
    <x-panel.encabezado
        titulo="Menú"
        :descripcion="'Productos que se venden en '.$this->restaurante->nombre.'. Marca como agotado lo que ya no tengas para que tus vendedores no lo ofrezcan.'"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
            @if ($this->agotados > 0)
                <flux:button icon="arrow-path" wire:click="reactivarTodos" wire:confirm="¿Marcar todos los productos como disponibles?" data-tour="reactivar">
                    Reactivar todo ({{ $this->agotados }})
                </flux:button>
            @endif
        </x-slot:acciones>
    </x-panel.encabezado>

    <div class="max-w-md" data-tour="buscar">
        <flux:input wire:model.live.debounce.300ms="buscar" icon="magnifying-glass" placeholder="Buscar en el menú…" clearable />
    </div>

    <div class="space-y-6" data-tour="menu">
        @forelse ($this->menu as $categoria => $productos)
            <section wire:key="cat-{{ \Illuminate\Support\Str::slug($categoria) }}">
                <div class="mb-3 flex items-center gap-3">
                    <flux:heading size="lg">{{ $categoria }}</flux:heading>
                    <span class="h-px flex-1 bg-zinc-200"></span>
                    <span class="text-xs text-zinc-500">{{ $productos->count() }}</span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($productos as $producto)
                        @php
                            $disponible = (bool) $producto->pivot->disponible;
                            $precio = $producto->pivot->precio ?? $producto->precio;
                        @endphp
                        <div wire:key="prod-{{ $producto->id }}" @class([
                            'flex items-center gap-3 rounded-xl border p-3 transition',
                            'border-zinc-200 bg-white' => $disponible,
                            'border-zinc-200 bg-zinc-50 opacity-70' => ! $disponible,
                        ]) @if ($loop->parent->first && $loop->first) data-tour="producto" @endif>
                            <x-panel.miniatura-producto :producto="$producto" :class="$disponible ? '' : 'grayscale'" />
                            <div class="min-w-0 flex-1">
                                <div @class(['truncate font-medium', 'text-zinc-900' => $disponible, 'text-zinc-500 line-through' => ! $disponible])>{{ $producto->nombre }}</div>
                                <div class="text-sm font-semibold text-orange-600">${{ number_format($precio, 2) }}</div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <x-panel.interruptor :activo="$disponible" wire:click="alternarDisponible({{ $producto->id }})"
                                    accesible="Disponible: {{ $producto->nombre }}" />
                                <span @class(['text-[11px] font-medium', 'text-green-600' => $disponible, 'text-red-600' => ! $disponible])>
                                    {{ $disponible ? 'Disponible' : 'Agotado' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-orange-300 bg-white px-6 py-12 text-center">
                <flux:icon name="book-open" variant="outline" class="size-8 text-orange-500" />
                <flux:text>{{ $buscar !== '' ? 'No hay productos con ese nombre.' : 'El dueño aún no asigna productos a tu restaurante.' }}</flux:text>
            </div>
        @endforelse
    </div>

    <x-recorrido clave="encargado.menu" :pasos="[
        ['titulo' => 'El menú de tu restaurante', 'descripcion' => 'Aquí ves los productos que se venden en tu restaurante, agrupados por categoría y con su precio. Los precios los define el dueño.'],
        ['elemento' => '[data-tour=buscar]', 'titulo' => 'Buscar', 'descripcion' => 'Escribe parte del nombre para encontrar un producto rápido.'],
        ['elemento' => '[data-tour=producto]', 'titulo' => 'Disponible o agotado', 'descripcion' => 'Cuando se acabe un producto, apaga su interruptor: quedará como «Agotado» y tus vendedores no podrán venderlo. Enciéndelo cuando vuelvas a tenerlo.', 'lado' => 'bottom'],
        ['elemento' => '[data-tour=reactivar]', 'titulo' => 'Reactivar todo', 'descripcion' => 'Al iniciar el día puedes marcar todo como disponible con un solo clic.', 'lado' => 'left'],
    ]" />
</div>
