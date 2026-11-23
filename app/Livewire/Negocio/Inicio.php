<?php

namespace App\Livewire\Negocio;

use App\Enums\Rol;
use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\Negocio;
use App\Models\Restaurante;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Mi negocio')]
class Inicio extends Component
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
            ->with(['personal.roles'])
            ->orderBy('nombre')
            ->get();
    }

    /** @return array<string, int> */
    #[Computed]
    public function personal(): array
    {
        $usuarios = $this->consultaPersonal()->with('roles')->get();

        return [
            'encargados' => $usuarios->filter(fn ($u) => $u->hasRole(Rol::Encargado->value))->count(),
            'vendedores' => $usuarios->filter(fn ($u) => $u->hasRole(Rol::Vendedor->value))->count(),
            'inactivos' => $usuarios->where('activo', false)->count(),
        ];
    }

    public function render()
    {
        return view('livewire.negocio.inicio');
    }
}
