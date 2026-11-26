<?php

namespace App\Services;

use App\Enums\CuentaCartera;
use App\Enums\EstadoRemision;
use App\Enums\FormaPago;
use App\Enums\TipoMovimiento;
use App\Models\Auditoria;
use App\Models\Comprador;
use App\Models\Configuracion;
use App\Models\Material;
use App\Models\Remision;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Emisión y anulación de remisiones, con sus efectos en stock y cartera.
 */
class RemisionService
{
    /**
     * Emite una remisión. El vendedor es el usuario que la registra y el precio
     * de cada línea es el precio vigente del material.
     *
     * @param  array{
     *     comprador_id?: int|null,
     *     comprador?: array{nombre: string, tipo_documento: string, documento: string, ciudad?: string|null},
     *     conductor: string,
     *     placa: string,
     *     maquina: string,
     *     destino: string,
     *     forma_pago: FormaPago|string,
     *     observaciones?: string|null,
     *     lineas: list<array{material_id: int, cantidad_m3: float|string}>,
     * }  $datos
     *
     * @throws ValidationException si el anticipo del comprador no alcanza.
     */
    public function emitir(User $vendedor, array $datos, ?CarbonInterface $emitidaEn = null): Remision
    {
        return DB::transaction(function () use ($vendedor, $datos, $emitidaEn): Remision {
            $comprador = $this->resolverComprador($datos);
            $formaPago = $datos['forma_pago'] instanceof FormaPago ? $datos['forma_pago'] : FormaPago::from($datos['forma_pago']);

            $materiales = Material::query()
                ->whereKey(array_column($datos['lineas'], 'material_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lineas = collect($datos['lineas'])->map(function (array $linea) use ($materiales): array {
                $material = $materiales->get($linea['material_id']);
                $cantidad = round((float) $linea['cantidad_m3'], 2);

                return [
                    'material_id' => $material->id,
                    'cantidad_m3' => $cantidad,
                    'precio_m3' => $material->precio_m3,
                    'subtotal' => (int) round($cantidad * $material->precio_m3),
                ];
            });

            $this->validarStock($materiales, $lineas);

            $total = (int) $lineas->sum('subtotal');

            if ($formaPago === FormaPago::Anticipo) {
                $this->validarTieneAnticipo($comprador);
            }

            $remision = Remision::query()->create([
                'numero' => $this->siguienteNumero(),
                'emitida_en' => $emitidaEn ?? now(),
                'comprador_id' => $comprador->id,
                'vendedor_id' => $vendedor->id,
                'conductor' => $datos['conductor'],
                'placa' => mb_strtoupper($datos['placa']),
                'maquina' => $datos['maquina'],
                'destino' => $datos['destino'],
                'forma_pago' => $formaPago,
                'observaciones' => $datos['observaciones'] ?? null,
                'total' => $total,
                'estado' => EstadoRemision::Emitida,
            ]);

            $remision->lineas()->createMany($lineas->all());

            foreach ($lineas as $linea) {
                Material::query()->whereKey($linea['material_id'])->decrement('stock_m3', $linea['cantidad_m3']);
            }

            $this->registrarEfectoEnCartera($remision, $vendedor, $emitidaEn);

            Auditoria::registrar('remision.emitida', $remision, ['numero' => $remision->numero, 'total' => $total]);

            return $remision->load(['lineas.material', 'comprador', 'vendedor']);
        });
    }

    /**
     * Anula una remisión: repone el stock y reversa sus movimientos de cartera.
     *
     * @throws ValidationException si ya estaba anulada.
     */
    public function anular(Remision $remision, User $usuario, string $motivo): Remision
    {
        return DB::transaction(function () use ($remision, $usuario, $motivo): Remision {
            $remision = Remision::query()->lockForUpdate()->findOrFail($remision->id);

            if ($remision->estaAnulada()) {
                throw ValidationException::withMessages(['remision' => 'La remisión ya está anulada.']);
            }

            foreach ($remision->lineas as $linea) {
                Material::query()->whereKey($linea->material_id)->increment('stock_m3', $linea->cantidad_m3);
            }

            foreach ($remision->movimientosCartera()->get() as $movimiento) {
                $remision->movimientosCartera()->create([
                    'comprador_id' => $movimiento->comprador_id,
                    'cuenta' => $movimiento->cuenta,
                    'tipo' => TipoMovimiento::ReversoAnulacion,
                    'valor' => -$movimiento->valor,
                    'nota' => 'Anulación remisión '.$remision->numeroFormateado(),
                    'user_id' => $usuario->id,
                    'registrado_en' => now(),
                ]);
            }

            $remision->update([
                'estado' => EstadoRemision::Anulada,
                'anulada_en' => now(),
                'anulada_por_id' => $usuario->id,
                'motivo_anulacion' => $motivo,
            ]);

            Auditoria::registrar('remision.anulada', $remision, ['numero' => $remision->numero, 'motivo' => $motivo]);

            return $remision;
        });
    }

