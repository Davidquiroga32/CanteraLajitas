<?php

namespace App\Services;

use App\Enums\CuentaCartera;
use App\Enums\FormaPago;
use App\Enums\TipoMovimiento;
use App\Models\Auditoria;
use App\Models\Comprador;
use App\Models\MovimientoCartera;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Abonos a la deuda (crédito) y anticipos recibidos de los compradores.
 */
class CarteraService
{
    /**
     * Registra un pago del comprador que reduce su deuda.
     *
     * @throws ValidationException si el abono supera lo que debe.
     */
    public function registrarAbono(
        Comprador $comprador,
        int $valor,
        FormaPago $medio,
        User $usuario,
        ?string $nota = null,
        ?CarbonInterface $fecha = null,
    ): MovimientoCartera {
        $this->validarMedio($medio);

        return DB::transaction(function () use ($comprador, $valor, $medio, $usuario, $nota, $fecha): MovimientoCartera {
            $deuda = $comprador->saldoCredito();

            if ($valor <= 0 || $valor > $deuda) {
                throw ValidationException::withMessages([
                    'valor' => 'El abono debe ser mayor a cero y no puede superar la deuda actual ($ '.number_format($deuda, 0, ',', '.').').',
                ]);
            }

            $movimiento = $comprador->movimientosCartera()->create([
                'cuenta' => CuentaCartera::Credito,
                'tipo' => TipoMovimiento::Abono,
                'valor' => -$valor,
                'medio_pago' => $medio,
                'nota' => $nota,
                'user_id' => $usuario->id,
                'registrado_en' => $fecha ?? now(),
            ]);

            Auditoria::registrar('cartera.abono', $comprador, ['valor' => $valor, 'medio' => $medio->value]);

            return $movimiento;
        });
    }

    /**
     * Registra dinero entregado por adelantado (saldo a favor del comprador).
     *
     * @throws ValidationException si el valor no es positivo.
     */
    public function registrarAnticipo(
        Comprador $comprador,
        int $valor,
        FormaPago $medio,
        User $usuario,
        ?string $nota = null,
        ?CarbonInterface $fecha = null,
    ): MovimientoCartera {
        $this->validarMedio($medio);

        if ($valor <= 0) {
            throw ValidationException::withMessages(['valor' => 'El anticipo debe ser mayor a cero.']);
        }

        return DB::transaction(function () use ($comprador, $valor, $medio, $usuario, $nota, $fecha): MovimientoCartera {
            $movimiento = $comprador->movimientosCartera()->create([
                'cuenta' => CuentaCartera::Anticipo,
                'tipo' => TipoMovimiento::AnticipoRecibido,
                'valor' => $valor,
                'medio_pago' => $medio,
                'nota' => $nota,
                'user_id' => $usuario->id,
                'registrado_en' => $fecha ?? now(),
            ]);

            Auditoria::registrar('cartera.anticipo', $comprador, ['valor' => $valor, 'medio' => $medio->value]);

            return $movimiento;
        });
    }

    /**
     * Los abonos y anticipos solo se reciben en efectivo o transferencia.
     */
    private function validarMedio(FormaPago $medio): void
    {
        if (! in_array($medio, [FormaPago::Efectivo, FormaPago::Transferencia], true)) {
            throw ValidationException::withMessages(['medio_pago' => 'El pago debe ser en efectivo o por transferencia.']);
        }
    }
}
