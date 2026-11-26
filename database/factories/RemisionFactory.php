<?php

namespace Database\Factories;

use App\Enums\EstadoRemision;
use App\Enums\FormaPago;
use App\Models\Comprador;
use App\Models\Remision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Crea la remisión sin efectos en stock ni cartera. Para el flujo completo
 * usar App\Services\RemisionService.
 *
 * @extends Factory<Remision>
 */
class RemisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero' => fake()->unique()->numberBetween(10_358, 99_999),
            'emitida_en' => now(),
            'comprador_id' => Comprador::factory(),
            'vendedor_id' => User::factory(),
            'conductor' => fake()->name(),
            'placa' => strtoupper(fake()->bothify('???###')),
            'maquina' => '938 G',
            'destino' => 'N/A',
            'forma_pago' => FormaPago::Efectivo,
            'observaciones' => null,
            'total' => 680_000,
            'estado' => EstadoRemision::Emitida,
        ];
    }
}
