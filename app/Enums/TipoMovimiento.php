<?php

namespace App\Enums;

enum TipoMovimiento: string
{
    /** Remisión a crédito: aumenta la deuda. */
    case CargoRemision = 'cargo_remision';

    /** Pago del comprador sobre su deuda. */
    case Abono = 'abono';

    /** Dinero recibido por adelantado: aumenta el saldo a favor. */
    case AnticipoRecibido = 'anticipo_recibido';

    /** Remisión pagada con anticipo: descuenta del saldo a favor. */
    case ConsumoRemision = 'consumo_remision';

    /** Reversa del efecto de una remisión anulada. */
    case ReversoAnulacion = 'reverso_anulacion';

    public function label(): string
    {
        return match ($this) {
            self::CargoRemision => 'Remisión a crédito',
            self::Abono => 'Abono',
            self::AnticipoRecibido => 'Anticipo recibido',
            self::ConsumoRemision => 'Remisión con anticipo',
            self::ReversoAnulacion => 'Reverso por anulación',
        };
    }
}
