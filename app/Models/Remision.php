<?php

namespace App\Models;

use App\Enums\EstadoRemision;
use App\Enums\FormaPago;
use Database\Factories\RemisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('remisiones')]
#[Fillable([
    'numero', 'emitida_en', 'comprador_id', 'vendedor_id', 'conductor', 'placa', 'maquina',
    'destino', 'forma_pago', 'observaciones', 'subtotal', 'retefuente', 'reteica', 'total',
    'diferencia_forma_pago', 'diferencia_valor', 'estado',
    'anulada_en', 'anulada_por_id', 'motivo_anulacion',
])]
class Remision extends Model
{
    /** @use HasFactory<RemisionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => 'emitida',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'emitida_en' => 'datetime',
            'anulada_en' => 'datetime',
            'forma_pago' => FormaPago::class,
            'estado' => EstadoRemision::class,
            'subtotal' => 'integer',
            'retefuente' => 'integer',
            'reteica' => 'integer',
            'total' => 'integer',
            'diferencia_forma_pago' => FormaPago::class,
            'diferencia_valor' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Comprador, $this>
     */
    public function comprador(): BelongsTo
    {
        return $this->belongsTo(Comprador::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por_id');
    }

    /**
     * @return HasMany<RemisionLinea, $this>
     */
    public function lineas(): HasMany
    {
        return $this->hasMany(RemisionLinea::class);
    }

    /**
     * @return HasMany<MovimientoCartera, $this>
     */
    public function movimientosCartera(): HasMany
    {
        return $this->hasMany(MovimientoCartera::class);
    }

    /**
     * Número como se imprime (p. ej. 000001).
     */
    public function numeroFormateado(): string
    {
        return str_pad((string) $this->numero, 6, '0', STR_PAD_LEFT);
    }

    public function estaAnulada(): bool
    {
        return $this->estado === EstadoRemision::Anulada;
    }

    /**
     * Total de metros cúbicos despachados en la remisión.
     */
    public function totalM3(): float
    {
        return (float) $this->lineas->sum('cantidad_m3');
    }

    /**
     * @param  Builder<Remision>  $query
     */
    #[Scope]
    protected function emitidas(Builder $query): void
    {
        $query->where('estado', EstadoRemision::Emitida);
    }

    /**
     * Remisiones que el usuario puede ver: el administrador ve todas, el vendedor solo las suyas.
     *
     * @param  Builder<Remision>  $query
     */
    #[Scope]
    protected function visiblesPara(Builder $query, User $user): void
    {
        if (! $user->isAdmin()) {
            $query->whereBelongsTo($user, 'vendedor');
        }
    }
}
