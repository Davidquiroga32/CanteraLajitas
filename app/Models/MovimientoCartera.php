<?php

namespace App\Models;

use App\Enums\CuentaCartera;
use App\Enums\FormaPago;
use App\Enums\TipoMovimiento;
use Database\Factories\MovimientoCarteraFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('movimientos_cartera')]
#[Fillable(['comprador_id', 'cuenta', 'tipo', 'valor', 'remision_id', 'medio_pago', 'nota', 'user_id', 'registrado_en'])]
class MovimientoCartera extends Model
{
    /** @use HasFactory<MovimientoCarteraFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cuenta' => CuentaCartera::class,
            'tipo' => TipoMovimiento::class,
            'medio_pago' => FormaPago::class,
            'valor' => 'integer',
            'registrado_en' => 'datetime',
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
     * @return BelongsTo<Remision, $this>
     */
    public function remision(): BelongsTo
    {
        return $this->belongsTo(Remision::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
