<?php

namespace App\Models;

use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('materiales')]
#[Fillable(['nombre', 'precio_m3', 'stock_m3', 'stock_minimo_m3', 'color', 'activo'])]
class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio_m3' => 'integer',
            'stock_m3' => 'decimal:2',
            'stock_minimo_m3' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<RemisionLinea, $this>
     */
    public function lineas(): HasMany
    {
        return $this->hasMany(RemisionLinea::class);
    }

    /**
     * El stock en patio está por debajo del mínimo definido.
     */
    public function stockBajo(): bool
    {
        return (float) $this->stock_m3 < (float) $this->stock_minimo_m3;
    }

    /**
     * @param  Builder<Material>  $query
     */
    #[Scope]
    protected function activos(Builder $query): void
    {
        $query->where('activo', true);
    }

    /**
     * Materiales que se pueden ofrecer en una remisión: activos y con stock en patio.
     *
     * @param  Builder<Material>  $query
     */
    #[Scope]
    protected function disponiblesParaVenta(Builder $query): void
    {
        $query->where('activo', true)->where('stock_m3', '>', 0);
    }
}
