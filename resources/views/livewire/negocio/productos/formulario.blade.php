<div class="flex flex-col gap-6">
    <x-panel.encabezado
        :titulo="$this->esEdicion() ? 'Editar '.$producto->nombre : 'Nuevo producto'"
        :descripcion="$this->esEdicion() ? 'Cambia los datos del producto o en qué restaurantes se vende.' : 'Agrega un producto a tu catálogo.'"
        :regresar="route('negocio.productos.index')"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
        </x-slot:acciones>
    </x-panel.encabezado>

    <form wire:submit="guardar" class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Datos --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="datos">
                <flux:heading size="lg" class="mb-5">Datos del producto</flux:heading>
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <flux:input wire:model="nombre" label="Nombre" placeholder="Ej. Taco al pastor" required autofocus />
                    </div>
                    <flux:select wire:model="categoriaId" label="Categoría" placeholder="Sin categoría">
                        <flux:select.option value="">Sin categoría</flux:select.option>
                        @foreach ($this->categorias as $opcion)
                            <flux:select.option :value="$opcion->id">{{ $opcion->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div class="flex items-end">
                        <flux:link :href="route('negocio.productos.categorias')" wire:navigate class="text-sm">Administrar categorías</flux:link>
                    </div>
                    <div class="md:col-span-2">
                        <flux:textarea wire:model="descripcion" label="Descripción (opcional)" rows="2" placeholder="Ej. Con piña, cebolla y cilantro" />
                    </div>
                </div>
            </section>

            {{-- Precio --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="precio">
                <flux:heading size="lg">Precio y costo</flux:heading>
                <flux:text class="mb-5 text-sm">El costo es lo que te cuesta prepararlo. Sirve para calcular tu ganancia.</flux:text>
                <div class="grid gap-5 md:grid-cols-3">
                    <flux:input wire:model.live.debounce.300ms="precio" label="Precio de venta" icon="currency-dollar" inputmode="decimal" placeholder="0.00" required />
                    <flux:input wire:model.live.debounce.300ms="costo" label="Costo (opcional)" icon="currency-dollar" inputmode="decimal" placeholder="0.00" />
                    <div class="rounded-lg border border-orange-200 bg-orange-50 px-4 py-3">
                        <div class="text-xs text-zinc-500">Margen de ganancia</div>
                        @php($margen = $this->margen())
                        <div @class(['text-2xl font-semibold', 'text-zinc-400' => $margen === null, 'text-green-600' => $margen !== null && $margen >= 50, 'text-orange-600' => $margen !== null && $margen >= 20 && $margen < 50, 'text-red-600' => $margen !== null && $margen < 20])>
                            {{ $margen === null ? '—' : $margen.'%' }}
                        </div>
                    </div>
                </div>
            </section>

            {{-- Restaurantes --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="restaurantes">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <flux:heading size="lg">¿Dónde se vende?</flux:heading>
                        <flux:text class="text-sm">Deja el precio especial vacío para usar el precio de venta.</flux:text>
                    </div>
                    <div class="flex gap-2">
                        <flux:button size="sm" variant="ghost" wire:click="marcarTodos(true)">Todos</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="marcarTodos(false)">Ninguno</flux:button>
                    </div>
                </div>

                <div class="divide-y divide-zinc-100 rounded-lg border border-zinc-200">
                    @forelse ($this->restaurantesDisponibles() as $restaurante)
                        <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between" wire:key="rest-{{ $restaurante->id }}">
                            <flux:checkbox wire:model.live="enRestaurantes.{{ $restaurante->id }}.vende"
                                :label="$restaurante->nombre.($restaurante->activo ? '' : ' (inactivo)')" />
                            <div class="sm:w-48">
                                <flux:input size="sm" wire:model="enRestaurantes.{{ $restaurante->id }}.precio" icon="currency-dollar" inputmode="decimal"
                                    :placeholder="$precio !== '' ? number_format((float) $precio, 2).' (base)' : 'Precio especial'"
                                    :disabled="! ($enRestaurantes[$restaurante->id]['vende'] ?? false)" />
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-zinc-500">
                            Aún no tienes restaurantes.
                            <flux:link :href="route('negocio.restaurantes.crear')" wire:navigate>Registra uno</flux:link> para poder vender este producto.
                        </div>
                    @endforelse
                </div>
                <flux:error name="enRestaurantes.*.precio" />
            </section>
        </div>

        <div class="space-y-6">
            {{-- Foto --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="foto">
                <flux:heading size="lg" class="mb-4">Foto</flux:heading>
                <div class="mb-4 flex aspect-square items-center justify-center overflow-hidden rounded-xl border border-dashed border-orange-300 bg-orange-50/50">
                    @if ($imagen)
                        <img src="{{ $imagen->temporaryUrl() }}" alt="Vista previa" class="size-full object-cover">
                    @elseif ($producto?->imagen && ! $quitarImagen)
                        <img src="{{ $producto->urlImagen() }}" alt="{{ $producto->nombre }}" class="size-full object-cover">
                    @else
                        <div class="flex flex-col items-center gap-2 text-orange-500">
                            <flux:icon name="photo" variant="outline" class="size-10" />
                            <span class="text-xs text-zinc-500">JPG, PNG o WEBP, máximo 2 MB</span>
                        </div>
                    @endif
                </div>
                <flux:input type="file" wire:model="imagen" accept="image/jpeg,image/png,image/webp" />
                <div wire:loading wire:target="imagen" class="mt-2 text-xs text-orange-600">Subiendo foto…</div>
                @if ($producto?->imagen && ! $imagen)
                    <flux:checkbox wire:model.live="quitarImagen" label="Quitar la foto actual" class="mt-3" />
                @endif
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="activo">
                <flux:switch wire:model="activo" label="Producto activo" description="Si lo apagas, desaparece del menú de todos tus restaurantes." />
            </section>

            <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-6" data-tour="guardar">
                <flux:button type="submit" variant="primary" icon="check" class="w-full" wire:loading.attr="disabled" wire:target="guardar,imagen">
                    {{ $this->esEdicion() ? 'Guardar cambios' : 'Crear producto' }}
                </flux:button>
                <flux:button variant="ghost" class="w-full" :href="route('negocio.productos.index')" wire:navigate>Cancelar</flux:button>
            </div>
        </div>
    </form>

    <x-recorrido :clave="$this->esEdicion() ? 'negocio.productos.editar' : 'negocio.productos.crear'" :pasos="[
        ['titulo' => $this->esEdicion() ? 'Editar un producto' : 'Crear un producto', 'descripcion' => 'El formulario tiene cuatro partes: datos, precio, restaurantes y foto. Te explico cada una.'],
        ['elemento' => '[data-tour=datos]', 'titulo' => '1. Datos', 'descripcion' => 'Nombre (obligatorio), categoría y una descripción corta que ayude a tus vendedores a identificarlo.'],
        ['elemento' => '[data-tour=precio]', 'titulo' => '2. Precio y costo', 'descripcion' => 'El precio de venta es obligatorio. Si capturas el costo, verás al instante tu margen de ganancia en verde, naranja o rojo.', 'lado' => 'top'],
        ['elemento' => '[data-tour=restaurantes]', 'titulo' => '3. ¿Dónde se vende?', 'descripcion' => 'Marca los restaurantes que ofrecen este producto. Si en alguno cuesta distinto (por ejemplo, en una plaza), escribe ahí su precio especial.', 'lado' => 'top'],
        ['elemento' => '[data-tour=foto]', 'titulo' => '4. Foto', 'descripcion' => 'Opcional, pero ayuda mucho en el punto de venta. Si no subes una, se muestran las iniciales del producto.', 'lado' => 'left'],
        ['elemento' => '[data-tour=guardar]', 'titulo' => 'Guardar', 'descripcion' => 'Al guardar, el producto aparece en el menú de los restaurantes que marcaste.', 'lado' => 'left'],
    ]" />
</div>
