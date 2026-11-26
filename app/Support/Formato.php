<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Formatos de Colombia (es-CO) para mostrar dinero y cantidades.
 */
class Formato
{
    /**
     * Dinero en pesos sin decimales: "$ 1.234.567".
     */
    public static function dinero(int|float|string|null $valor): string
    {
        return '$ '.number_format((float) $valor, 0, ',', '.');
    }

    /**
     * Número con separador de miles y los decimales pedidos: "1.234,5".
     */
    public static function numero(int|float|string|null $valor, int $decimales = 0): string
    {
        return number_format((float) $valor, $decimales, ',', '.');
    }

    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    /**
     * Fecha corta: "23 nov".
     */
    public static function fechaCorta(CarbonInterface $fecha): string
    {
        return $fecha->day.' '.self::MESES[$fecha->month - 1];
    }

    /**
     * Fecha con año: "23 nov 2026".
     */
    public static function fecha(CarbonInterface $fecha): string
    {
        return self::fechaCorta($fecha).' '.$fecha->year;
    }

    /**
     * Valores grandes abreviados para ejes de gráficos: "1,5 M".
     */
    public static function millones(int|float $valor): string
    {
        return $valor == 0 ? '0' : self::numero($valor / 1_000_000, 1).' M';
    }

    /**
     * Cantidad en m³ sin decimales cuando es entera: "12" o "12,5".
     */
    public static function m3(int|float|string|null $valor): string
    {
        $numero = (float) $valor;

        return floor($numero) == $numero ? self::numero($numero) : rtrim(rtrim(self::numero($numero, 2), '0'), ',');
    }
}
