@php
    $licencia = $this->negocio->licenciaActual();
    $limite = $this->negocio->limiteRestaurantes();
    $usados = $this->restaurantes->count();
@endphp

<div class="flex flex-col gap-6">
    <x-panel.encabezado
        titulo="Hola, {{ auth()->user()->name }}"
        :descripcion="'Administra '.$this->negocio->nombre.': tus restaurantes y tu personal.'"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
            <flux:button icon="user-plus" :href="route('negocio.personal.crear')" wire:navigate>Agregar personal</flux:button>
            <x-panel.boton-nuevo-restaurante :disponible="$usados < $limite" />
        </x-slot:acciones>
    </x-panel.encabezado>

    {{-- Resumen --}}
    <div class="grid gap-4 md:grid-cols-3" data-tour="resumen">
        <div class="rounded-xl border border-zinc-200 bg-white p-5">
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-sm">Restaurantes</flux:text>
                    <div class="text-3xl font-semibold tracking-tight text-zinc-900">
                        {{ $usados }} <span class="text-base font-normal text-zinc-400">de {{ $limite }}</span>
                    </div>
                </div>
                <div class="flex size-10 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                    <flux:icon name="building-storefront" variant="outline" class="size-5" />
                </div>
            </div>
            <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-zinc-100">
                <div class="h-full rounded-full bg-orange-500" style="width: {{ $limite ? min(100, round($usados / $limite * 100)) : 0 }}%"></div>
            </div>
            <flux:text class="mt-2 text-xs">Tu plan permite hasta {{ $limite }} {{ $limite === 1 ? 'restaurante' : 'restaurantes' }}.</flux:text>
        </div>

        <x-panel.estadistica titulo="Personal" :valor="$this->personal['encargados'] + $this->personal['vendedores']" icono="user-group"
            :detalle="$this->personal['encargados'].' encargados · '.$this->personal['vendedores'].' vendedores'" />

        <div class="rounded-xl border border-orange-200 bg-orange-50 p-5" data-tour="suscripcion">
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-sm">Suscripción</flux:text>
                    <div class="text-2xl font-semibold tracking-tight text-zinc-900">Plan {{ $licencia?->plan->etiqueta() ?? '—' }}</div>
                </div>
                <x-panel.estado-licencia :estado="$this->negocio->estadoLicencia()" />
            </div>
            @if ($licencia)
                <flux:text class="mt-3 text-sm">
                    Vigente hasta el <span class="font-medium text-zinc-900">{{ $licencia->fecha_fin->translatedFormat('d \d\e F \d\e Y') }}</span>
                    ({{ $licencia->diasRestantes() }} días).
                </flux:text>
            @endif
        </div>
    </div>

    {{-- Restaurantes --}}
    <section class="space-y-3" data-tour="restaurantes">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Tus restaurantes</flux:heading>
            <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('negocio.restaurantes.index')" wire:navigate>Administrar</flux:button>
        </div>

        @if ($this->restaurantes->isEmpty())
            <div class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-orange-300 bg-white px-6 py-12 text-center">
                <div class="flex size-12 items-center justify-center rounded-full bg-orange-50 text-orange-600">
                    <flux:icon name="building-storefront" variant="outline" class="size-6" />
                </div>
                <flux:heading>Registra tu primer restaurante</flux:heading>
                <flux:text class="max-w-sm">Después podrás asignarle un encargado y vendedores, y empezar a registrar ventas.</flux:text>
                <flux:button variant="primary" icon="plus" :href="route('negocio.restaurantes.crear')" wire:navigate>Nuevo restaurante</flux:button>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($this->restaurantes as $restaurante)
                    @php
                        $encargado = $restaurante->personal->first(fn ($u) => $u->hasRole('encargado'));
                        $vendedores = $restaurante->personal->filter(fn ($u) => $u->hasRole('vendedor'))->count();
                    @endphp
                    <a href="{{ route('negocio.restaurantes.editar', $restaurante) }}" wire:navigate
                       class="group flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-orange-300 hover:shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-zinc-900 group-hover:text-orange-600">{{ $restaurante->nombre }}</div>
                                <div class="truncate text-sm text-zinc-500">{{ $restaurante->direccion ?? 'Sin dirección' }}</div>
                            </div>
                            @unless ($restaurante->activo)
                                <flux:badge size="sm" color="zinc">Inactivo</flux:badge>
                            @endunless
                        </div>
                        <div class="grid grid-cols-3 gap-2 border-t border-zinc-100 pt-3 text-sm">
                            <div>
                                <div class="text-xs text-zinc-500">Horario</div>
                                <div class="text-zinc-800">{{ $restaurante->horario() ?? '—' }}</div>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs text-zinc-500">Encargado</div>
                                <div class="truncate text-zinc-800">{{ $encargado?->name ?? 'Sin asignar' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-zinc-500">Vendedores</div>
                                <div class="text-zinc-800">{{ $vendedores }}</div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Próximos módulos --}}
    <section class="space-y-3">
        <flux:heading size="lg">Próximamente</flux:heading>
        <div class="grid gap-4 md:grid-cols-2">
            <x-panel.modulo icono="banknotes" entrega="6" titulo="Gastos" descripcion="Gastos de cada restaurante." />
            <x-panel.modulo icono="chart-bar" entrega="7" titulo="Dashboard" descripcion="Velocímetro de gastos contra ventas." />
        </div>
    </section>

    <x-recorrido clave="negocio.inicio" :pasos="[
        ['titulo' => 'Bienvenido a tu negocio', 'descripcion' => 'Eres el administrador de tu negocio en Delidata. Desde aquí registras tus restaurantes y a tu personal. Te muestro lo básico.'],
        ['elemento' => '[data-tour=resumen]', 'titulo' => 'Tu resumen', 'descripcion' => 'Cuántos restaurantes llevas de los que permite tu plan, cuánto personal tienes y hasta cuándo está vigente tu suscripción.'],
        ['elemento' => '[data-tour=nuevo-restaurante]', 'titulo' => 'Paso 1: registra un restaurante', 'descripcion' => 'Empieza aquí. Cada local de tu negocio es un restaurante. Si llegas al límite de tu plan, el botón se desactiva.', 'lado' => 'left'],
        ['elemento' => '[data-tour=restaurantes]', 'titulo' => 'Tus restaurantes', 'descripcion' => 'Aquí aparecen con su horario, su encargado y cuántos vendedores tienen. Da clic en uno para editarlo.', 'lado' => 'top'],
        ['elemento' => '[data-tour=menu-productos]', 'titulo' => 'Paso 2: arma tu menú', 'descripcion' => 'En «Productos» creas tus categorías y productos, y eliges en qué restaurantes se vende cada uno.', 'lado' => 'right'],
        ['elemento' => '[data-tour=menu-personal]', 'titulo' => 'Paso 3: agrega a tu personal', 'descripcion' => 'En «Personal» creas las cuentas de tus encargados y vendedores y les asignas su restaurante.', 'lado' => 'right'],
    ]" />
</div>
