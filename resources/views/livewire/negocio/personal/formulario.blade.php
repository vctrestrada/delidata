<div class="flex flex-col gap-6">
    <x-panel.encabezado
        :titulo="$titulo"
        :descripcion="$this->esEdicion()
            ? 'Actualiza los datos de la cuenta. Deja la contraseña vacía para no cambiarla.'
            : 'Crea la cuenta con la que esta persona entrará a Delidata.'"
        :regresar="$this->ruta('personal.index')"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
        </x-slot:acciones>
    </x-panel.encabezado>

    <form wire:submit="guardar" class="grid max-w-4xl gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="cuenta">
                <flux:heading size="lg" class="mb-5">Cuenta</flux:heading>
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <flux:input wire:model="nombre" label="Nombre completo" placeholder="Nombre y apellidos" required autofocus />
                    </div>
                    <flux:input wire:model="correo" label="Correo para iniciar sesión" type="email" placeholder="persona@correo.com" required />
                    <flux:input wire:model="password" :label="$this->esEdicion() ? 'Nueva contraseña (opcional)' : 'Contraseña'" type="password" viewable
                        :required="! $this->esEdicion()" description="Mínimo 8 caracteres." />
                </div>
            </section>

            @unless ($this->esEncargado())
                <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="puesto">
                    <flux:heading size="lg" class="mb-5">Puesto y restaurante</flux:heading>
                    <div class="space-y-5">
                        <flux:radio.group wire:model="rol" label="Puesto" variant="cards" class="max-sm:flex-col">
                            <flux:radio value="encargado" label="Encargado" description="Responsable del restaurante. Administra a sus vendedores." />
                            <flux:radio value="vendedor" label="Vendedor" description="Registra las ventas del restaurante." />
                        </flux:radio.group>

                        <flux:select wire:model="restauranteId" label="Restaurante donde trabaja" placeholder="Elige un restaurante…">
                            @foreach ($this->restaurantesDisponibles() as $opcion)
                                <flux:select.option :value="$opcion->id">{{ $opcion->nombre }}{{ $opcion->activo ? '' : ' (inactivo)' }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        @if ($this->restaurantesDisponibles()->isEmpty())
                            <flux:callout icon="exclamation-triangle" color="orange">
                                <flux:callout.text>
                                    Primero registra un restaurante en
                                    <flux:callout.link :href="route('negocio.restaurantes.crear')" wire:navigate>Restaurantes</flux:callout.link>.
                                </flux:callout.text>
                            </flux:callout>
                        @endif
                    </div>
                </section>
            @else
                <flux:callout icon="building-storefront" color="orange">
                    <flux:callout.text>
                        La cuenta se creará como <strong>vendedor</strong> de <strong>{{ auth()->user()->restaurante?->nombre }}</strong>.
                    </flux:callout.text>
                </flux:callout>
            @endunless
        </div>

        <div class="space-y-6">
            @if ($this->esEdicion())
                <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="activo">
                    <flux:switch wire:model="activo" label="Cuenta activa" description="Si la desactivas, no podrá iniciar sesión." />
                </section>
            @endif

            <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-6" data-tour="guardar">
                <flux:button type="submit" variant="primary" icon="check" class="w-full">
                    {{ $this->esEdicion() ? 'Guardar cambios' : 'Crear cuenta' }}
                </flux:button>
                <flux:button variant="ghost" class="w-full" :href="$this->ruta('personal.index')" wire:navigate>Cancelar</flux:button>
            </div>
        </div>
    </form>

    <x-recorrido :clave="$this->prefijo().'.personal.formulario'" :pasos="[
        ['titulo' => $this->esEncargado() ? 'Crear un vendedor' : 'Crear una cuenta de personal', 'descripcion' => 'Cada persona tiene su propia cuenta para que sepas quién registró cada venta.'],
        ['elemento' => '[data-tour=cuenta]', 'titulo' => '1. Datos de la cuenta', 'descripcion' => 'Nombre, el correo con el que iniciará sesión y una contraseña. Compártele la contraseña; podrá cambiarla en su configuración.'],
        ['elemento' => '[data-tour=puesto]', 'titulo' => '2. Puesto y restaurante', 'descripcion' => 'Elige si será encargado (administra el restaurante y a sus vendedores) o vendedor (registra ventas), y en qué restaurante trabaja.', 'lado' => 'top'],
        ['elemento' => '[data-tour=activo]', 'titulo' => 'Cuenta activa', 'descripcion' => 'Desactívala cuando la persona deje de trabajar contigo. Su historial se conserva.', 'lado' => 'left'],
        ['elemento' => '[data-tour=guardar]', 'titulo' => '3. Guardar', 'descripcion' => 'La persona podrá entrar de inmediato, sin verificar su correo.', 'lado' => 'left'],
    ]" />
</div>
