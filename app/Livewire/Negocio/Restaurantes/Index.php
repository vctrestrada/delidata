<?php

namespace App\Livewire\Negocio\Restaurantes;

use App\Enums\Rol;
use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\Negocio;
use App\Models\Restaurante;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Restaurantes')]
class Index extends Component
{
    use AlcanceDelNegocio;

    #[Computed]
    public function negocio(): Negocio
    {
        return $this->negocioActual();
    }

    /** @return Collection<int, Restaurante> */
    #[Computed]
    public function restaurantes(): Collection
    {
        return $this->consultaRestaurantes()
            ->with('personal.roles')
            ->orderBy('nombre')
            ->get();
    }

    public function alternarActivo(int $id): void
    {
        $restaurante = $this->consultaRestaurantes()->findOrFail($id);
        $restaurante->update(['activo' => ! $restaurante->activo]);
        unset($this->restaurantes);

        Flux::toast(
            variant: 'success',
            text: $restaurante->activo
                ? "{$restaurante->nombre} se activó."
                : "{$restaurante->nombre} se desactivó. Su encargado y vendedores no podrán entrar.",
        );
    }

    public function eliminar(int $id): void
    {
        $restaurante = $this->consultaRestaurantes()->withCount('personal')->findOrFail($id);

        if ($restaurante->personal_count > 0) {
            Flux::toast(
                variant: 'danger',
                heading: 'No se puede eliminar',
                text: "{$restaurante->nombre} todavía tiene personal asignado. Reasígnalo a otro restaurante o desactiva el restaurante.",
            );

            return;
        }

        $restaurante->delete();
        unset($this->restaurantes);

        Flux::toast(variant: 'success', text: "Se eliminó {$restaurante->nombre}.");
    }

    public function encargadoDe(Restaurante $restaurante): ?string
    {
        return $restaurante->personal->first(fn ($u) => $u->hasRole(Rol::Encargado->value))?->name;
    }

    public function vendedoresDe(Restaurante $restaurante): int
    {
        return $restaurante->personal->filter(fn ($u) => $u->hasRole(Rol::Vendedor->value))->count();
    }

    public function render()
    {
        return view('livewire.negocio.restaurantes.index');
    }
}
