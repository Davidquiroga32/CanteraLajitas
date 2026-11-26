<?php

namespace Database\Seeders;

use App\Enums\TipoDocumento;
use App\Models\Comprador;
use Illuminate\Database\Seeder;

/**
 * Compradores ficticios de demostración (solo desarrollo).
 */
class CompradorSeeder extends Seeder
{
    /**
     * @var list<array{0: string, 1: TipoDocumento, 2: string, 3: string}>
     */
    private const COMPRADORES = [
        ['Constructora Llanos del Sol S.A.S.', TipoDocumento::NIT, '900.412.118-3', 'Villavicencio'],
        ['Ferretería El Maestro', TipoDocumento::NIT, '822.003.917-1', 'Villavicencio'],
        ['Ing. Carlos Alberto Rojas', TipoDocumento::CC, '17.345.220', 'Restrepo'],
        ['Consorcio Vial Orinoquía', TipoDocumento::NIT, '901.220.664-9', 'Acacías'],
        ['Inversiones Morichal Ltda.', TipoDocumento::NIT, '800.554.310-2', 'Villavicencio'],
        ['Marta Cecilia Bautista', TipoDocumento::CC, '40.215.667', 'Cumaral'],
        ['Prefabricados del Meta', TipoDocumento::NIT, '892.008.451-6', 'Villavicencio'],
        ['Hugo Armando Salcedo', TipoDocumento::CC, '86.050.912', 'Villavicencio'],
        ['Agregados y Obras Ariari', TipoDocumento::NIT, '901.075.339-5', 'Granada'],
        ['Junta de Acción Comunal Vereda La Vaca', TipoDocumento::NIT, '822.007.551-0', 'Villavicencio'],
    ];

    public function run(): void
    {
        foreach (self::COMPRADORES as [$nombre, $tipo, $documento, $ciudad]) {
            Comprador::query()->firstOrCreate(
                ['documento' => $documento],
                ['nombre' => $nombre, 'tipo_documento' => $tipo, 'ciudad' => $ciudad],
            );
        }
    }
}
