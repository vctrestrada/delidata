<?php

namespace App\Livewire\Gastos;

use App\Enums\CategoriaGasto;
use App\Enums\MetodoPago;
use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\Gasto;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Registro y edición de un gasto.
 *  - Encargado: siempre en su restaurante.
 *  - Dueño: elige el restaurante.
 */
class Formulario extends Component
{
    use AlcanceDelNegocio;
    use WithFileUploads;

    public ?Gasto $gasto = null;

    public string $fecha = '';

    public string $restauranteId = '';

    public string $categoria = 'insumos';

    public string $concepto = '';

    public string $monto = '';

    public string $metodo = 'efectivo';

    public string $proveedor = '';

    public string $notas = '';

    /** @var TemporaryUploadedFile|null */
    public $comprobante = null;

    public bool $quitarComprobante = false;

    public function mount(?Gasto $gasto = null): void
    {
        if ($gasto?->exists) {
            abort_unless($this->consultaGastos()->whereKey($gasto->id)->exists(), 404);
            $this->gasto = $gasto;
            $this->fill([
                'fecha' => $gasto->fecha->toDateString(),
                'restauranteId' => (string) $gasto->restaurante_id,
                'categoria' => $gasto->categoria->value,
                'concepto' => $gasto->concepto,
                'monto' => (string) $gasto->monto,
                'metodo' => $gasto->metodo_pago->value,
                'proveedor' => (string) $gasto->proveedor,
                'notas' => (string) $gasto->notas,
            ]);

            return;
        }

        $this->fecha = today()->toDateString();
        $this->restauranteId = (string) ($this->esEncargado()
            ? $this->usuario()->restaurante_id
            : (request()->integer('restaurante') ?: $this->restaurantesDisponibles()->first()?->id));
    }

    public function esEdicion(): bool
    {
        return $this->gasto !== null;
    }

    protected function rules(): array
    {
        return [
            'fecha' => ['required', 'date', 'before_or_equal:today', 'after:'.today()->subYears(2)->toDateString()],
            'restauranteId' => ['required', Rule::in($this->restaurantesDisponibles()->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'categoria' => ['required', Rule::enum(CategoriaGasto::class)],
            'concepto' => ['required', 'string', 'max:150'],
            'monto' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'metodo' => ['required', Rule::enum(MetodoPago::class)],
            'proveedor' => ['nullable', 'string', 'max:120'],
            'notas' => ['nullable', 'string', 'max:255'],
            'comprobante' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'restauranteId' => 'restaurante',
            'categoria' => 'categoría',
            'metodo' => 'forma de pago',
        ];
    }

    protected function messages(): array
    {
        return [
            'fecha.before_or_equal' => 'No puedes registrar gastos con fecha futura.',
            'comprobante.max' => 'El comprobante no debe pesar más de 5 MB.',
            'comprobante.mimes' => 'Sube una foto (JPG, PNG, WEBP) o un PDF.',
        ];
    }

    public function guardar(): void
    {
        if ($this->esEncargado()) {
            $this->restauranteId = (string) $this->usuario()->restaurante_id;
        }

        $datos = $this->validate();

        $gasto = $this->gasto ?? new Gasto([
            'negocio_id' => $this->usuario()->negocio_id,
            'registrado_por' => $this->usuario()->id,
        ]);

        $gasto->fill([
            'restaurante_id' => (int) $datos['restauranteId'],
            'fecha' => $datos['fecha'],
            'categoria' => $datos['categoria'],
            'concepto' => trim($datos['concepto']),
            'monto' => $datos['monto'],
            'metodo_pago' => $datos['metodo'],
            'proveedor' => $datos['proveedor'] ?: null,
            'notas' => $datos['notas'] ?: null,
        ]);

        if ($this->comprobante || $this->quitarComprobante) {
            if ($gasto->comprobante) {
                Storage::disk(Gasto::DISCO_COMPROBANTES)->delete($gasto->comprobante);
            }

            $gasto->comprobante = $this->comprobante
                ? $this->comprobante->store('comprobantes/'.$this->usuario()->negocio_id, Gasto::DISCO_COMPROBANTES)
                : null;
        }

        $gasto->save();

        Flux::toast(
            variant: 'success',
            text: ($this->esEdicion() ? 'Se actualizó' : 'Se registró').' el gasto «'.$gasto->concepto.'» por $'.number_format((float) $gasto->monto, 2).'.',
        );

        $this->redirect($this->ruta('gastos.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.gastos.formulario')
            ->title($this->esEdicion() ? 'Editar gasto' : 'Registrar gasto');
    }
}
