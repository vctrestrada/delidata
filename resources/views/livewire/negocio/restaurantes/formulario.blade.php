<div class="flex flex-col gap-6">
    <x-panel.encabezado
        :titulo="$this->esEdicion() ? 'Editar '.$restaurante->nombre : 'Nuevo restaurante'"
        :descripcion="$this->esEdicion() ? 'Actualiza los datos de este restaurante.' : 'Registra un local de tu negocio. Solo el nombre es obligatorio.'"
        :regresar="route('negocio.restaurantes.index')"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
        </x-slot:acciones>
    </x-panel.encabezado>

    <form wire:submit="guardar" class="grid max-w-4xl gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="datos">
                <flux:heading size="lg" class="mb-5">Datos del restaurante</flux:heading>
                <div class="grid gap-5">
                    <flux:input wire:model="nombre" label="Nombre" placeholder="Ej. La Esquina — Centro" required autofocus
                        description="Usa un nombre que tu personal reconozca, por ejemplo la colonia o la plaza." />
                    <flux:input wire:model="direccion" label="Dirección" placeholder="Calle, número, colonia" />
                    <flux:input wire:model="telefono" label="Teléfono" type="tel" placeholder="55 1234 5678" />
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="horario">
                <flux:heading size="lg">Horario</flux:heading>
                <flux:text class="mb-5 text-sm">Opcional. Lo usaremos para los reportes por turno.</flux:text>
                <div class="grid grid-cols-2 gap-5">
                    <flux:input wire:model="abre" type="time" label="Abre" />
                    <flux:input wire:model="cierra" type="time" label="Cierra" />
                </div>
            </section>
        </div>

        <div class="space-y-6">
            @if ($this->esEdicion())
                <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="activo">
                    <flux:switch wire:model="activo" label="Restaurante activo"
                        description="Si lo desactivas, su encargado y vendedores no podrán entrar." />
                </section>
            @endif

            <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-6" data-tour="guardar">
                <flux:button type="submit" variant="primary" icon="check" class="w-full">
                    {{ $this->esEdicion() ? 'Guardar cambios' : 'Registrar restaurante' }}
                </flux:button>
                <flux:button variant="ghost" class="w-full" :href="route('negocio.restaurantes.index')" wire:navigate>Cancelar</flux:button>
            </div>
        </div>
    </form>

    <x-recorrido :clave="$this->esEdicion() ? 'negocio.restaurantes.editar' : 'negocio.restaurantes.crear'" :pasos="[
        ['titulo' => $this->esEdicion() ? 'Editar un restaurante' : 'Registrar un restaurante', 'descripcion' => 'Llena los datos del local. Solo el nombre es obligatorio; lo demás lo puedes completar después.'],
        ['elemento' => '[data-tour=datos]', 'titulo' => 'Datos del restaurante', 'descripcion' => 'Ponle un nombre que distinga este local de los demás (por ejemplo «La Esquina — Centro»). La dirección y el teléfono son opcionales.'],
        ['elemento' => '[data-tour=horario]', 'titulo' => 'Horario', 'descripcion' => 'Hora de apertura y de cierre. Si llenas una, también debes llenar la otra.', 'lado' => 'top'],
        ['elemento' => '[data-tour=activo]', 'titulo' => 'Activar o desactivar', 'descripcion' => 'Si un local cierra temporalmente, desactívalo en lugar de borrarlo.', 'lado' => 'left'],
        ['elemento' => '[data-tour=guardar]', 'titulo' => 'Guardar', 'descripcion' => 'Al guardar regresas al listado. El siguiente paso es asignarle personal desde «Personal».', 'lado' => 'left'],
    ]" />
</div>
