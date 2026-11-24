<div class="flex flex-col gap-6">
    <x-panel.encabezado
        titulo="Suscripciones"
        descripcion="Todos los negocios que usan Delidata, con su dueño y el estado de su licencia."
    >
        <x-slot:acciones>
            <x-recorrido.boton />
            <flux:button variant="primary" icon="plus" :href="route('admin.suscripciones.crear')" wire:navigate data-tour="nuevo">
                Nueva suscripción
            </flux:button>
        </x-slot:acciones>
    </x-panel.encabezado>

    {{-- Filtros --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center" data-tour="filtros">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.400ms="buscar"
                icon="magnifying-glass"
                placeholder="Buscar por negocio, RFC, dueño o correo…"
                clearable
            />
        </div>

        <div class="sm:w-56">
            <flux:select wire:model.live="estado">
                <flux:select.option value="">Todos los estados</flux:select.option>
                @foreach (\App\Enums\EstadoLicencia::filtrables() as $opcion)
                    <flux:select.option :value="$opcion->value">{{ $opcion->etiqueta() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @if ($buscar !== '' || $estado !== '')
            <flux:button variant="ghost" icon="x-mark" wire:click="limpiarFiltros">Limpiar</flux:button>
        @endif
    </div>

    {{-- Tabla --}}
    <div class="rounded-xl border border-zinc-200 bg-white px-5" data-tour="tabla">
        <flux:table :paginate="$this->negocios">
            <flux:table.columns>
                <flux:table.column>Negocio</flux:table.column>
                <flux:table.column>Dueño</flux:table.column>
                <flux:table.column>Plan</flux:table.column>
                <flux:table.column>Vigencia</flux:table.column>
                <flux:table.column>Licencia</flux:table.column>
                <flux:table.column>Activo</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->negocios as $negocio)
                    @php($licencia = $negocio->licenciaActual())
                    <flux:table.row :key="$negocio->id">
                        <flux:table.cell>
                            <a href="{{ route('admin.suscripciones.detalle', $negocio) }}" wire:navigate class="group">
                                <div class="font-medium text-zinc-900 group-hover:text-orange-600">{{ $negocio->nombre }}</div>
                                <div class="text-xs text-zinc-500">{{ $negocio->rfc ?? 'Sin RFC' }}</div>
                            </a>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-zinc-800">{{ $negocio->dueno?->name ?? '—' }}</div>
                            <div class="text-xs text-zinc-500">{{ $negocio->dueno?->email }}</div>
                        </flux:table.cell>

                        <flux:table.cell>{{ $licencia?->plan->etiqueta() ?? '—' }}</flux:table.cell>

                        <flux:table.cell>
                            @if ($licencia)
                                <div class="text-sm text-zinc-800">{{ $licencia->fecha_fin->translatedFormat('d M Y') }}</div>
                                <div class="text-xs text-zinc-500">
                                    {{ $licencia->diasRestantes() > 0 ? $licencia->diasRestantes().' días restantes' : 'Terminó' }}
                                </div>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <x-panel.estado-licencia :estado="$negocio->estadoLicencia()" />
                        </flux:table.cell>

                        <flux:table.cell>
                            <x-panel.interruptor
                                :activo="$negocio->activo"
                                wire:click="alternarActivo({{ $negocio->id }})"
                                wire:confirm="{{ $negocio->activo ? '¿Desactivar '.$negocio->nombre.'? Su personal ya no podrá entrar al sistema.' : '¿Activar '.$negocio->nombre.'?' }}"
                                accesible="Activar o desactivar {{ $negocio->nombre }}"
                            />
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Acciones" />
                                <flux:menu>
                                    <flux:menu.item icon="eye" :href="route('admin.suscripciones.detalle', $negocio)" wire:navigate>
                                        Ver detalle y licencias
                                    </flux:menu.item>
                                    <flux:menu.item icon="pencil-square" :href="route('admin.suscripciones.editar', $negocio)" wire:navigate>
                                        Editar datos
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <div class="flex flex-col items-center gap-2 py-10 text-center">
                                <flux:icon name="building-storefront" variant="outline" class="size-8 text-orange-500" />
                                @if ($buscar !== '' || $estado !== '')
                                    <flux:text>No hay negocios con esos filtros.</flux:text>
                                @else
                                    <flux:text>Todavía no registras ningún negocio.</flux:text>
                                    <flux:button size="sm" variant="primary" icon="plus" :href="route('admin.suscripciones.crear')" wire:navigate>
                                        Registrar la primera
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <x-recorrido clave="admin.suscripciones" :pasos="[
        ['titulo' => 'Administración de suscripciones', 'descripcion' => 'En esta pantalla ves y administras todos los negocios de la plataforma. Vamos paso a paso.'],
        ['elemento' => '[data-tour=nuevo]', 'titulo' => '1. Registrar un negocio', 'descripcion' => 'Empieza aquí. Abre el formulario donde capturas los datos del negocio, la cuenta de su dueño y su primera licencia.', 'lado' => 'left'],
        ['elemento' => '[data-tour=filtros]', 'titulo' => '2. Buscar y filtrar', 'descripcion' => 'Escribe el nombre del negocio, su RFC o el nombre o correo del dueño. Con el selector de la derecha filtras por estado de licencia, por ejemplo «Por vencer».'],
        ['elemento' => '[data-tour=tabla]', 'titulo' => '3. El listado', 'descripcion' => 'Cada fila muestra el plan, cuándo vence la licencia y su estado. Da clic en el nombre del negocio para ver su detalle, renovar o suspender su licencia.', 'lado' => 'top'],
        ['elemento' => '[data-tour=tabla]', 'titulo' => '4. Activar o desactivar', 'descripcion' => 'El interruptor «Activo» bloquea o permite la entrada de todo el personal del negocio sin borrar sus datos. El menú «···» de cada fila tiene accesos directos para ver o editar.', 'lado' => 'top'],
    ]" />
</div>
