<?php

namespace App\Models;

use Database\Factories\RemisionLineaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('remision_lineas')]
#[Fillable(['remision_id', 'material_id', 'cantidad_m3', 'precio_m3', 'subtotal'])]
class RemisionLinea extends Model
{
    /** @use HasFactory<RemisionLineaFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cantidad_m3' => 'decimal:2',
            'precio_m3' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Remision, $this>
     */
    public function remision(): BelongsTo
    {
        return $this->belongsTo(Remision::class);
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
