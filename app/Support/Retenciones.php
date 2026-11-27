<?php

namespace App\Support;

use App\Models\Configuracion;

/**
 * Retenciones que algunos compradores descuentan al pagar: Retención en la fuente
 * (porcentaje) y ReteICA (por mil). Se calculan sobre el subtotal y se restan del total.
 */
class Retenciones
{
    public function __construct(
        public readonly float $porcentajeRetefuente,
        public readonly float $porMilReteica,
    ) {}

    public static function vigentes(): self
    {
        return new self(
            (float) Configuracion::valor('retefuente_porcentaje'),
            (float) Configuracion::valor('reteica_por_mil'),
        );
    }

    /**
     * @return array{retefuente: int, reteica: int, total: int}
     */
    public function calcular(int $subtotal, bool $aplicaRetefuente, bool $aplicaReteica): array
    {
        $retefuente = $aplicaRetefuente ? (int) round($subtotal * $this->porcentajeRetefuente / 100) : 0;
        $reteica = $aplicaReteica ? (int) round($subtotal * $this->porMilReteica / 1000) : 0;

        return [
            'retefuente' => $retefuente,
            'reteica' => $reteica,
            'total' => $subtotal - $retefuente - $reteica,
        ];
    }

    public function etiquetaRetefuente(): string
    {
        return 'Retención en la fuente '.Formato::numero($this->porcentajeRetefuente, fmod($this->porcentajeRetefuente, 1) ? 1 : 0).'%';
    }

    public function etiquetaReteica(): string
    {
        $porcentaje = $this->porMilReteica / 10;

        return 'ICA '.Formato::numero($this->porMilReteica).' × 1.000 ('.Formato::numero($porcentaje, fmod($porcentaje, 1) ? 1 : 0).'%)';
    }
}
