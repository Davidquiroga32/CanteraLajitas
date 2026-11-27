<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Table('auditoria', timestamps: false)]
#[Fillable(['user_id', 'accion', 'auditable_type', 'auditable_id', 'datos', 'ip', 'created_at'])]
class Auditoria extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Registra una acción del usuario autenticado (o de `$usuario`, cuando aún no
     * hay sesión, como en un intento de ingreso).
     *
     * @param  array<string, mixed>|null  $datos
     */
    public static function registrar(string $accion, ?Model $auditable = null, ?array $datos = null, ?User $usuario = null): self
    {
        return static::query()->create([
            'user_id' => $usuario?->id ?? auth()->id(),
            'accion' => $accion,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'datos' => $datos,
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
