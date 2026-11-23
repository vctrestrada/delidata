<?php

namespace App\Livewire\Negocio\Restaurantes;

use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\Restaurante;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Alta y edición de un restaurante del negocio.
 */
class Formulario extends Component
{
    use AlcanceDelNegocio;

    public ?Restaurante $restaurante = null;

    public string $nombre = '';

    public string $direccion = '';

    public string $telefono = '';

    public string $abre = '';

    public string $cierra = '';

    public bool $activo = true;

    public function mount(?Restaurante $restaurante = null): void
    {
        if ($restaurante?->exists) {
            $this->autorizarRestaurante($restaurante);
            $this->restaurante = $restaurante;
            $this->fill([
                'nombre' => $restaurante->nombre,
                'direccion' => (string) $restaurante->direccion,
                'telefono' => (string) $restaurante->telefono,
                'abre' => $restaurante->abre ? substr($restaurante->abre, 0, 5) : '',
                'cierra' => $restaurante->cierra ? substr($restaurante->cierra, 0, 5) : '',
                'activo' => $restaurante->activo,
            ]);

            return;
        }

        // Si ya llegó al límite del plan, no tiene caso mostrar el formulario.
        if (! $this->negocioActual()->puedeAgregarRestaurante()) {
            Flux::toast(variant: 'warning', text: 'Llegaste al límite de restaurantes de tu plan.');
            $this->redirectRoute('negocio.restaurantes.index', navigate: true);
        }
    }

    public function esEdicion(): bool
    {
        return $this->restaurante !== null;
    }

    protected function rules(): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:120',
                Rule::unique('restaurantes', 'nombre')
                    ->where('negocio_id', $this->usuario()->negocio_id)
                    ->ignore($this->restaurante?->id),
            ],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'abre' => ['nullable', 'date_format:H:i', 'required_with:cierra'],
            'cierra' => ['nullable', 'date_format:H:i', 'required_with:abre'],
            'activo' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'direccion' => 'dirección',
            'telefono' => 'teléfono',
            'abre' => 'hora de apertura',
            'cierra' => 'hora de cierre',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.unique' => 'Ya tienes un restaurante con ese nombre.',
            'telefono.regex' => 'Escribe solo números, espacios, guiones o paréntesis.',
        ];
    }

    public function guardar(): void
    {
        $datos = $this->validate();
        $datos = [
            ...$datos,
            'direccion' => $datos['direccion'] ?: null,
            'telefono' => $datos['telefono'] ?: null,
            'abre' => $datos['abre'] ?: null,
            'cierra' => $datos['cierra'] ?: null,
        ];

        if ($this->esEdicion()) {
            $this->restaurante->update($datos);
            Flux::toast(variant: 'success', text: "Se guardaron los cambios de {$this->restaurante->nombre}.");
        } else {
            // Se vuelve a revisar el límite por si se abrió el formulario en dos pestañas.
            if (! $this->negocioActual()->puedeAgregarRestaurante()) {
                Flux::toast(variant: 'danger', text: 'Llegaste al límite de restaurantes de tu plan.');

                return;
            }

            $restaurante = Restaurante::create([...$datos, 'negocio_id' => $this->usuario()->negocio_id]);
            Flux::toast(
                variant: 'success',
                heading: 'Restaurante registrado',
                text: "Ahora asígnale un encargado y vendedores a {$restaurante->nombre}.",
            );
        }

        $this->redirectRoute('negocio.restaurantes.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.negocio.restaurantes.formulario')
            ->title($this->esEdicion() ? 'Editar restaurante' : 'Nuevo restaurante');
    }
}
