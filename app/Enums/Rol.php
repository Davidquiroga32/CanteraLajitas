<?php

namespace App\Enums;

enum Rol: string
{
    case Admin = 'admin';
    case Vendedor = 'vendedor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Vendedor => 'Vendedor',
        };
    }
}
