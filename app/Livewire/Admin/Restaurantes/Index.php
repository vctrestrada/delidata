<?php

namespace App\Livewire\Admin\Restaurantes;

use App\Enums\EstadoLicencia;
use App\Models\Restaurante;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Restaurantes')]
class Index extends Component
{
    use WithPagination;

    private const POR_PAGINA = 10;

    #[Url(as: 'q', except: '')]
    public string $buscar = '';

    #[Url(except: '')]
    public string $estado = '';

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset('buscar', 'estado');
        $this->resetPage();
    }

    /**
     * El estado de la licencia se calcula con fechas, por eso el filtro
     * se aplica en PHP y la paginación se arma a mano.
     */
    #[Computed]
    public function restaurantes(): LengthAwarePaginator
    {
        $filtro = EstadoLicencia::tryFrom($this->estado);

        $todos = Restaurante::query()
            ->with(['dueno', 'licencias'])
            ->buscar($this->buscar)
            ->orderBy('nombre')
            ->get()
            ->when($filtro, fn ($c) => $c->filter(fn (Restaurante $r) => $r->estadoLicencia() === $filtro))
            ->values();

        $pagina = $this->getPage();

        return new LengthAwarePaginator(
            $todos->forPage($pagina, self::POR_PAGINA),
            $todos->count(),
            self::POR_PAGINA,
            $pagina,
            ['path' => route('admin.restaurantes.index')],
        );
    }

    public function alternarActivo(int $id): void
    {
        $restaurante = Restaurante::findOrFail($id);
        $restaurante->update(['activo' => ! $restaurante->activo]);

        unset($this->restaurantes);

        Flux::toast(
            variant: 'success',
            text: $restaurante->activo
                ? "{$restaurante->nombre} se activó."
                : "{$restaurante->nombre} se desactivó. Su personal ya no podrá entrar.",
        );
    }
}
