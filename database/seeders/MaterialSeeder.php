<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;

/**
 * Catálogo real de productos con su precio por m³ (COP).
 * El stock inicial es de ejemplo hasta que la empresa entregue el inventario real.
 */
class MaterialSeeder extends Seeder
{
    /**
     * @var list<array{nombre: string, precio_m3: int, stock_m3: int, stock_minimo_m3: int, color: string}>
     */
    private const PRODUCTOS = [
        ['nombre' => 'Recebo morado', 'precio_m3' => 70_000, 'stock_m3' => 1_600, 'stock_minimo_m3' => 400, 'color' => '#7b4a5a'],
        ['nombre' => 'Recebo zarandeado', 'precio_m3' => 50_000, 'stock_m3' => 1_500, 'stock_minimo_m3' => 400, 'color' => '#a8714a'],
        ['nombre' => 'Recebo gris', 'precio_m3' => 50_000, 'stock_m3' => 1_300, 'stock_minimo_m3' => 400, 'color' => '#8c8a86'],
        ['nombre' => 'Relleno', 'precio_m3' => 25_000, 'stock_m3' => 1_800, 'stock_minimo_m3' => 500, 'color' => '#9a8d79'],
        ['nombre' => 'Arena de río', 'precio_m3' => 85_000, 'stock_m3' => 1_400, 'stock_minimo_m3' => 400, 'color' => '#c9a55e'],
        ['nombre' => 'Gravilla de río', 'precio_m3' => 90_000, 'stock_m3' => 1_100, 'stock_minimo_m3' => 400, 'color' => '#6f6e6a'],
        ['nombre' => 'Piedra rajón', 'precio_m3' => 120_000, 'stock_m3' => 1_500, 'stock_minimo_m3' => 300, 'color' => '#7d6f5e'],
    ];

    public function run(): void
    {
        foreach (self::PRODUCTOS as $producto) {
            Material::query()->firstOrCreate(['nombre' => $producto['nombre']], $producto);
        }
    }
}
