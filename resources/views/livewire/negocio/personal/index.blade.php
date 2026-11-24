<div class="flex flex-col gap-6">
    <x-panel.encabezado
        :titulo="$this->esEncargado() ? 'Vendedores' : 'Personal'"
        :descripcion="$this->esEncargado()
            ? 'Los vendedores de '.auth()->user()->restaurante?->nombre.'. Crea sus cuentas y desactívalas cuando ya no trabajen aquí.'
            : 'Encargados y vendedores de tus restaurantes. Cada cuenta entra solo al restaurante que le asignes.'"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
            <flux:button variant="primary" icon="user-plus" :href="$this->ruta('personal.crear')" wire:navigate data-tour="nuevo">
                {{ $this->esEncargado() ? 'Nuevo vendedor' : 'Agregar personal' }}
            </flux:button>
        </x-slot:acciones>
    </x-panel.encabezado>

    {{-- Filtros --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center" data-tour="filtros">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.400ms="buscar" icon="magnifying-glass" placeholder="Buscar por nombre o correo…" clearable />
        </div>

        @unless ($this->esEncargado())
            <div class="lg:w-56">
                <flux:select wire:model.live="restaurante">
                    <flux:select.option value="">Todos los restaurantes</flux:select.option>
                    @foreach ($this->restaurantesDisponibles() as $opcion)
                        <flux:select.option :value="$opcion->id">{{ $opcion->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="lg:w-44">
                <flux:select wire:model.live="rol">
                    <flux:select.option value="">Todos los puestos</flux:select.option>
                    @foreach ($this->rolesAsignables() as $opcion)
                        <flux:select.option :value="$opcion->value">{{ $opcion->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endunless

        @if ($buscar !== '' || $restaurante !== '' || $rol !== '')
            <flux:button variant="ghost" icon="x-mark" wire:click="limpiarFiltros">Limpiar</flux:button>
        @endif
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white px-5" data-tour="tabla">
        <flux:table :paginate="$this->personal">
            <flux:table.columns>
                <flux:table.column>Nombre</flux:table.column>
                <flux:table.column>Puesto</flux:table.column>
                @unless ($this->esEncargado())
                    <flux:table.column>Restaurante</flux:table.column>
                @endunless
                <flux:table.column>Activo</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->personal as $persona)
                    @php($puesto = $persona->rolPrincipal())
                    <flux:table.row :key="$persona->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" :name="$persona->name" :initials="$persona->initials()" color="orange" />
                                <div class="min-w-0">
                                    <div @class(['truncate font-medium', 'text-zinc-900' => $persona->activo, 'text-zinc-400 line-through' => ! $persona->activo])>{{ $persona->name }}</div>
                                    <div class="truncate text-xs text-zinc-500">{{ $persona->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$puesto === \App\Enums\Rol::Encargado ? 'orange' : 'zinc'">{{ $puesto?->etiqueta() }}</flux:badge>
                        </flux:table.cell>
                        @unless ($this->esEncargado())
                            <flux:table.cell>
                                @if ($persona->restaurante)
                                    {{ $persona->restaurante->nombre }}
                                @else
                                    <span class="text-orange-600">Sin asignar</span>
                                @endif
                            </flux:table.cell>
                        @endunless
                        <flux:table.cell>
                            <x-panel.interruptor
                                :activo="$persona->activo"
                                wire:click="alternarActivo({{ $persona->id }})"
                                wire:confirm="{{ $persona->activo ? '¿Desactivar a '.$persona->name.'? Ya no podrá iniciar sesión.' : '¿Reactivar a '.$persona->name.'?' }}"
                                accesible="Activar o desactivar a {{ $persona->name }}"
                            />
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" :href="$this->ruta('personal.editar', $persona)" wire:navigate>
                                Editar
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <div class="flex flex-col items-center gap-2 py-10 text-center">
                                <flux:icon name="user-group" variant="outline" class="size-8 text-orange-500" />
                                <flux:text>{{ $buscar !== '' || $restaurante !== '' || $rol !== '' ? 'Nadie coincide con esos filtros.' : 'Todavía no registras a nadie.' }}</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    @if ($this->esEncargado())
        <x-recorrido clave="encargado.vendedores" :pasos="[
            ['titulo' => 'Tus vendedores', 'descripcion' => 'Aquí administras las cuentas de los vendedores de tu restaurante.'],
            ['elemento' => '[data-tour=nuevo]', 'titulo' => '1. Nuevo vendedor', 'descripcion' => 'Crea la cuenta con su nombre, correo y una contraseña. Quedará asignado automáticamente a tu restaurante.', 'lado' => 'left'],
            ['elemento' => '[data-tour=filtros]', 'titulo' => '2. Buscar', 'descripcion' => 'Encuentra a un vendedor por su nombre o correo.'],
            ['elemento' => '[data-tour=tabla]', 'titulo' => '3. Activar o editar', 'descripcion' => 'Si alguien deja de trabajar, apaga su interruptor: ya no podrá entrar, pero sus ventas se conservan. Con «Editar» cambias su contraseña.', 'lado' => 'top'],
        ]" />
    @else
        <x-recorrido clave="negocio.personal" :pasos="[
            ['titulo' => 'Tu personal', 'descripcion' => 'Aquí creas las cuentas de tus encargados y vendedores. Cada uno solo ve el restaurante que le asignes.'],
            ['elemento' => '[data-tour=nuevo]', 'titulo' => '1. Agregar personal', 'descripcion' => 'Captura nombre, correo y contraseña, elige si es encargado o vendedor y en qué restaurante trabaja.', 'lado' => 'left'],
            ['elemento' => '[data-tour=filtros]', 'titulo' => '2. Buscar y filtrar', 'descripcion' => 'Busca por nombre o correo, o filtra por restaurante y por puesto.'],
            ['elemento' => '[data-tour=tabla]', 'titulo' => '3. El listado', 'descripcion' => 'Ves el puesto (los encargados en naranja) y su restaurante. El interruptor desactiva la cuenta sin borrar su historial; con «Editar» lo cambias de restaurante o de contraseña.', 'lado' => 'top'],
        ]" />
    @endif
</div>
