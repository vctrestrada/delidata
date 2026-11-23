<x-layouts::app title="Acceso no disponible">
    <div class="flex flex-1 items-center justify-center py-12">
        <div class="max-w-lg space-y-5 rounded-2xl border border-zinc-200 bg-white p-8 text-center">
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-orange-50 text-orange-600">
                <flux:icon :name="in_array($motivo, ['sin-restaurante', 'restaurante-inactivo']) ? 'building-storefront' : 'key'" variant="outline" class="size-7" />
            </div>

            <div class="space-y-2">
                <flux:heading size="xl">
                    @switch($motivo)
                        @case('sin-negocio') Tu cuenta no está ligada a ningún negocio @break
                        @case('negocio-inactivo') {{ $negocio->nombre }} está desactivado @break
                        @case('suscripcion') La suscripción de {{ $negocio->nombre }} no está vigente @break
                        @case('sin-restaurante') Aún no tienes un restaurante asignado @break
                        @case('restaurante-inactivo') {{ $restaurante->nombre }} está desactivado @break
                    @endswitch
                </flux:heading>

                @if ($motivo === 'suscripcion' && $estado)
                    <x-panel.estado-licencia :estado="$estado" />
                @endif
            </div>

            <flux:text>
                @if (in_array($motivo, ['sin-restaurante', 'restaurante-inactivo']))
                    Pide al dueño o al encargado que te asigne a un restaurante activo.
                @elseif ($esDueno)
                    Para seguir usando Delidata comunícate con el equipo de Delidata y renueva tu suscripción.
                    Tus datos están guardados y estarán disponibles en cuanto se reactive.
                @else
                    Avisa al dueño del negocio. En cuanto la suscripción se reactive podrás volver a entrar.
                @endif
            </flux:text>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button type="submit" variant="ghost" icon="arrow-right-start-on-rectangle">Cerrar sesión</flux:button>
            </form>
        </div>
    </div>
</x-layouts::app>
