<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Remision;
use App\Models\RemisionLinea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RemisionLinea>
 */
class RemisionLineaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'remision_id' => Remision::factory(),
            'material_id' => Material::factory(),
            'cantidad_m3' => 10,
            'precio_m3' => 68_000,
            'subtotal' => 680_000,
        ];
    }
}
