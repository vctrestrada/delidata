<?php

namespace App\Livewire\Negocio\Personal;

use App\Enums\Rol;
use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * Alta y edición de personal.
 *  - Dueño: crea encargados o vendedores y elige su restaurante.
 *  - Encargado: solo crea vendedores, y siempre en su propio restaurante.
 */
class Formulario extends Component
{
    use AlcanceDelNegocio;

    public ?User $usuarioEditado = null;

    public string $nombre = '';

    public string $correo = '';

    public string $password = '';

    public string $rol = '';

    public string $restauranteId = '';

    public bool $activo = true;

    public function mount(?User $usuario = null): void
    {
        if ($usuario?->exists) {
            $this->autorizarUsuario($usuario);
            $this->usuarioEditado = $usuario;
            $this->fill([
                'nombre' => $usuario->name,
                'correo' => $usuario->email,
                'rol' => (string) $usuario->rolPrincipal()?->value,
                'restauranteId' => (string) $usuario->restaurante_id,
                'activo' => $usuario->activo,
            ]);
        } else {
            $this->rol = Rol::Vendedor->value;
            $this->restauranteId = (string) ($this->esEncargado()
                ? $this->usuario()->restaurante_id
                : (request()->integer('restaurante') ?: $this->restaurantesDisponibles()->first()?->id));
        }
    }

    public function esEdicion(): bool
    {
        return $this->usuarioEditado !== null;
    }

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'correo' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->usuarioEditado?->id)],
            'password' => [$this->esEdicion() ? 'nullable' : 'required', Password::defaults(), 'min:8'],
            'rol' => ['required', Rule::in(array_map(fn (Rol $r) => $r->value, $this->rolesAsignables()))],
            'restauranteId' => ['required', Rule::in($this->restaurantesDisponibles()->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'activo' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'correo' => 'correo',
            'password' => 'contraseña',
            'rol' => 'puesto',
            'restauranteId' => 'restaurante',
        ];
    }

    protected function messages(): array
    {
        return [
            'correo.unique' => 'Ya existe una cuenta con este correo.',
            'restauranteId.required' => 'Elige el restaurante donde trabajará.',
            'restauranteId.in' => 'Elige uno de tus restaurantes.',
        ];
    }

    public function guardar(): void
    {
        $this->correo = mb_strtolower(trim($this->correo));

        // El encargado no puede cambiar el puesto ni el restaurante.
        if ($this->esEncargado()) {
            $this->rol = Rol::Vendedor->value;
            $this->restauranteId = (string) $this->usuario()->restaurante_id;
        }

        $datos = $this->validate();

        $usuario = $this->usuarioEditado ?? new User;
        $usuario->fill(['name' => $datos['nombre'], 'email' => $datos['correo']]);

        if (filled($datos['password'])) {
            $usuario->password = $datos['password'];
        }

        $usuario->forceFill([
            'negocio_id' => $this->usuario()->negocio_id,
            'restaurante_id' => (int) $datos['restauranteId'],
            'activo' => $datos['activo'],
            // Quien crea la cuenta es el dueño o el encargado; no se pide verificar el correo.
            'email_verified_at' => $usuario->email_verified_at ?? now(),
        ])->save();

        $usuario->syncRoles([$datos['rol']]);

        Flux::toast(
            variant: 'success',
            heading: $this->esEdicion() ? 'Cambios guardados' : 'Cuenta creada',
            text: $this->esEdicion()
                ? "Se actualizó la cuenta de {$usuario->name}."
                : "{$usuario->name} ya puede entrar con su correo y la contraseña que le asignaste.",
        );

        $this->redirect($this->ruta('personal.index'), navigate: true);
    }

    public function render()
    {
        $titulo = $this->esEdicion() ? 'Editar a '.$this->usuarioEditado->name : ($this->esEncargado() ? 'Nuevo vendedor' : 'Agregar personal');

        return view('livewire.negocio.personal.formulario', ['titulo' => $titulo])->title($titulo);
    }
}