    /**
     * Siguiente número consecutivo. Empieza en el número configurado
     * (continuación del talonario físico).
     */
    public function siguienteNumero(): int
    {
        $ultimo = Remision::query()->lockForUpdate()->max('numero');
        $inicial = (int) Configuracion::valor('remision_numero_inicial');

        return $ultimo ? max($ultimo + 1, $inicial) : $inicial;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function resolverComprador(array $datos): Comprador
    {
        if (! empty($datos['comprador_id'])) {
            return Comprador::query()->findOrFail($datos['comprador_id']);
        }

        $nuevo = $datos['comprador'];

        return Comprador::query()->firstOrCreate(
            ['documento' => trim($nuevo['documento'])],
            [
                'nombre' => trim($nuevo['nombre']),
                'tipo_documento' => $nuevo['tipo_documento'],
                'ciudad' => $nuevo['ciudad'] ?? null,
            ],
        );
    }

    /**
     * No se vende un material inactivo ni más de lo que hay en patio.
     *
     * @param  Collection<int, Material>  $materiales
     * @param  Collection<int, array{material_id: int, cantidad_m3: float}>  $lineas
     *
     * @throws ValidationException
     */
    private function validarStock($materiales, $lineas): void
    {
        $errores = [];

        foreach ($lineas->groupBy('material_id') as $materialId => $grupo) {
            $material = $materiales->get($materialId);
            $pedido = (float) $grupo->sum('cantidad_m3');
            $disponible = (float) $material->stock_m3;

            if (! $material->activo || $disponible <= 0) {
                $errores['lineas'][] = $material->nombre.' no tiene existencias en patio. Pida al administrador que actualice el stock.';
            } elseif ($pedido > $disponible) {
                $errores['lineas'][] = 'Solo quedan '.number_format($disponible, 2, ',', '.').' m³ de '.$material->nombre.' y la remisión pide '.number_format($pedido, 2, ',', '.').' m³.';
            }
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    /**
     * Para pagar con anticipo el comprador debe tener saldo a favor.
     *
     * @throws ValidationException
     */
    private function validarTieneAnticipo(Comprador $comprador): void
    {
        if ($comprador->saldoAnticipo() <= 0) {
            throw ValidationException::withMessages([
                'forma_pago' => $comprador->nombre.' no tiene saldo de anticipo. Elija otra forma de pago (por ejemplo Crédito).',
            ]);
        }
    }

    /**
     * Crédito: todo el total pasa a deuda. Anticipo: se descuenta del saldo a
     * favor y, si no alcanza, la diferencia pasa a deuda (el comprador queda como deudor).
     */
    private function registrarEfectoEnCartera(Remision $remision, User $usuario, ?CarbonInterface $fecha): void
    {
        $alAnticipo = 0;
        $aCredito = 0;

        if ($remision->forma_pago === FormaPago::Credito) {
            $aCredito = $remision->total;
        } elseif ($remision->forma_pago === FormaPago::Anticipo) {
            $alAnticipo = min($remision->comprador->saldoAnticipo(), $remision->total);
            $aCredito = $remision->total - $alAnticipo;
        }

        $base = [
            'comprador_id' => $remision->comprador_id,
            'nota' => 'Remisión '.$remision->numeroFormateado(),
            'user_id' => $usuario->id,
            'registrado_en' => $fecha ?? now(),
        ];

        if ($alAnticipo > 0) {
            $remision->movimientosCartera()->create([
                ...$base,
                'cuenta' => CuentaCartera::Anticipo,
                'tipo' => TipoMovimiento::ConsumoRemision,
                'valor' => -$alAnticipo,
            ]);
        }

        if ($aCredito > 0) {
            $remision->movimientosCartera()->create([
                ...$base,
                'cuenta' => CuentaCartera::Credito,
                'tipo' => TipoMovimiento::CargoRemision,
                'valor' => $aCredito,
                'nota' => $alAnticipo > 0 ? $base['nota'].' (diferencia no cubierta por anticipo)' : $base['nota'],
            ]);
        }
    }
}
