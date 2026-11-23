<?php

namespace App\Livewire\Negocio\Personal;

use App\Enums\Rol;
use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Personal del negocio.
 *  - Dueño: ve encargados y vendedores de todos sus restaurantes.
 *  - Encargado: ve solo a los vendedores de su restaurante.
 */
class Index extends Component
{
    use AlcanceDelNegocio;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $buscar = '';

    #[Url(except: '')]
    public string $restaurante = '';

    #[Url(except: '')]
    public string $rol = '';

    public function updated(string $propiedad): void
    {
        if (in_array($propiedad, ['buscar', 'restaurante', 'rol'], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset('buscar', 'restaurante', 'rol');
        $this->resetPage();
    }

    #[Computed]
    public function personal(): LengthAwarePaginator
    {
        $like = '%'.mb_strtolower(trim($this->buscar)).'%';

        return $this->consultaPersonal()
            ->with(['roles', 'restaurante'])
            ->when($this->buscar !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereRaw('LOWER(name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(email) LIKE ?', [$like])))
            ->when($this->restaurante !== '', fn (Builder $q) => $q->where('restaurante_id', (int) $this->restaurante))
            ->when(Rol::tryFrom($this->rol), fn (Builder $q, Rol $rol) => $q->role($rol->value))
            ->orderBy('name')
            ->paginate(12);
    }

    public function alternarActivo(int $id): void
    {
        /** @var User $usuario */
        $usuario = $this->consultaPersonal()->findOrFail($id);
        $usuario->forceFill(['activo' => ! $usuario->activo])->save();
        unset($this->personal);

        Flux::toast(
            variant: 'success',
            text: $usuario->activo ? "{$usuario->name} puede volver a entrar." : "{$usuario->name} ya no podrá entrar al sistema.",
        );
    }

    public function render()
    {
        return view('livewire.negocio.personal.index')
            ->title($this->esEncargado() ? 'Vendedores' : 'Personal');
    }
}
