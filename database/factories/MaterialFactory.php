<?php

namespace Database\Factories;

use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Material '.fake()->unique()->word(),
            'precio_m3' => fake()->randomElement([25_000, 50_000, 70_000, 85_000, 90_000, 120_000]),
            'stock_m3' => 500,
            'stock_minimo_m3' => 100,
            'color' => fake()->hexColor(),
            'activo' => true,
        ];
    }
}
