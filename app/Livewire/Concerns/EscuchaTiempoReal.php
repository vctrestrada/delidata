<?php

namespace App\Livewire\Concerns;

use App\Enums\Rol;

/**
 * Suscribe un componente Livewire a los canales privados de Reverb.
 *
 * - Dueño: canal de su negocio (todas sus ventas y gastos).
 * - Encargado y vendedor: canal de su restaurante.
 *
 * El componente define qué eventos le interesan con eventosTiempoReal()
 * y reacciona en alRecibirEvento(). Al terminar, Livewire vuelve a pintar
 * el componente con los datos nuevos.
 */
trait EscuchaTiempoReal
{
    /** Contador que cambia con cada evento; sirve para redibujar gráficas. */
    public int $version = 0;

    /** @return list<string> p. ej. ['venta.registrada', 'gastos.actualizados'] */
    abstract protected function eventosTiempoReal(): array;

    protected function canalTiempoReal(): ?string
    {
        $usuario = auth()->user();

        return match ($usuario?->rolPrincipal()) {
            Rol::Dueno => 'negocio.'.$usuario->negocio_id,
            Rol::Encargado, Rol::Vendedor => $usuario->restaurante_id ? 'restaurante.'.$usuario->restaurante_id : null,
            default => null,
        };
    }

    public function getListeners(): array
    {
        $canal = $this->canalTiempoReal();

        if ($canal === null) {
            return [];
        }

        return collect($this->eventosTiempoReal())
            ->mapWithKeys(fn (string $evento) => ["echo-private:{$canal},.{$evento}" => 'alRecibirEvento'])
            ->all();
    }

    public function alRecibirEvento(array $datos = []): void
    {
        $this->version++;
        $this->despuesDeEvento($datos);
    }

    /** Punto de extensión opcional. */
    protected function despuesDeEvento(array $datos): void {}
}
