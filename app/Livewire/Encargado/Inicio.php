<?php

namespace App\Livewire\Encargado;

use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\Restaurante;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Mi restaurante')]
class Inicio extends Component
{
    use AlcanceDelNegocio;

    #[Computed]
    public function restaurante(): Restaurante
    {
        return $this->usuario()->restaurante()->with('negocio')->firstOrFail();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function vendedores(): Collection
    {
        return $this->consultaPersonal()->orderByDesc('activo')->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.encargado.inicio');
    }
}
