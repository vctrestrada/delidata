<x-layouts::app title="Acceso suspendido">
    <div class="flex flex-1 items-center justify-center py-12">
        <div class="max-w-lg space-y-5 rounded-2xl border border-zinc-200 bg-white p-8 text-center">
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-orange-50 text-orange-600">
                <flux:icon name="key" variant="outline" class="size-7" />
            </div>

            <div class="space-y-2">
                <flux:heading size="xl">
                    @if ($restaurante === null)
                        Tu cuenta no está ligada a un restaurante
                    @elseif (! $restaurante->activo)
                        {{ $restaurante->nombre }} está desactivado
                    @else
                        La licencia de {{ $restaurante->nombre }} no está vigente
                    @endif
                </flux:heading>

                @if ($estado)
                    <x-panel.estado-licencia :estado="$estado" />
                @endif
            </div>

            <flux:text>
                @if ($esDueno)
                    Para seguir usando Delidata comunícate con el administrador de la plataforma y renueva tu licencia.
                    Tus datos están guardados y estarán disponibles en cuanto se reactive.
                @else
                    Avisa al dueño del restaurante. En cuanto la licencia se reactive podrás volver a entrar.
                @endif
            </flux:text>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button type="submit" variant="ghost" icon="arrow-right-start-on-rectangle">Cerrar sesión</flux:button>
            </form>
        </div>
    </div>
</x-layouts::app>
