<?php

namespace App\Livewire\Admin\Suscripciones;

use App\Enums\EstadoLicencia;
use App\Models\Negocio;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Suscripciones')]
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
    public function negocios(): LengthAwarePaginator
    {
        $filtro = EstadoLicencia::tryFrom($this->estado);

        $todos = Negocio::query()
            ->with(['dueno', 'licencias'])
            ->buscar($this->buscar)
            ->orderBy('nombre')
            ->get()
            ->when($filtro, fn ($c) => $c->filter(fn (Negocio $n) => $n->estadoLicencia() === $filtro))
            ->values();

        $pagina = $this->getPage();

        return new LengthAwarePaginator(
            $todos->forPage($pagina, self::POR_PAGINA),
            $todos->count(),
            self::POR_PAGINA,
            $pagina,
            ['path' => route('admin.suscripciones.index')],
        );
    }

    public function alternarActivo(int $id): void
    {
        $negocio = Negocio::findOrFail($id);
        $negocio->update(['activo' => ! $negocio->activo]);

        unset($this->negocios);

        Flux::toast(
            variant: 'success',
            text: $negocio->activo
                ? "{$negocio->nombre} se activó."
                : "{$negocio->nombre} se desactivó. Su dueño y su personal ya no podrán entrar.",
        );
    }
}
