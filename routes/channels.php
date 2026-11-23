<?php

use App\Enums\Rol;
use App\Models\Restaurante;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canales privados de Delidata
|--------------------------------------------------------------------------
| negocio.{id}      → solo el dueño de ese negocio.
| restaurante.{id}  → el encargado y los vendedores de ese restaurante,
|                     y el dueño del negocio al que pertenece.
| Un usuario desactivado no puede suscribirse a nada.
*/

Broadcast::channel('negocio.{negocioId}', function (User $usuario, int $negocioId) {
    return $usuario->activo
        && $usuario->negocio_id === $negocioId
        && $usuario->hasRole(Rol::Dueno->value);
});

Broadcast::channel('restaurante.{restauranteId}', function (User $usuario, int $restauranteId) {
    if (! $usuario->activo) {
        return false;
    }

    if ($usuario->hasRole(Rol::Dueno->value)) {
        return Restaurante::whereKey($restauranteId)->where('negocio_id', $usuario->negocio_id)->exists();
    }

    return $usuario->restaurante_id === $restauranteId
        && $usuario->hasAnyRole([Rol::Encargado->value, Rol::Vendedor->value]);
});
