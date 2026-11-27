<?php

namespace App\Models;

use App\Enums\CuentaCartera;
use App\Enums\TipoDocumento;
use Database\Factories\CompradorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('compradores')]
#[Fillable(['nombre', 'tipo_documento', 'documento', 'ciudad', 'retefuente', 'reteica', 'activo'])]
class Comprador extends Model
{
    /** @use HasFactory<CompradorFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_documento' => TipoDocumento::class,
            'activo' => 'boolean',
            'retefuente' => 'boolean',
            'reteica' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Remision, $this>
     */
    public function remisiones(): HasMany
    {
        return $this->hasMany(Remision::class);
    }

    /**
     * @return HasMany<MovimientoCartera, $this>
     */
    public function movimientosCartera(): HasMany
    {
        return $this->hasMany(MovimientoCartera::class);
    }

    public function aplicaRetenciones(): bool
    {
        return $this->retefuente || $this->reteica;
    }

    /**
     * Documento con su tipo, como se imprime en la remisión (p. ej. "NIT 900.412.118-3").
     */
    public function documentoCompleto(): string
    {
        return $this->tipo_documento->value.' '.$this->documento;
    }

    /**
     * Lo que el comprador le debe a la empresa (compras a crédito menos abonos).
     *
     * Con `bloquear` (dentro de una transacción) lee el último saldo confirmado y
     * bloquea esos movimientos hasta que la transacción termine.
     */
    public function saldoCredito(bool $bloquear = false): int
    {
        return $this->saldo(CuentaCartera::Credito, $bloquear);
    }

    /**
     * Saldo a favor disponible por anticipos. Ver `saldoCredito` sobre `bloquear`.
     */
    public function saldoAnticipo(bool $bloquear = false): int
    {
        return $this->saldo(CuentaCartera::Anticipo, $bloquear);
    }

    private function saldo(CuentaCartera $cuenta, bool $bloquear): int
    {
        return (int) $this->movimientosCartera()
            ->where('cuenta', $cuenta)
            ->when($bloquear, fn ($q) => $q->lockForUpdate())
            ->sum('valor');
    }

    /**
     * Agrega `saldo_credito` y `saldo_anticipo` en la misma consulta (para listados).
     *
     * @param  Builder<Comprador>  $query
     */
    #[Scope]
    protected function conSaldos(Builder $query): void
    {
        $query->withSum(['movimientosCartera as saldo_credito' => fn (Builder $q) => $q->where('cuenta', CuentaCartera::Credito)], 'valor')
            ->withSum(['movimientosCartera as saldo_anticipo' => fn (Builder $q) => $q->where('cuenta', CuentaCartera::Anticipo)], 'valor');
    }

    /**
     * Busca por nombre o documento.
     *
     * @param  Builder<Comprador>  $query
     */
    #[Scope]
    protected function buscar(Builder $query, ?string $termino): void
    {
        if (blank($termino)) {
            return;
        }

        $query->where(function (Builder $q) use ($termino): void {
            $q->where('nombre', 'like', "%{$termino}%")
                ->orWhere('documento', 'like', "%{$termino}%")
                ->orWhere('ciudad', 'like', "%{$termino}%");
        });
    }
}
