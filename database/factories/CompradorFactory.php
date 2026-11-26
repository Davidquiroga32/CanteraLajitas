<?php

namespace Database\Factories;

use App\Enums\TipoDocumento;
use App\Models\Comprador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comprador>
 */
class CompradorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'tipo_documento' => TipoDocumento::CC,
            'documento' => number_format(fake()->unique()->numberBetween(10_000_000, 99_999_999), 0, ',', '.'),
            'ciudad' => 'Villavicencio',
            'activo' => true,
        ];
    }

    /**
     * Comprador empresa (con NIT).
     */
    public function empresa(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => fake()->company().' S.A.S.',
            'tipo_documento' => TipoDocumento::NIT,
            'documento' => fake()->unique()->numerify('9##.###.###-#'),
        ]);
    }
}
