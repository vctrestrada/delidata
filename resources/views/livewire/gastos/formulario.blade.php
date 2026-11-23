<div class="flex flex-col gap-6">
    <x-panel.encabezado
        :titulo="$this->esEdicion() ? 'Editar gasto' : 'Registrar gasto'"
        :descripcion="$this->esEncargado()
            ? 'El gasto se registrará en '.auth()->user()->restaurante?->nombre.'.'
            : 'Elige el restaurante al que corresponde el gasto.'"
        :regresar="$this->ruta('gastos.index')"
    >
        <x-slot:acciones>
            <x-recorrido.boton />
        </x-slot:acciones>
    </x-panel.encabezado>

    <form wire:submit="guardar" class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Categoría --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="categoria">
                <flux:heading size="lg" class="mb-5">¿Qué tipo de gasto es?</flux:heading>
                <flux:radio.group wire:model="categoria" variant="cards" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (\App\Enums\CategoriaGasto::cases() as $opcion)
                        <flux:radio :value="$opcion->value" :icon="$opcion->icono()" :label="$opcion->etiqueta()" :description="$opcion->descripcion()" />
                    @endforeach
                </flux:radio.group>
                <flux:error name="categoria" />
            </section>

            {{-- Detalle --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="detalle">
                <flux:heading size="lg" class="mb-5">Detalle</flux:heading>
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <flux:input wire:model="concepto" label="Concepto" placeholder="Ej. 10 kg de pastor, recibo de luz de noviembre…" required />
                    </div>
                    <flux:input wire:model="monto" label="Monto" icon="currency-dollar" inputmode="decimal" placeholder="0.00" required />
                    <flux:input wire:model="fecha" type="date" label="Fecha del gasto" :max="today()->toDateString()" required />
                    <flux:select wire:model="metodo" label="¿Cómo se pagó?">
                        @foreach (\App\Enums\MetodoPago::cases() as $opcion)
                            <flux:select.option :value="$opcion->value">{{ $opcion->etiqueta() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="proveedor" label="Proveedor (opcional)" placeholder="Ej. Carnicería Don Pepe, CFE" />
                    @unless ($this->esEncargado())
                        <div class="md:col-span-2">
                            <flux:select wire:model="restauranteId" label="Restaurante" placeholder="Elige un restaurante…">
                                @foreach ($this->restaurantesDisponibles() as $opcion)
                                    <flux:select.option :value="$opcion->id">{{ $opcion->nombre }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endunless
                    <div class="md:col-span-2">
                        <flux:textarea wire:model="notas" label="Notas (opcional)" rows="2" />
                    </div>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            {{-- Comprobante --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6" data-tour="comprobante">
                <flux:heading size="lg">Comprobante</flux:heading>
                <flux:text class="mb-4 text-sm">Foto del ticket o la factura en PDF. Solo tú y tu equipo autorizado pueden verlo.</flux:text>

                <div class="mb-4 flex aspect-[4/3] items-center justify-center overflow-hidden rounded-xl border border-dashed border-orange-300 bg-orange-50/50">
                    @if ($comprobante && str_starts_with((string) $comprobante->getMimeType(), 'image/'))
                        <img src="{{ $comprobante->temporaryUrl() }}" alt="Vista previa" class="size-full object-contain">
                    @elseif ($comprobante)
                        <div class="flex flex-col items-center gap-2 text-orange-600">
                            <flux:icon name="document-text" variant="outline" class="size-10" />
                            <span class="max-w-48 truncate text-xs">{{ $comprobante->getClientOriginalName() }}</span>
                        </div>
                    @elseif ($gasto?->comprobante && ! $quitarComprobante)
                        <a href="{{ route('gastos.comprobante', $gasto) }}" target="_blank" class="flex flex-col items-center gap-2 text-orange-600 hover:text-orange-700">
                            <flux:icon :name="$gasto->comprobanteEsPdf() ? 'document-text' : 'photo'" variant="outline" class="size-10" />
                            <span class="text-xs underline">Ver comprobante actual</span>
                        </a>
                    @else
                        <div class="flex flex-col items-center gap-2 text-orange-500">
                            <flux:icon name="paper-clip" variant="outline" class="size-10" />
                            <span class="text-xs text-zinc-500">JPG, PNG, WEBP o PDF · máximo 5 MB</span>
                        </div>
                    @endif
                </div>

                <flux:input type="file" wire:model="comprobante" accept="image/jpeg,image/png,image/webp,application/pdf" />
                <div wire:loading wire:target="comprobante" class="mt-2 text-xs text-orange-600">Subiendo archivo…</div>
                @if ($gasto?->comprobante && ! $comprobante)
                    <flux:checkbox wire:model.live="quitarComprobante" label="Quitar el comprobante actual" class="mt-3" />
                @endif
            </section>

            <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-6" data-tour="guardar">
                <flux:button type="submit" variant="primary" icon="check" class="w-full" wire:loading.attr="disabled" wire:target="guardar,comprobante">
                    {{ $this->esEdicion() ? 'Guardar cambios' : 'Registrar gasto' }}
                </flux:button>
                <flux:button variant="ghost" class="w-full" :href="$this->ruta('gastos.index')" wire:navigate>Cancelar</flux:button>
            </div>
        </div>
    </form>

    <x-recorrido :clave="$this->prefijo().'.gastos.formulario'" :pasos="[
        ['titulo' => 'Registrar un gasto', 'descripcion' => 'Tarda menos de un minuto. Entre más constante seas, más exactos serán tus reportes.'],
        ['elemento' => '[data-tour=categoria]', 'titulo' => '1. Tipo de gasto', 'descripcion' => 'Elige la categoría. Debajo de cada una hay ejemplos para que sepas cuál usar.'],
        ['elemento' => '[data-tour=detalle]', 'titulo' => '2. Detalle', 'descripcion' => 'Describe qué compraste o pagaste, cuánto, en qué fecha y cómo lo pagaste. El proveedor es opcional pero te ayuda a buscar después.', 'lado' => 'top'],
        ['elemento' => '[data-tour=comprobante]', 'titulo' => '3. Comprobante', 'descripcion' => 'Toma una foto del ticket o sube la factura en PDF. Se guarda de forma privada: no se puede abrir con un enlace público.', 'lado' => 'left'],
        ['elemento' => '[data-tour=guardar]', 'titulo' => '4. Guardar', 'descripcion' => 'Listo. El gasto se suma de inmediato al resumen.', 'lado' => 'left'],
    ]" />
</div>
