<?php

namespace App\Livewire\Admin\Suscripciones;

use App\Enums\EstadoLicencia;
use App\Enums\PlanLicencia;
use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * Alta y edición de una suscripción (negocio).
 *
 * - Al crear: datos del negocio + cuenta del dueño + primera licencia.
 * - Al editar: datos del negocio y del dueño (las licencias se manejan en el detalle).
 */
class Formulario extends Component
{
    public ?Negocio $negocio = null;

    // Negocio
    public string $nombre = '';

    public string $rfc = '';

    public string $telefono = '';

    public string $correo = '';

    public string $direccion = '';

    // Dueño
    public string $duenoNombre = '';

    public string $duenoCorreo = '';

    public string $duenoPassword = '';

    // Primera licencia (solo al crear)
    public string $plan = 'profesional';

    public int $meses = 12;

    public string $fechaInicio = '';

    public string $precio = '';

    public function mount(?Negocio $negocio = null): void
    {
        if ($negocio?->exists) {
            $this->negocio = $negocio->load('dueno');
            $this->fill([
                'nombre' => $negocio->nombre,
                'rfc' => (string) $negocio->rfc,
                'telefono' => (string) $negocio->telefono,
                'correo' => (string) $negocio->correo,
                'direccion' => (string) $negocio->direccion,
                'duenoNombre' => (string) $negocio->dueno?->name,
                'duenoCorreo' => (string) $negocio->dueno?->email,
            ]);

            return;
        }

        $this->fechaInicio = today()->toDateString();
        $this->calcularPrecio();
    }

    public function esEdicion(): bool
    {
        return $this->negocio !== null;
    }

    /** Al cambiar plan o meses se recalcula el precio sugerido. */
    public function updatedPlan(): void
    {
        $this->calcularPrecio();
    }

    public function updatedMeses(): void
    {
        $this->calcularPrecio();
    }

    private function calcularPrecio(): void
    {
        $plan = PlanLicencia::tryFrom($this->plan) ?? PlanLicencia::Profesional;
        $this->precio = number_format($plan->precioMensual() * max(1, $this->meses), 2, '.', '');
    }

    public function fechaFinEstimada(): ?string
    {
        if (! $this->fechaInicio || $this->meses < 1) {
            return null;
        }

        return Carbon::parse($this->fechaInicio)->addMonthsNoOverflow($this->meses)->subDay()->translatedFormat('d \d\e F \d\e Y');
    }

    protected function rules(): array
    {
        $duenoId = $this->negocio?->dueno_id;

        $reglas = [
            'nombre' => ['required', 'string', 'max:120'],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'correo' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'duenoNombre' => ['required', 'string', 'max:120'],
            'duenoCorreo' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($duenoId)],
            'duenoPassword' => [$this->esEdicion() ? 'nullable' : 'required', Password::defaults(), 'min:8'],
        ];

        if (! $this->esEdicion()) {
            $reglas += [
                'plan' => ['required', Rule::enum(PlanLicencia::class)],
                'meses' => ['required', 'integer', Rule::in([1, 3, 6, 12, 24])],
                'fechaInicio' => ['required', 'date'],
                'precio' => ['required', 'numeric', 'min:0', 'max:9999999'],
            ];
        }

        return $reglas;
    }

    protected function validationAttributes(): array
    {
        return [
            'nombre' => 'nombre del negocio',
            'rfc' => 'RFC',
            'telefono' => 'teléfono',
            'correo' => 'correo del negocio',
            'direccion' => 'dirección',
            'duenoNombre' => 'nombre del dueño',
            'duenoCorreo' => 'correo del dueño',
            'duenoPassword' => 'contraseña',
            'fechaInicio' => 'fecha de inicio',
        ];
    }

    protected function messages(): array
    {
        return [
            'rfc.regex' => 'El RFC no tiene un formato válido (ej. ESQ240101AB1).',
            'telefono.regex' => 'Escribe solo números, espacios, guiones o paréntesis.',
            'duenoCorreo.unique' => 'Ya existe una cuenta con este correo.',
        ];
    }

    public function guardar(): void
    {
        $this->rfc = mb_strtoupper(trim($this->rfc));
        $this->duenoCorreo = mb_strtolower(trim($this->duenoCorreo));

        $datos = $this->validate();

        $negocio = DB::transaction(fn () => $this->esEdicion()
            ? $this->actualizar($datos)
            : $this->crear($datos));

        Flux::toast(
            variant: 'success',
            heading: $this->esEdicion() ? 'Cambios guardados' : 'Suscripción registrada',
            text: $this->esEdicion()
                ? "Se actualizaron los datos de {$negocio->nombre}."
                : "{$negocio->nombre} ya puede entrar con la cuenta de su dueño.",
        );

        $this->redirectRoute('admin.suscripciones.detalle', $negocio, navigate: true);
    }

    private function crear(array $datos): Negocio
    {
        $dueno = User::create([
            'name' => $datos['duenoNombre'],
            'email' => $datos['duenoCorreo'],
            'password' => $datos['duenoPassword'],
        ]);
        // El super admin crea la cuenta, así que no se pide verificar el correo.
        $dueno->forceFill(['email_verified_at' => now()])->save();
        $dueno->assignRole(Rol::Dueno->value);

        $negocio = Negocio::create([
            ...$this->datosNegocio($datos),
            'dueno_id' => $dueno->id,
        ]);

        $dueno->negocio()->associate($negocio)->save();

        $plan = PlanLicencia::from($datos['plan']);
        $inicio = Carbon::parse($datos['fechaInicio']);

        $negocio->licencias()->create([
            'plan' => $plan,
            'max_restaurantes' => $plan->maxRestaurantes(),
            'precio' => $datos['precio'],
            'fecha_inicio' => $inicio,
            'fecha_fin' => $inicio->copy()->addMonthsNoOverflow((int) $datos['meses'])->subDay(),
            'estado' => EstadoLicencia::Activa,
            'notas' => 'Licencia inicial',
        ]);

        return $negocio;
    }

    private function actualizar(array $datos): Negocio
    {
        $this->negocio->update($this->datosNegocio($datos));

        if ($dueno = $this->negocio->dueno) {
            $dueno->fill(['name' => $datos['duenoNombre'], 'email' => $datos['duenoCorreo']]);

            if (filled($datos['duenoPassword'])) {
                $dueno->password = $datos['duenoPassword'];
            }

            $dueno->save();
        }

        return $this->negocio;
    }

    private function datosNegocio(array $datos): array
    {
        return [
            'nombre' => $datos['nombre'],
            'rfc' => $datos['rfc'] ?: null,
            'telefono' => $datos['telefono'] ?: null,
            'correo' => $datos['correo'] ?: null,
            'direccion' => $datos['direccion'] ?: null,
        ];
    }

    public function render()
    {
        return view('livewire.admin.suscripciones.formulario')
            ->title($this->esEdicion() ? 'Editar suscripción' : 'Nueva suscripción');
    }
}
