<div class="flex flex-col gap-6">
    <x-panel.encabezado
        :titulo="$this->esEdicion() ? 'Editar '.$negocio->nombre : 'Nueva suscripción'"
        :descripcion="$this->esEdicion()
            ? 'Actualiza los datos del negocio y de su dueño. Las licencias se administran desde el detalle.'
            : 'Registra el negocio, crea la cuenta de su dueño y asígnale su primera licencia.'"
        :regresar="$this->esEdicion() ? route('admin.suscripciones.detalle', $negocio) : route('admin.suscripciones.index')"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
        </x-slot:acciones>
    </x-panel.encabezado>

    <form wire:submit="guardar" class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- 1. Negocio --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="seccion-negocio">
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex size-7 items-center justify-center rounded-full bg-orange-500 text-sm font-semibold text-white">1</span>
                    <div>
                        <flux:heading size="lg">Datos del negocio</flux:heading>
                        <flux:text class="text-sm">Solo el nombre es obligatorio.</flux:text>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <flux:input wire:model="nombre" label="Nombre comercial" placeholder="Ej. La Esquina Taquería" required autofocus />
                    </div>
                    <flux:input wire:model="rfc" label="RFC" placeholder="ESQ240101AB1" maxlength="13" class:input="uppercase" />
                    <flux:input wire:model="telefono" label="Teléfono" type="tel" placeholder="55 1234 5678" />
                    <flux:input wire:model="correo" label="Correo del negocio" type="email" placeholder="contacto@negocio.mx" />
                    <flux:input wire:model="direccion" label="Dirección fiscal" placeholder="Calle, número, colonia, ciudad" />
                </div>
            </section>

            {{-- 2. Dueño --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="seccion-dueno">
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex size-7 items-center justify-center rounded-full bg-orange-500 text-sm font-semibold text-white">2</span>
                    <div>
                        <flux:heading size="lg">Cuenta del dueño</flux:heading>
                        <flux:text class="text-sm">
                            {{ $this->esEdicion() ? 'Deja la contraseña vacía si no quieres cambiarla.' : 'Con estos datos el dueño entrará a Delidata. No necesita verificar su correo.' }}
                        </flux:text>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <flux:input wire:model="duenoNombre" label="Nombre completo" placeholder="Nombre y apellidos" required />
                    <flux:input wire:model="duenoCorreo" label="Correo para iniciar sesión" type="email" placeholder="dueno@correo.com" required />
                    <div class="md:col-span-2">
                        <flux:input
                            wire:model="duenoPassword"
                            :label="$this->esEdicion() ? 'Nueva contraseña (opcional)' : 'Contraseña temporal'"
                            type="password"
                            viewable
                            :required="! $this->esEdicion()"
                            description="Mínimo 8 caracteres. Compártela con el dueño para que la cambie en su configuración."
                        />
                    </div>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            {{-- 3. Licencia inicial --}}
            @unless ($this->esEdicion())
                <section class="rounded-xl border border-orange-200 bg-orange-50/60 p-6" data-tour="seccion-licencia">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex size-7 items-center justify-center rounded-full bg-orange-500 text-sm font-semibold text-white">3</span>
                        <div>
                            <flux:heading size="lg">Primera licencia</flux:heading>
                            <flux:text class="text-sm">Podrás renovarla después desde el detalle.</flux:text>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <flux:radio.group wire:model.live="plan" label="Plan" variant="cards" class="flex-col">
                            @foreach (\App\Enums\PlanLicencia::cases() as $opcion)
                                <flux:radio :value="$opcion->value" :label="$opcion->etiqueta()" :description="$opcion->descripcion()" />
                            @endforeach
                        </flux:radio.group>

                        <div class="grid grid-cols-2 gap-4">
                            <flux:select wire:model.live="meses" label="Duración">
                                <flux:select.option value="1">1 mes</flux:select.option>
                                <flux:select.option value="3">3 meses</flux:select.option>
                                <flux:select.option value="6">6 meses</flux:select.option>
                                <flux:select.option value="12">12 meses</flux:select.option>
                                <flux:select.option value="24">24 meses</flux:select.option>
                            </flux:select>
                            <flux:input wire:model.live="fechaInicio" type="date" label="Inicia" />
                        </div>

                        <flux:input wire:model="precio" label="Precio total" icon="currency-dollar" inputmode="decimal"
                            description="Se calcula con el plan y la duración; puedes ajustarlo si diste un descuento." />

                        @if ($fin = $this->fechaFinEstimada())
                            <div class="rounded-lg border border-orange-200 bg-white px-4 py-3 text-sm text-zinc-700">
                                La licencia terminará el <span class="font-semibold text-orange-600">{{ $fin }}</span>.
                            </div>
                        @endif
                    </div>
                </section>
            @endunless

            <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-6" data-tour="guardar">
                <flux:button type="submit" variant="primary" icon="check" class="w-full">
                    {{ $this->esEdicion() ? 'Guardar cambios' : 'Registrar suscripción' }}
                </flux:button>
                <flux:button variant="ghost" class="w-full" :href="$this->esEdicion() ? route('admin.suscripciones.detalle', $negocio) : route('admin.suscripciones.index')" wire:navigate>
                    Cancelar
                </flux:button>
            </div>
        </div>
    </form>

    @if ($this->esEdicion())
        <x-recorrido clave="admin.suscripciones.editar" :pasos="[
            ['titulo' => 'Editar un negocio', 'descripcion' => 'Aquí corriges los datos del negocio o de su dueño. Las licencias no se cambian aquí, sino en el detalle del negocio.'],
            ['elemento' => '[data-tour=seccion-negocio]', 'titulo' => 'Datos del negocio', 'descripcion' => 'Cambia el nombre, RFC o datos de contacto.'],
            ['elemento' => '[data-tour=seccion-dueno]', 'titulo' => 'Cuenta del dueño', 'descripcion' => 'Si el dueño olvidó su contraseña, escribe una nueva aquí. Si la dejas vacía, se queda la que tenía.', 'lado' => 'top'],
            ['elemento' => '[data-tour=guardar]', 'titulo' => 'Guardar', 'descripcion' => 'Al guardar regresas al detalle del negocio.', 'lado' => 'left'],
        ]" />
    @else
        <x-recorrido clave="admin.suscripciones.crear" :pasos="[
            ['titulo' => 'Registrar un negocio', 'descripcion' => 'El formulario tiene 3 secciones numeradas. Al guardar se crean el negocio, la cuenta del dueño y su licencia, todo junto.'],
            ['elemento' => '[data-tour=seccion-negocio]', 'titulo' => '1. Datos del negocio', 'descripcion' => 'Escribe el nombre comercial (obligatorio). El RFC, teléfono, correo y dirección son opcionales y puedes completarlos después.'],
            ['elemento' => '[data-tour=seccion-dueno]', 'titulo' => '2. Cuenta del dueño', 'descripcion' => 'Captura el nombre y el correo con el que el dueño iniciará sesión, y una contraseña temporal. El dueño entra directo, sin verificar su correo.', 'lado' => 'top'],
            ['elemento' => '[data-tour=seccion-licencia]', 'titulo' => '3. Primera licencia', 'descripcion' => 'Elige el plan (define cuántos restaurantes puede tener), la duración y la fecha de inicio. El precio se calcula solo, pero puedes cambiarlo. Abajo ves en qué fecha terminará.', 'lado' => 'left'],
            ['elemento' => '[data-tour=guardar]', 'titulo' => 'Registrar', 'descripcion' => 'Revisa los datos y presiona «Registrar suscripción». Si falta algo, el campo se marca en rojo con el motivo.', 'lado' => 'left'],
        ]" />
    @endif
</div>
