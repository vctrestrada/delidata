@php
    $actual = $restaurante->licenciaActual();
    $programada = $restaurante->licenciaProgramada();
    $estado = $restaurante->estadoLicencia();
@endphp

<div class="flex flex-col gap-6">
    <x-panel.encabezado
        :titulo="$restaurante->nombre"
        :descripcion="'Registrado el '.$restaurante->created_at->translatedFormat('d \d\e F \d\e Y')"
        :regresar="route('admin.restaurantes.index')"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
            <flux:button icon="pencil-square" :href="route('admin.restaurantes.editar', $restaurante)" wire:navigate data-tour="editar">
                Editar datos
            </flux:button>
            <flux:button variant="primary" icon="arrow-path" wire:click="abrirRenovacion" data-tour="renovar">
                Renovar licencia
            </flux:button>
        </x-slot:acciones>
    </x-panel.encabezado>

    @unless ($restaurante->activo)
        <flux:callout icon="no-symbol" color="zinc" heading="Restaurante desactivado">
            <flux:callout.text>Su personal no puede entrar a Delidata aunque la licencia esté vigente.</flux:callout.text>
        </flux:callout>
    @endunless

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Licencia actual --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="licencia-actual">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <flux:text class="text-sm">Licencia actual</flux:text>
                        <flux:heading size="xl">{{ $actual?->plan->etiqueta() ?? 'Sin licencia' }}</flux:heading>
                    </div>
                    <x-panel.estado-licencia :estado="$estado" size="lg" />
                </div>

                @if ($actual)
                    <div class="mt-6 grid gap-4 sm:grid-cols-3">
                        <div>
                            <div class="text-xs uppercase tracking-wide text-zinc-500">Vigencia</div>
                            <div class="mt-1 text-sm font-medium text-zinc-900">
                                {{ $actual->fecha_inicio->translatedFormat('d M Y') }} – {{ $actual->fecha_fin->translatedFormat('d M Y') }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wide text-zinc-500">Sucursales permitidas</div>
                            <div class="mt-1 text-sm font-medium text-zinc-900">{{ $actual->max_sucursales }}</div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wide text-zinc-500">Precio del periodo</div>
                            <div class="mt-1 text-sm font-medium text-zinc-900">${{ number_format($actual->precio, 2) }}</div>
                        </div>
                    </div>

                    <div class="mt-6 space-y-2">
                        <div class="flex justify-between text-xs text-zinc-500">
                            <span>Periodo consumido</span>
                            <span>{{ $actual->diasRestantes() }} días restantes</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-zinc-100">
                            <div class="h-full rounded-full bg-orange-500 transition-all" style="width: {{ $actual->porcentajeConsumido() }}%"></div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2 border-t border-zinc-100 pt-4" data-tour="acciones-licencia">
                        @if ($actual->estado === \App\Enums\EstadoLicencia::Activa)
                            <flux:button size="sm" icon="pause-circle" wire:click="suspender({{ $actual->id }})"
                                wire:confirm="¿Suspender la licencia? El personal de {{ $restaurante->nombre }} no podrá entrar hasta que la reactives.">
                                Suspender
                            </flux:button>
                        @elseif ($actual->estado === \App\Enums\EstadoLicencia::Suspendida)
                            <flux:button size="sm" variant="primary" icon="play-circle" wire:click="reactivar({{ $actual->id }})">
                                Reactivar
                            </flux:button>
                        @endif

                        @if ($actual->estado !== \App\Enums\EstadoLicencia::Cancelada)
                            <flux:button size="sm" variant="ghost" icon="x-circle" wire:click="cancelar({{ $actual->id }})"
                                wire:confirm="¿Cancelar esta licencia? Esta acción no se puede deshacer; tendrás que registrar una renovación.">
                                Cancelar licencia
                            </flux:button>
                        @endif
                    </div>
                @else
                    <flux:text class="mt-4">Este restaurante no tiene una licencia en curso. Usa «Renovar licencia» para asignarle una.</flux:text>
                @endif

                @if ($programada)
                    <div class="mt-4 flex items-center gap-2 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        <flux:icon name="calendar-days" variant="micro" />
                        Renovación {{ $programada->plan->etiqueta() }} programada del {{ $programada->fecha_inicio->translatedFormat('d M Y') }} al {{ $programada->fecha_fin->translatedFormat('d M Y') }}.
                    </div>
                @endif
            </section>

            {{-- Renovación --}}
            @if ($mostrarRenovacion)
                <section class="rounded-xl border border-orange-200 bg-orange-50/60 p-6" data-tour="form-renovacion" wire:transition>
                    <div class="mb-5 flex items-start justify-between gap-3">
                        <div>
                            <flux:heading size="lg">Renovar licencia</flux:heading>
                            <flux:text class="text-sm">
                                Empezará el <span class="font-medium text-orange-700">{{ $this->inicioRenovacion()->translatedFormat('d \d\e F \d\e Y') }}</span>
                                y terminará el <span class="font-medium text-orange-700">{{ $this->finRenovacion()->translatedFormat('d \d\e F \d\e Y') }}</span>.
                            </flux:text>
                        </div>
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="cerrarRenovacion" aria-label="Cerrar" />
                    </div>

                    <form wire:submit="renovar" class="space-y-5">
                        <flux:radio.group wire:model.live="plan" label="Plan" variant="cards" class="max-sm:flex-col">
                            @foreach (\App\Enums\PlanLicencia::cases() as $opcion)
                                <flux:radio :value="$opcion->value" :label="$opcion->etiqueta()" :description="$opcion->descripcion()" />
                            @endforeach
                        </flux:radio.group>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:select wire:model.live="meses" label="Duración">
                                <flux:select.option value="1">1 mes</flux:select.option>
                                <flux:select.option value="3">3 meses</flux:select.option>
                                <flux:select.option value="6">6 meses</flux:select.option>
                                <flux:select.option value="12">12 meses</flux:select.option>
                                <flux:select.option value="24">24 meses</flux:select.option>
                            </flux:select>
                            <flux:input wire:model="precio" label="Precio total" icon="currency-dollar" inputmode="decimal" />
                        </div>

                        <flux:textarea wire:model="notas" label="Notas (opcional)" rows="2" placeholder="Ej. Pago por transferencia, folio 1234" />

                        <div class="flex justify-end gap-2">
                            <flux:button variant="ghost" wire:click="cerrarRenovacion">Cancelar</flux:button>
                            <flux:button type="submit" variant="primary" icon="check">Confirmar renovación</flux:button>
                        </div>
                    </form>
                </section>
            @endif

            {{-- Historial --}}
            <section class="rounded-xl border border-zinc-200 bg-white px-5 pt-5" data-tour="historial">
                <flux:heading size="lg">Historial de licencias</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Plan</flux:table.column>
                        <flux:table.column>Periodo</flux:table.column>
                        <flux:table.column>Precio</flux:table.column>
                        <flux:table.column>Estado</flux:table.column>
                        <flux:table.column>Notas</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($restaurante->licencias as $licencia)
                            <flux:table.row :key="'licencia-'.$licencia->id">
                                <flux:table.cell class="font-medium text-zinc-900">{{ $licencia->plan->etiqueta() }}</flux:table.cell>
                                <flux:table.cell>{{ $licencia->fecha_inicio->translatedFormat('d M Y') }} – {{ $licencia->fecha_fin->translatedFormat('d M Y') }}</flux:table.cell>
                                <flux:table.cell>${{ number_format($licencia->precio, 2) }}</flux:table.cell>
                                <flux:table.cell><x-panel.estado-licencia :estado="$licencia->estadoActual()" /></flux:table.cell>
                                <flux:table.cell class="max-w-56 truncate text-zinc-500">{{ $licencia->notas ?? '—' }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="py-6 text-center text-zinc-500">Sin licencias registradas.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </section>
        </div>

        {{-- Columna lateral --}}
        <div class="space-y-6">
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="dueno">
                <flux:text class="text-sm">Dueño</flux:text>
                @if ($restaurante->dueno)
                    <div class="mt-3 flex items-center gap-3">
                        <flux:avatar :name="$restaurante->dueno->name" :initials="$restaurante->dueno->initials()" color="orange" />
                        <div class="min-w-0">
                            <div class="truncate font-medium text-zinc-900">{{ $restaurante->dueno->name }}</div>
                            <div class="truncate text-sm text-zinc-500">{{ $restaurante->dueno->email }}</div>
                        </div>
                    </div>
                @else
                    <flux:text class="mt-2">Sin dueño asignado.</flux:text>
                @endif

                <div class="mt-5 flex items-center justify-between border-t border-zinc-100 pt-4 text-sm">
                    <span class="text-zinc-500">Usuarios del restaurante</span>
                    <span class="font-medium text-zinc-900">{{ $restaurante->usuarios_count }}</span>
                </div>
            </section>

            <section class="space-y-3 rounded-xl border border-zinc-200 bg-white p-6 text-sm">
                <flux:text class="text-sm">Datos fiscales y contacto</flux:text>
                <dl class="space-y-2">
                    <div class="flex justify-between gap-4"><dt class="text-zinc-500">RFC</dt><dd class="text-zinc-900">{{ $restaurante->rfc ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-zinc-500">Teléfono</dt><dd class="text-zinc-900">{{ $restaurante->telefono ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-zinc-500">Correo</dt><dd class="truncate text-zinc-900">{{ $restaurante->correo ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-zinc-500">Dirección</dt><dd class="text-right text-zinc-900">{{ $restaurante->direccion ?? '—' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="activo">
                <flux:switch
                    :checked="$restaurante->activo"
                    wire:click="alternarActivo"
                    wire:confirm="{{ $restaurante->activo ? '¿Desactivar el restaurante? Su personal ya no podrá entrar.' : '¿Activar el restaurante?' }}"
                    label="Restaurante activo"
                    description="Apágalo para bloquear la entrada de todo su personal sin borrar sus datos."
                />
            </section>
        </div>
    </div>

    <x-recorrido clave="admin.restaurantes.detalle" :pasos="[
        ['titulo' => 'Detalle del restaurante', 'descripcion' => 'Aquí ves todo sobre un restaurante y administras su licencia. Te explico cada parte.'],
        ['elemento' => '[data-tour=licencia-actual]', 'titulo' => 'Licencia actual', 'descripcion' => 'Plan contratado, fechas, sucursales permitidas y una barra con el avance del periodo. La etiqueta de la derecha indica si está activa, por vencer, vencida o suspendida.'],
        ['elemento' => '[data-tour=acciones-licencia]', 'titulo' => 'Suspender o cancelar', 'descripcion' => '«Suspender» bloquea temporalmente el acceso (por ejemplo, por falta de pago) y luego puedes reactivarla. «Cancelar» la da por terminada de forma definitiva.', 'lado' => 'top'],
        ['elemento' => '[data-tour=renovar]', 'titulo' => 'Renovar', 'descripcion' => 'Abre el formulario de renovación. Si la licencia sigue vigente, la nueva empieza justo al día siguiente de que termine la actual, así el restaurante no pierde días.', 'lado' => 'left'],
        ['elemento' => '[data-tour=historial]', 'titulo' => 'Historial', 'descripcion' => 'Todas las licencias que ha tenido el restaurante, con precio y notas. Útil para revisar pagos anteriores.', 'lado' => 'top'],
        ['elemento' => '[data-tour=dueno]', 'titulo' => 'Dueño y usuarios', 'descripcion' => 'La cuenta del dueño y cuántos usuarios tiene el restaurante. Para cambiar sus datos o contraseña usa «Editar datos».', 'lado' => 'left'],
        ['elemento' => '[data-tour=activo]', 'titulo' => 'Activar o desactivar', 'descripcion' => 'Interruptor general: si lo apagas, nadie del restaurante puede entrar, aunque la licencia esté vigente.', 'lado' => 'left'],
    ]" />
</div>
