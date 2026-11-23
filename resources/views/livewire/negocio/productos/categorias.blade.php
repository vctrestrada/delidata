<div class="flex flex-col gap-6">
    <x-panel.encabezado
        titulo="Categorías"
        descripcion="Agrupan tus productos en el menú. El orden de esta lista es el orden en que tus vendedores las verán."
        :regresar="route('negocio.productos.index')"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
        </x-slot:acciones>
    </x-panel.encabezado>

    <div class="grid max-w-3xl gap-6">
        <form wire:submit="agregar" class="flex flex-col gap-3 rounded-xl border border-orange-200 bg-orange-50 p-5 sm:flex-row sm:items-start" data-tour="agregar">
            <div class="flex-1">
                <flux:input wire:model="nueva" placeholder="Nueva categoría, ej. Tacos, Bebidas, Postres…" />
            </div>
            <flux:button type="submit" variant="primary" icon="plus">Agregar</flux:button>
        </form>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white" data-tour="lista">
            @forelse ($this->categorias as $categoria)
                <div class="flex items-center gap-3 border-b border-zinc-100 px-4 py-3 last:border-0" wire:key="categoria-{{ $categoria->id }}">
                    <div class="flex flex-col" @if ($loop->first) data-tour="orden" @endif>
                        <flux:button size="xs" variant="subtle" icon="chevron-up" wire:click="mover({{ $categoria->id }}, -1)" :disabled="$loop->first" aria-label="Subir" />
                        <flux:button size="xs" variant="subtle" icon="chevron-down" wire:click="mover({{ $categoria->id }}, 1)" :disabled="$loop->last" aria-label="Bajar" />
                    </div>

                    @if ($editandoId === $categoria->id)
                        <form wire:submit="guardarEdicion" class="flex flex-1 items-start gap-2">
                            <div class="flex-1"><flux:input wire:model="editandoNombre" size="sm" autofocus /></div>
                            <flux:button type="submit" size="sm" variant="primary" icon="check" aria-label="Guardar" />
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="cancelarEdicion" aria-label="Cancelar" />
                        </form>
                    @else
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-medium text-zinc-900">{{ $categoria->nombre }}</div>
                            <div class="text-xs text-zinc-500">{{ $categoria->productos_count }} {{ $categoria->productos_count === 1 ? 'producto' : 'productos' }}</div>
                        </div>
                        <div class="flex items-center gap-1" @if ($loop->first) data-tour="acciones" @endif>
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editar({{ $categoria->id }})" aria-label="Renombrar" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="eliminar({{ $categoria->id }})"
                                wire:confirm="¿Eliminar «{{ $categoria->nombre }}»? Sus productos no se borran; quedarán sin categoría."
                                aria-label="Eliminar" />
                        </div>
                    @endif
                </div>
            @empty
                <div class="flex flex-col items-center gap-2 px-5 py-10 text-center">
                    <flux:icon name="tag" variant="outline" class="size-8 text-orange-500" />
                    <flux:text>Aún no tienes categorías. Agrega la primera arriba.</flux:text>
                </div>
            @endforelse
        </div>
    </div>

    <x-recorrido clave="negocio.categorias" :pasos="[
        ['titulo' => 'Categorías del menú', 'descripcion' => 'Las categorías ordenan tu menú: por ejemplo Tacos, Bebidas y Postres. Así tus vendedores encuentran rápido cada producto.'],
        ['elemento' => '[data-tour=agregar]', 'titulo' => '1. Agregar', 'descripcion' => 'Escribe el nombre y presiona «Agregar». Se coloca al final de la lista.'],
        ['elemento' => '[data-tour=orden]', 'titulo' => '2. Ordenar', 'descripcion' => 'Con las flechas subes o bajas una categoría. Las primeras aparecen primero en el punto de venta.', 'lado' => 'right'],
        ['elemento' => '[data-tour=acciones]', 'titulo' => '3. Renombrar o eliminar', 'descripcion' => 'El lápiz te deja cambiar el nombre ahí mismo. Si eliminas una categoría, sus productos no se borran: quedan sin categoría.', 'lado' => 'left'],
    ]" />
</div>
