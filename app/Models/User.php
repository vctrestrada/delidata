<?php

namespace App\Models;

use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property int|null $negocio_id
 * @property int|null $restaurante_id
 * @property bool $activo
 * @property list<string>|null $recorridos_vistos
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    /** Valores por defecto al crear el modelo (iguales a los de la base de datos). */
    protected $attributes = [
        'activo' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'recorridos_vistos' => 'array',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * Rol principal del usuario según la jerarquía definida en App\Enums\Rol.
     */
    public function rolPrincipal(): ?Rol
    {
        foreach (Rol::cases() as $rol) {
            if ($this->hasRole($rol->value)) {
                return $rol;
            }
        }

        return null;
    }

    /** Negocio al que pertenece (null para el super admin). */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    /** Restaurante donde trabaja (solo encargados y vendedores). */
    public function restaurante(): BelongsTo
    {
        return $this->belongsTo(Restaurante::class);
    }

    /** ¿Ya vio el recorrido guiado de esta pantalla? */
    public function haVistoRecorrido(string $clave): bool
    {
        return in_array($clave, $this->recorridos_vistos ?? [], true);
    }

    /** Guarda que el usuario ya vio un recorrido guiado. */
    public function marcarRecorridoVisto(string $clave): void
    {
        if ($this->haVistoRecorrido($clave)) {
            return;
        }

        $this->recorridos_vistos = [...($this->recorridos_vistos ?? []), $clave];
        $this->save();
    }
}
