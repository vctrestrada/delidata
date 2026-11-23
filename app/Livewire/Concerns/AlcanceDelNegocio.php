<?php

namespace App\Livewire\Concerns;

use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\Restaurante;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Limita lo que ve y modifica cada usuario a su propio negocio:
 *  - El dueño ve todos los restaurantes y todo el personal de su negocio.
 *  - El encargado solo ve su restaurante y a los vendedores de ese restaurante.
 *
 * Así un negocio nunca puede ver ni tocar datos de otro.
 */
trait AlcanceDelNegocio
{
    protected function usuario(): User
    {
        return auth()->user();
    }

    protected function rolActual(): ?Rol
    {
        return $this->usuario()->rolPrincipal();
    }

    public function esEncargado(): bool
    {
        return $this->rolActual() === Rol::Encargado;
    }

    protected function negocioActual(): Negocio
    {
        return $this->usuario()->negocio()->with('licencias')->firstOrFail();
    }

    /** Prefijo de nombres de ruta según el rol: "negocio" o "encargado". */
    public function prefijo(): string
    {
        return $this->esEncargado() ? 'encargado' : 'negocio';
    }

    public function ruta(string $nombre, mixed $parametros = []): string
    {
        return route($this->prefijo().'.'.$nombre, $parametros);
    }

    /** Restaurantes que el usuario puede administrar o asignar. */
    protected function consultaRestaurantes(): Builder
    {
        return Restaurante::query()
            ->delNegocio($this->usuario()->negocio_id)
            ->when($this->esEncargado(), fn (Builder $q) => $q->whereKey($this->usuario()->restaurante_id));
    }

    /** @return Collection<int, Restaurante> */
    public function restaurantesDisponibles(): Collection
    {
        return $this->consultaRestaurantes()->orderBy('nombre')->get();
    }

    /** @return list<Rol> */
    public function rolesAsignables(): array
    {
        return $this->rolActual()?->puedeCrear() ?? [];
    }

    /** Personal que el usuario puede ver y administrar. */
    protected function consultaPersonal(): Builder
    {
        return User::query()
            ->where('negocio_id', $this->usuario()->negocio_id)
            ->whereKeyNot($this->usuario()->id)
            ->role(array_map(fn (Rol $r) => $r->value, $this->rolesAsignables()))
            ->when($this->esEncargado(), fn (Builder $q) => $q->where('restaurante_id', $this->usuario()->restaurante_id));
    }

    protected function autorizarRestaurante(Restaurante $restaurante): void
    {
        abort_unless($this->consultaRestaurantes()->whereKey($restaurante->id)->exists(), 404);
    }

    protected function autorizarUsuario(User $usuario): void
    {
        abort_unless($this->consultaPersonal()->whereKey($usuario->id)->exists(), 404);
    }
}
