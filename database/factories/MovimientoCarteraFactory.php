<?php

namespace Database\Factories;

use App\Enums\CuentaCartera;
use App\Enums\FormaPago;
use App\Enums\TipoMovimiento;
use App\Models\Comprador;
use App\Models\MovimientoCartera;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovimientoCartera>
 */
class MovimientoCarteraFactory extends Factory
{
    /**
     * Por defecto: un anticipo recibido en efectivo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'comprador_id' => Comprador::factory(),
            'cuenta' => CuentaCartera::Anticipo,
            'tipo' => TipoMovimiento::AnticipoRecibido,
            'valor' => 1_000_000,
            'medio_pago' => FormaPago::Efectivo,
            'nota' => null,
            'user_id' => User::factory(),
            'registrado_en' => now(),
        ];
    }

    /**
     * Deuda por una compra a crédito.
     */
    public function deuda(int $valor = 1_000_000): static
    {
        return $this->state(fn (array $attributes) => [
            'cuenta' => CuentaCartera::Credito,
            'tipo' => TipoMovimiento::CargoRemision,
            'valor' => $valor,
            'medio_pago' => null,
        ]);
    }
}
