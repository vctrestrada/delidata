@php
    $i = $this->indicadores;
    $zona = $this->zona((float) $i['proporcion']);
    // Llave que cambia con los filtros: obliga a redibujar las gráficas.
    $llave = md5($desde.$hasta.$restaurante.$rankingPor.$version);
@endphp

<div class="flex flex-col gap-6">
    <x-panel.encabezado
        titulo="Dashboard"
        :descripcion="$this->esEncargado()
            ? 'Cómo va '.auth()->user()->restaurante?->nombre.': ventas contra gastos.'
            : 'Cómo va tu negocio: ventas contra gastos, tus mejores productos y tus horas pico.'"
    >
        <x-slot:acciones>
            <x-panel.en-vivo />
            <x-recorrido.boton />
        </x-slot:acciones>
    </x-panel.encabezado>

    {{-- Filtros --}}
    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 lg:flex-row lg:items-end" data-tour="filtros">
        <div class="lg:w-52">
            <flux:select wire:model.live="periodo" label="Periodo" size="sm">
                <flux:select.option value="7">Últimos 7 días</flux:select.option>
                <flux:select.option value="30">Últimos 30 días</flux:select.option>
                <flux:select.option value="mes">Este mes</flux:select.option>
                <flux:select.option value="mes-pasado">Mes pasado</flux:select.option>
                <flux:select.option value="personalizado">Personalizado</flux:select.option>
            </flux:select>
        </div>
        <div class="grid grid-cols-2 gap-3 lg:w-80">
            <flux:input type="date" wire:model.live="desde" label="Desde" size="sm" :max="today()->toDateString()" />
            <flux:input type="date" wire:model.live="hasta" label="Hasta" size="sm" :max="today()->toDateString()" />
        </div>
        @unless ($this->esEncargado())
            <div class="lg:w-60">
                <flux:select wire:model.live="restaurante" label="Restaurante" size="sm">
                    <flux:select.option value="">Todo el negocio</flux:select.option>
                    @foreach ($this->restaurantesDisponibles() as $opcion)
                        <flux:select.option :value="$opcion->id">{{ $opcion->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endunless
        <div class="flex-1 text-right text-xs text-zinc-500" wire:loading.remove>
            {{ \Illuminate\Support\Carbon::parse($desde)->translatedFormat('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($hasta)->translatedFormat('d M Y') }}
        </div>
        <div class="flex-1 text-right text-xs text-orange-600" wire:loading>Actualizando…</div>
    </div>

    {{-- Velocímetro + indicadores --}}
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="flex flex-col items-center rounded-xl border border-zinc-200 bg-white p-6" data-tour="velocimetro">
            <div class="self-start">
                <flux:heading size="lg">Velocímetro de gastos</flux:heading>
                <flux:text class="text-sm">Qué porcentaje de lo que vendes se va en gastos.</flux:text>
            </div>

            <div class="w-full max-w-sm" wire:key="velocimetro-{{ $llave }}">
                <div x-data="grafica(@js($this->graficaVelocimetro))" wire:ignore class="-mb-4 mt-4"></div>
            </div>

            <div class="flex w-full max-w-sm justify-between px-2 text-[11px] text-zinc-400">
                <span>0%</span><span>50%</span><span>100%</span>
            </div>

            <div class="mt-4 text-center">
                <div class="text-lg font-semibold {{ $zona['clase'] }}">{{ $zona['texto'] }}</div>
                <flux:text class="max-w-xs text-sm">{{ $zona['mensaje'] }}</flux:text>
            </div>

            <div class="mt-4 flex w-full max-w-sm gap-1 text-[10px] font-medium uppercase tracking-wide">
                <div class="flex-[60] rounded-l-full bg-green-100 py-1 text-center text-green-700">Sano &lt; {{ \App\Livewire\Dashboard\Panel::ZONA_PRECAUCION }}%</div>
                <div class="flex-[25] bg-orange-100 py-1 text-center text-orange-700">Precaución</div>
                <div class="flex-[15] rounded-r-full bg-red-100 py-1 text-center text-red-700">Peligro</div>
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 xl:col-span-2" data-tour="indicadores">
            <div class="rounded-xl border border-orange-200 bg-orange-50 p-5">
                <flux:text class="text-sm">Ventas</flux:text>
                <div class="text-3xl font-semibold tracking-tight text-zinc-900">${{ number_format($i['ventas'], 2) }}</div>
                <x-panel.variacion :valor="$i['variacionVentas']" />
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5">
                <flux:text class="text-sm">Gastos</flux:text>
                <div class="text-3xl font-semibold tracking-tight text-zinc-900">${{ number_format($i['gastos'], 2) }}</div>
                <x-panel.variacion :valor="$i['variacionGastos']" inverso />
            </div>
            <div @class(['rounded-xl border p-5', 'border-green-200 bg-green-50' => $i['utilidad'] >= 0, 'border-red-200 bg-red-50' => $i['utilidad'] < 0])>
                <flux:text class="text-sm">{{ $i['utilidad'] >= 0 ? 'Utilidad' : 'Pérdida' }}</flux:text>
                <div @class(['text-3xl font-semibold tracking-tight', 'text-green-700' => $i['utilidad'] >= 0, 'text-red-700' => $i['utilidad'] < 0])>
                    ${{ number_format(abs($i['utilidad']), 2) }}
                </div>
                <x-panel.variacion :valor="$i['variacionUtilidad']" />
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5">
                <flux:text class="text-sm">Ticket promedio</flux:text>
                <div class="text-3xl font-semibold tracking-tight text-zinc-900">${{ number_format($i['ticketPromedio'], 2) }}</div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs text-zinc-500">{{ number_format($i['tickets']) }} ventas</span>
                    <x-panel.variacion :valor="$i['variacionTickets']" />
                </div>
            </div>
        </div>
    </div>

    {{-- Tendencia --}}
    <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="tendencia">
        <flux:heading size="lg">Ventas y gastos por día</flux:heading>
        <flux:text class="text-sm">Cuando la línea gris (gastos) sube por encima de la naranja (ventas), ese día perdiste dinero; suele pasar en días de renta o nómina.</flux:text>
        <div wire:key="tendencia-{{ $llave }}" class="mt-2">
            <div x-data="grafica(@js($this->graficaTendencia))" wire:ignore></div>
        </div>
    </section>

    {{-- Comparación por restaurante --}}
    @if (! $this->esEncargado() && $this->porRestaurante->count() > 1)
        <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="restaurantes">
            <flux:heading size="lg">Comparación entre restaurantes</flux:heading>
            <flux:text class="text-sm">¿Cuál de tus locales deja más dinero?</flux:text>

            <div class="mt-4 grid gap-6 lg:grid-cols-[1fr_auto]">
                <div wire:key="restaurantes-{{ $llave }}">
                    <div x-data="grafica(@js($this->graficaRestaurantes))" wire:ignore></div>
                </div>
                <div class="grid content-start gap-3 sm:grid-cols-2 lg:w-80 lg:grid-cols-1">
                    @foreach ($this->porRestaurante as $fila)
                        @php($z = $this->zona((float) $fila['proporcion']))
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-100 p-3" wire:key="vr-{{ $fila['id'] }}-{{ $llave }}">
                            <div class="w-24 shrink-0">
                                <div x-data="grafica(@js($this->velocimetro((float) $fila['proporcion'], 110)))" wire:ignore></div>
                            </div>
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-zinc-900">{{ $fila['nombre'] }}</div>
                                <div class="text-xs {{ $z['clase'] }}">{{ $z['texto'] }}</div>
                                <div class="text-xs text-zinc-500">Utilidad ${{ number_format($fila['utilidad'], 0) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        {{-- Productos --}}
        <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="productos">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <flux:heading size="lg">Productos más vendidos</flux:heading>
                    <flux:text class="text-sm">Top 10 del periodo.</flux:text>
                </div>
                <flux:radio.group wire:model.live="rankingPor" variant="segmented" size="sm">
                    <flux:radio value="importe" label="Por dinero" />
                    <flux:radio value="piezas" label="Por piezas" />
                </flux:radio.group>
            </div>
            <div wire:key="productos-{{ $llave }}" class="mt-2">
                <div x-data="grafica(@js($this->graficaProductos))" wire:ignore></div>
            </div>
        </section>

        <div class="grid gap-6">
            {{-- Horas pico --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="horas">
                <flux:heading size="lg">Horas pico</flux:heading>
                <flux:text class="text-sm">Número de ventas por hora. Las barras naranjas son tus horas más fuertes: ten más personal ahí.</flux:text>
                <div wire:key="horas-{{ $llave }}" class="mt-2">
                    <div x-data="grafica(@js($this->graficaHoras))" wire:ignore></div>
                </div>
            </section>

            {{-- Formas de pago --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="metodos">
                <flux:heading size="lg">Formas de pago</flux:heading>
                <div wire:key="metodos-{{ $llave }}" class="mt-2">
                    <div x-data="grafica(@js($this->graficaMetodos))" wire:ignore></div>
                </div>
            </section>
        </div>
    </div>

    <x-recorrido :clave="$this->prefijo().'.dashboard'" :pasos="[
        ['titulo' => 'Tu dashboard', 'descripcion' => 'Aquí ves de un vistazo si tu negocio está ganando dinero. Te explico cada gráfica.'],
        ['elemento' => '[data-tour=en-vivo]', 'titulo' => 'En vivo', 'descripcion' => 'Cuando este indicador está en verde, la pantalla se actualiza sola en cuanto alguien cobra o registra un gasto. No necesitas recargar.', 'lado' => 'left'],
        ['elemento' => '[data-tour=filtros]', 'titulo' => '1. Periodo' . ($this->esEncargado() ? '' : ' y restaurante'), 'descripcion' => 'Elige qué días quieres analizar. ' . ($this->esEncargado() ? '' : 'También puedes ver un solo restaurante o todo el negocio. ') . 'Todos los números se comparan contra el periodo anterior del mismo tamaño.'],
        ['elemento' => '[data-tour=velocimetro]', 'titulo' => '2. El velocímetro', 'descripcion' => 'Muestra qué parte de tus ventas se va en gastos. Verde (menos de 60%): sano. Naranja (60 a 85%): cuidado. Rojo (más de 85%): casi no te queda nada.', 'lado' => 'right'],
        ['elemento' => '[data-tour=indicadores]', 'titulo' => '3. Indicadores', 'descripcion' => 'Ventas, gastos, utilidad y ticket promedio. La flecha verde o roja te dice si vas mejor o peor que en el periodo anterior.', 'lado' => 'left'],
        ['elemento' => '[data-tour=tendencia]', 'titulo' => '4. Día por día', 'descripcion' => 'Ventas (naranja) contra gastos (gris). Pasa el cursor sobre la gráfica para ver los montos de cada día.', 'lado' => 'top'],
        ['elemento' => '[data-tour=restaurantes]', 'titulo' => '5. Tus restaurantes', 'descripcion' => 'Compara ventas, gastos y utilidad de cada local, cada uno con su propio velocímetro.', 'lado' => 'top'],
        ['elemento' => '[data-tour=productos]', 'titulo' => '6. Lo que más se vende', 'descripcion' => 'Cambia entre «Por dinero» y «Por piezas»: a veces lo que más se vende no es lo que más dinero deja.', 'lado' => 'top'],
        ['elemento' => '[data-tour=horas]', 'titulo' => '7. Horas pico', 'descripcion' => 'Te ayuda a decidir cuándo tener más vendedores y cuándo preparar más producto.', 'lado' => 'left'],
    ]" />
</div>
