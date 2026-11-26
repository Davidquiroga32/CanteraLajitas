<?php

namespace App\Enums;

enum EstadoRemision: string
{
    case Emitida = 'emitida';
    case Anulada = 'anulada';

    public function label(): string
    {
        return match ($this) {
            self::Emitida => 'Emitida',
            self::Anulada => 'Anulada',
        };
    }
}
