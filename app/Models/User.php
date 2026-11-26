<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'cedula', 'cargo', 'role', 'activo', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Rol::class,
            'activo' => 'boolean',
        ];
    }

    /**
     * Remisiones emitidas por este usuario como vendedor.
     *
     * @return HasMany<Remision, $this>
     */
    public function remisiones(): HasMany
    {
        return $this->hasMany(Remision::class, 'vendedor_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === Rol::Admin;
    }

    /**
     * Iniciales para el avatar de la barra lateral.
     */
    public function iniciales(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $parte): string => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->implode('');
    }

    /**
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('activo', true);
    }
}
