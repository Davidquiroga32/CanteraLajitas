<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

/**
 * Datos de la empresa y del formato de remisión (tomados del talonario físico).
 * No sobrescribe valores que el administrador ya haya cambiado.
 */
class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Configuracion::DEFAULTS as $clave => $valor) {
            Configuracion::query()->firstOrCreate(['clave' => $clave], ['valor' => $valor]);
        }
    }
}
