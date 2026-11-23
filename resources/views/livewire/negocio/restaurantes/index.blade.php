@php
    $limite = $this->negocio->limiteRestaurantes();
    $usados = $this->restaurantes->count();
@endphp

<div class="flex flex-col gap-6">
    <x-panel.encabezado
        titulo="Restaurantes"
        descripcion="Los locales de tu negocio. Cada uno tiene su propio encargado, vendedores, ventas y gastos."
    >
        <x-slot:acciones>
            <x-recorrido.boton />
            <x-panel.boton-nuevo-restaurante :disponible="$usados < $limite" />
        </x-slot:acciones>
    </x-panel.encabezado>

    {{-- Límite del plan --}}
    <div class="flex flex-col gap-3 rounded-xl border border-orange-200 bg-orange-50 p-4 sm:flex-row sm:items-center sm:justify-between" data-tour="limite">
        <div class="flex items-center gap-3">
            <flux:icon name="information-circle" variant="outline" class="size-5 text-orange-600" />
            <flux:text class="text-sm text-zinc-700">
                Usas <span class="font-semibold text-zinc-900">{{ $usados }} de {{ $limite }}</span>
                restaurantes de tu plan <span class="font-semibold text-zinc-900">{{ $this->negocio->licenciaActual()?->plan->etiqueta() }}</span>.
                @if ($usados >= $limite)
                    Para agregar más, pide a Delidata cambiar a un plan mayor.
                @endif
            </flux:text>
        </div>
        <div class="h-1.5 w-full overflow-hidden rounded-full bg-white sm:w-48">
            <div class="h-full rounded-full bg-orange-500" style="width: {{ $limite ? min(100, round($usados / $limite * 100)) : 0 }}%"></div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white px-5" data-tour="tabla">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Restaurante</flux:table.column>
                <flux:table.column>Horario</flux:table.column>
                <flux:table.column>Encargado</flux:table.column>
                <flux:table.column>Vendedores</flux:table.column>
                <flux:table.column>Activo</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->restaurantes as $restaurante)
                    <flux:table.row :key="$restaurante->id">
                        <flux:table.cell>
                            <a href="{{ route('negocio.restaurantes.editar', $restaurante) }}" wire:navigate class="group">
                                <div class="font-medium text-zinc-900 group-hover:text-orange-600">{{ $restaurante->nombre }}</div>
                                <div class="max-w-72 truncate text-xs text-zinc-500">{{ $restaurante->direccion ?? 'Sin dirección' }}</div>
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $restaurante->horario() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($nombre = $this->encargadoDe($restaurante))
                                {{ $nombre }}
                            @else
                                <span class="text-orange-600">Sin asignar</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:link :href="route('negocio.personal.index', ['restaurante' => $restaurante->id])" wire:navigate variant="subtle">
                                {{ $this->vendedoresDe($restaurante) }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:switch
                                :checked="$restaurante->activo"
                                wire:click="alternarActivo({{ $restaurante->id }})"
                                wire:confirm="{{ $restaurante->activo ? '¿Desactivar '.$restaurante->nombre.'? Su encargado y vendedores no podrán entrar.' : '¿Activar '.$restaurante->nombre.'?' }}"
                                aria-label="Activar o desactivar {{ $restaurante->nombre }}"
                            />
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Acciones" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" :href="route('negocio.restaurantes.editar', $restaurante)" wire:navigate>Editar</flux:menu.item>
                                    <flux:menu.item icon="user-group" :href="route('negocio.personal.index', ['restaurante' => $restaurante->id])" wire:navigate>Ver personal</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="eliminar({{ $restaurante->id }})"
                                        wire:confirm="¿Eliminar {{ $restaurante->nombre }}? Esta acción no se puede deshacer.">
                                        Eliminar
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <div class="flex flex-col items-center gap-2 py-10 text-center">
                                <flux:icon name="building-storefront" variant="outline" class="size-8 text-orange-500" />
                                <flux:text>Todavía no registras ningún restaurante.</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <x-recorrido clave="negocio.restaurantes" :pasos="[
        ['titulo' => 'Tus restaurantes', 'descripcion' => 'Aquí das de alta y administras los locales de tu negocio. Te explico cómo.'],
        ['elemento' => '[data-tour=limite]', 'titulo' => 'Límite de tu plan', 'descripcion' => 'Tu suscripción define cuántos restaurantes puedes tener. Esta barra muestra cuántos llevas.'],
        ['elemento' => '[data-tour=nuevo-restaurante]', 'titulo' => '1. Nuevo restaurante', 'descripcion' => 'Abre el formulario para registrar un local: nombre, dirección, teléfono y horario.', 'lado' => 'left'],
        ['elemento' => '[data-tour=tabla]', 'titulo' => '2. El listado', 'descripcion' => 'Cada fila muestra el horario, el encargado y cuántos vendedores tiene. Si dice «Sin asignar» en naranja, falta darle un encargado desde «Personal».', 'lado' => 'top'],
        ['elemento' => '[data-tour=tabla]', 'titulo' => '3. Activar, editar o eliminar', 'descripcion' => 'El interruptor desactiva un restaurante sin borrar nada (su personal no podrá entrar). En el menú «···» lo editas, ves su personal o lo eliminas si ya no tiene personal asignado.', 'lado' => 'top'],
    ]" />
</div>
