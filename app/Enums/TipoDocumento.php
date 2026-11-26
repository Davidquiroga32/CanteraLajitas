<?php

namespace App\Enums;

enum TipoDocumento: string
{
    case CC = 'CC';
    case NIT = 'NIT';

    /**
     * Tipo de comprador que se muestra en pantalla: empresa (NIT) o persona (CC).
     */
    public function tipoComprador(): string
    {
        return match ($this) {
            self::CC => 'Persona',
            self::NIT => 'Empresa',
        };
    }
}
