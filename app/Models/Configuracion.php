<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Datos de la empresa y del formato de remisión, editables por el administrador.
 */
#[Table('configuracion')]
#[Fillable(['clave', 'valor'])]
class Configuracion extends Model
{
    /**
     * Valores por defecto (datos del talonario físico actual).
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'empresa_nombre' => 'CANTERA LAJITAS S.A.S.',
        'empresa_nit' => '900.897.394-0',
        'empresa_mina' => 'MINA LA VACA',
        'empresa_direccion' => 'Calle 27 # 23-49 Barrio San Jorge 1',
        'empresa_telefonos' => '317 370 3814 - 315 319 9366',
        'remision_titulo' => 'REMISIÓN DE COMPRA',
        'remision_codigo' => 'LAJ-FT-10',
        'remision_version' => '03',
        'remision_aprobado' => '05-ENE-2026',
        'remision_legal' => 'Contrato de Concesión PLACA IGU-10411 · Agencia Nacional de Minería / Lic. Ambiental Res. No. 205 Corporación CDA',
        'remision_numero_inicial' => '1',
        'remision_copias' => '3',
    ];

    public static function valor(string $clave): ?string
    {
        return static::query()->where('clave', $clave)->value('valor') ?? (self::DEFAULTS[$clave] ?? null);
    }

    public static function guardar(string $clave, ?string $valor): void
    {
        static::query()->updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }
}
