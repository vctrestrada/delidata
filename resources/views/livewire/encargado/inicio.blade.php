<div class="flex flex-col gap-6">
    <x-panel.encabezado
        titulo="Hola, {{ auth()->user()->name }}"
        :descripcion="'Eres encargado de '.$this->restaurante->nombre.' ('.$this->restaurante->negocio->nombre.').'"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
            <flux:button variant="primary" icon="user-plus" :href="route('encargado.personal.crear')" wire:navigate data-tour="nuevo-vendedor">
                Nuevo vendedor
            </flux:button>
        </x-slot:acciones>
    </x-panel.encabezado>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Restaurante --}}
        <section class="rounded-xl border border-orange-200 bg-orange-50 p-6" data-tour="restaurante">
            <div class="flex size-10 items-center justify-center rounded-lg bg-orange-500 text-white">
                <flux:icon name="building-storefront" variant="outline" class="size-5" />
            </div>
            <flux:heading size="lg" class="mt-4">{{ $this->restaurante->nombre }}</flux:heading>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-zinc-500">Dirección</dt><dd class="text-right text-zinc-900">{{ $this->restaurante->direccion ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-zinc-500">Teléfono</dt><dd class="text-zinc-900">{{ $this->restaurante->telefono ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-zinc-500">Horario</dt><dd class="text-zinc-900">{{ $this->restaurante->horario() ?? '—' }}</dd></div>
            </dl>
        </section>

        {{-- Vendedores --}}
        <section class="rounded-xl border border-zinc-200 bg-white lg:col-span-2" data-tour="vendedores">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
                <div>
                    <flux:heading size="lg">Vendedores</flux:heading>
                    <flux:text class="text-sm">{{ $this->vendedores->where('activo', true)->count() }} activos de {{ $this->vendedores->count() }}</flux:text>
                </div>
                <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('encargado.personal.index')" wire:navigate>Administrar</flux:button>
            </div>

            @forelse ($this->vendedores->take(6) as $vendedor)
                <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-5 py-3 last:border-0">
                    <div class="flex min-w-0 items-center gap-3">
                        <flux:avatar size="sm" :name="$vendedor->name" :initials="$vendedor->initials()" color="orange" />
                        <div class="min-w-0">
                            <div class="truncate font-medium text-zinc-900">{{ $vendedor->name }}</div>
                            <div class="truncate text-xs text-zinc-500">{{ $vendedor->email }}</div>
                        </div>
                    </div>
                    @unless ($vendedor->activo)
                        <flux:badge size="sm" color="zinc">Inactivo</flux:badge>
                    @endunless
                </div>
            @empty
                <div class="flex flex-col items-center gap-2 px-5 py-10 text-center">
                    <flux:icon name="user-group" variant="outline" class="size-8 text-orange-500" />
                    <flux:text>Aún no hay vendedores en tu restaurante.</flux:text>
                </div>
            @endforelse
        </section>
    </div>

    <section class="space-y-3">
        <flux:heading size="lg">Próximamente</flux:heading>
        <div class="grid gap-4 md:grid-cols-2">
            <x-panel.modulo icono="receipt-percent" entrega="5" titulo="Ventas del día" descripcion="Lo que vendió cada vendedor." />
            <x-panel.modulo icono="banknotes" entrega="6" titulo="Gastos" descripcion="Registra los gastos del restaurante." />
        </div>
    </section>

    <x-recorrido clave="encargado.inicio" :pasos="[
        ['titulo' => 'Bienvenido, encargado', 'descripcion' => 'Desde aquí administras tu restaurante y a sus vendedores. Te muestro cómo.'],
        ['elemento' => '[data-tour=restaurante]', 'titulo' => 'Tu restaurante', 'descripcion' => 'Los datos del local que tienes a tu cargo. Si algo está mal, pídele al dueño que lo corrija.'],
        ['elemento' => '[data-tour=vendedores]', 'titulo' => 'Tus vendedores', 'descripcion' => 'Las personas que registran ventas en tu restaurante. Con «Administrar» ves la lista completa.', 'lado' => 'top'],
        ['elemento' => '[data-tour=menu-menu]', 'titulo' => 'El menú', 'descripcion' => 'En «Menú» ves los productos de tu restaurante y marcas los que se agotaron.', 'lado' => 'right'],
        ['elemento' => '[data-tour=nuevo-vendedor]', 'titulo' => 'Agrega un vendedor', 'descripcion' => 'Crea su cuenta con nombre, correo y contraseña. Quedará asignado a tu restaurante automáticamente.', 'lado' => 'left'],
    ]" />
</div>
