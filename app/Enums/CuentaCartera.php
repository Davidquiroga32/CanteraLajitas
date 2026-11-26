<?php

namespace App\Enums;

enum CuentaCartera: string
{
    /** Lo que el comprador le debe a la empresa. */
    case Credito = 'credito';

    /** Saldo a favor que el comprador dejó como anticipo. */
    case Anticipo = 'anticipo';
}
