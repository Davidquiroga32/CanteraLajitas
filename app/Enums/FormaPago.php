<?php

namespace App\Enums;

enum FormaPago: string
{
    case Efectivo = 'efectivo';
    case Transferencia = 'transferencia';
    case Credito = 'credito';
    case Anticipo = 'anticipo';
    case Donacion = 'donacion';

    public function label(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Transferencia => 'Transferencia',
            self::Credito => 'Crédito',
            self::Anticipo => 'Anticipo',
            self::Donacion => 'Donación',
        };
    }

    /**
     * Indica si la venta cuenta como ingreso en reportes (las donaciones no).
     */
    public function cuentaComoIngreso(): bool
    {
        return $this !== self::Donacion;
    }
}
