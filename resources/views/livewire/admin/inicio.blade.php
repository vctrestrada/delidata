<div class="flex flex-col gap-6">
    <x-panel.encabezado
        titulo="Hola, {{ auth()->user()->name }}"
        descripcion="Así va la plataforma: negocios registrados y estado de sus licencias."
    >
        <x-slot:acciones>
            <x-recorrido.boton />
            <flux:button variant="primary" icon="plus" :href="route('admin.suscripciones.crear')" wire:navigate data-tour="nuevo">
                Nueva suscripción
            </flux:button>
        </x-slot:acciones>
    </x-panel.encabezado>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" data-tour="resumen">
        <x-panel.estadistica titulo="Suscripciones" :valor="$this->resumen['total']" icono="building-storefront"
            detalle="Registrados en la plataforma" />
        <x-panel.estadistica titulo="Licencias vigentes" :valor="$this->resumen['vigentes']" icono="check-badge"
            detalle="Pueden usar el sistema hoy" />
        <x-panel.estadistica titulo="Por vencer" :valor="$this->resumen['porVencer']" icono="clock" :destacado="$this->resumen['porVencer'] > 0"
            detalle="Vencen en {{ \App\Enums\EstadoLicencia::DIAS_AVISO }} días o menos" />
        <x-panel.estadistica titulo="Ingreso de licencias vigentes" :valor="'$'.number_format($this->resumen['ingresoVigente'], 2)" icono="banknotes"
            detalle="Suma de los periodos activos" />
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white" data-tour="atencion">
        <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
            <div>
                <flux:heading size="lg">Requieren atención</flux:heading>
                <flux:text class="text-sm">Licencias por vencer, vencidas, suspendidas o negocios sin licencia.</flux:text>
            </div>
            <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('admin.suscripciones.index')" wire:navigate>
                Ver todos
            </flux:button>
        </div>

        @forelse ($this->atencion as $negocio)
            @php($licencia = $negocio->licenciaActual())
            <a href="{{ route('admin.suscripciones.detalle', $negocio) }}" wire:navigate
               class="flex items-center justify-between gap-4 border-b border-zinc-100 px-5 py-3 last:border-0 hover:bg-orange-50/50">
                <div class="min-w-0">
                    <div class="truncate font-medium text-zinc-900">{{ $negocio->nombre }}</div>
                    <div class="truncate text-sm text-zinc-500">{{ $negocio->dueno?->name ?? 'Sin dueño' }}</div>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    @if ($licencia)
                        <span class="hidden text-sm text-zinc-500 sm:inline">Vence {{ $licencia->fecha_fin->translatedFormat('d M Y') }}</span>
                    @endif
                    <x-panel.estado-licencia :estado="$negocio->estadoLicencia()" />
                </div>
            </a>
        @empty
            <div class="flex flex-col items-center gap-2 px-5 py-10 text-center">
                <flux:icon name="check-circle" variant="outline" class="size-8 text-orange-500" />
                <flux:text>Todo en orden: no hay licencias que requieran atención.</flux:text>
            </div>
        @endforelse
    </div>

    <x-recorrido clave="admin.inicio" :pasos="[
        ['titulo' => 'Bienvenido a Delidata', 'descripcion' => 'Este es tu panel de super administrador. Desde aquí controlas qué negocios usan la plataforma y el estado de sus licencias. Te muestro cómo funciona en 4 pasos.'],
        ['elemento' => '[data-tour=resumen]', 'titulo' => 'Resumen de la plataforma', 'descripcion' => 'Cuántos negocios tienes, cuántos pueden usar el sistema hoy, cuántos están por vencer y cuánto suman las licencias vigentes. La tarjeta de «Por vencer» se pinta de naranja cuando hay alguno.'],
        ['elemento' => '[data-tour=atencion]', 'titulo' => 'Requieren atención', 'descripcion' => 'Aquí aparecen los negocios a los que debes darles seguimiento. Da clic en cualquiera para ver su detalle y renovar o reactivar su licencia.', 'lado' => 'top'],
        ['elemento' => '[data-tour=nuevo]', 'titulo' => 'Registra tu primera suscripción', 'descripcion' => 'Con este botón das de alta un negocio, creas la cuenta de su dueño y le asignas su primera licencia, todo en un solo formulario.', 'lado' => 'left'],
        ['elemento' => '[data-tour=menu-suscripciones]', 'titulo' => 'Listado completo', 'descripcion' => 'En «Suscripciones» del menú lateral puedes buscar, filtrar por estado y administrar todos los negocios. Puedes repetir este recorrido con el botón «Ver recorrido».', 'lado' => 'right'],
    ]" />
</div>
