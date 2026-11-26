<?php

namespace App\Services;

use App\Enums\CuentaCartera;
use App\Enums\FormaPago;
use App\Models\Comprador;
use App\Models\Material;
use App\Models\MovimientoCartera;
use App\Models\Remision;
use App\Models\RemisionLinea;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Cifras del panel de inicio. El administrador ve toda la operación;
 * el vendedor solo sus remisiones. Las donaciones no cuentan como ingreso.
 */
class DashboardService
{
    /**
     * @return array{
     *     hoy: array{valor: int, remisiones: int},
     *     mes: array{valor: int, remisiones: int, m3: float, ticket: int, variacion: float|null, inicio: CarbonInterface},
     *     serie: list<array{dia: CarbonInterface, valor: int, remisiones: int}>,
     *     materiales: Collection<int, array{material: Material, m3: float, valor: int}>,
     *     ultimas: Collection<int, Remision>,
     *     compradores: Collection<int, array{comprador: Comprador, valor: int, remisiones: int}>,
     *     admin: array{deuda: int, deudores: int, anticipos: int, stock_bajo: Collection<int, Material>}|null,
     * }
     */
    public function resumen(User $usuario, ?CarbonInterface $ahora = null): array
    {
        $ahora = CarbonImmutable::instance($ahora ?? now());
        $inicioMes = $ahora->startOfMonth();
        $inicioMesAnterior = $inicioMes->subMonthNoOverflow();
        $mismoMomentoMesAnterior = $ahora->subMonthNoOverflow();

        $ventasMes = $this->ingresos($usuario)->whereBetween('emitida_en', [$inicioMes, $ahora]);
        $valorMes = (int) (clone $ventasMes)->sum('total');
        $remisionesMes = (clone $ventasMes)->count();
        $valorMesAnterior = (int) $this->ingresos($usuario)->whereBetween('emitida_en', [$inicioMesAnterior, $mismoMomentoMesAnterior])->sum('total');

        $materiales = $this->materialesVendidos($usuario, $inicioMes, $ahora);

        return [
            'hoy' => [
                'valor' => (int) $this->ingresos($usuario)->whereBetween('emitida_en', [$ahora->startOfDay(), $ahora->endOfDay()])->sum('total'),
                'remisiones' => $this->remisiones($usuario)->whereBetween('emitida_en', [$ahora->startOfDay(), $ahora->endOfDay()])->count(),
            ],
            'mes' => [
                'valor' => $valorMes,
                'remisiones' => $remisionesMes,
                'm3' => (float) $materiales->sum('m3'),
                'ticket' => $remisionesMes ? intdiv($valorMes, $remisionesMes) : 0,
                'variacion' => $valorMesAnterior ? round(($valorMes - $valorMesAnterior) / $valorMesAnterior * 100, 1) : null,
                'inicio' => $inicioMes,
            ],
            'serie' => $this->serieDiaria($usuario, $ahora->subDays(29)->startOfDay(), $ahora),
            'materiales' => $materiales,
            'ultimas' => $this->remisiones($usuario)->with('comprador')->latest('emitida_en')->latest('id')->limit(6)->get(),
            'compradores' => $this->mejoresCompradores($usuario, $inicioMes, $ahora),
            'admin' => $usuario->isAdmin() ? $this->resumenAdmin() : null,
        ];
    }

    /**
     * @return Builder<Remision>
     */
    private function remisiones(User $usuario): Builder
    {
        return Remision::query()->emitidas()->visiblesPara($usuario);
    }

    /**
     * @return Builder<Remision>
     */
    private function ingresos(User $usuario): Builder
    {
        return $this->remisiones($usuario)->where('forma_pago', '!=', FormaPago::Donacion);
    }

    /**
     * Ventas por día, con los días sin ventas en cero.
     *
     * @return list<array{dia: CarbonInterface, valor: int, remisiones: int}>
     */
    private function serieDiaria(User $usuario, CarbonImmutable $desde, CarbonImmutable $hasta): array
    {
        $porDia = $this->ingresos($usuario)
            ->whereBetween('emitida_en', [$desde, $hasta])
            ->selectRaw('date(emitida_en) as dia, sum(total) as valor, count(*) as remisiones')
            ->groupByRaw('date(emitida_en)')
            ->toBase()
            ->get()
            ->keyBy('dia');

        $serie = [];

        for ($dia = $desde; $dia->lte($hasta); $dia = $dia->addDay()) {
            $fila = $porDia->get($dia->toDateString());
            $serie[] = [
                'dia' => $dia,
                'valor' => (int) ($fila->valor ?? 0),
                'remisiones' => (int) ($fila->remisiones ?? 0),
            ];
        }

        return $serie;
    }

    /**
     * Metros cúbicos y valor por material (incluye donaciones en m³ despachados).
     *
     * @return Collection<int, array{material: Material, m3: float, valor: int}>
     */
    private function materialesVendidos(User $usuario, CarbonInterface $desde, CarbonInterface $hasta): Collection
    {
        return RemisionLinea::query()
            ->whereHas('remision', fn (Builder $q) => $q->emitidas()->visiblesPara($usuario)->whereBetween('emitida_en', [$desde, $hasta]))
            ->selectRaw('material_id, sum(cantidad_m3) as m3, sum(subtotal) as valor')
            ->groupBy('material_id')
            ->with('material')
            ->get()
            ->map(fn (RemisionLinea $fila): array => [
                'material' => $fila->material,
                'm3' => (float) $fila->m3,
                'valor' => (int) $fila->valor,
            ])
            ->sortByDesc('m3')
            ->values();
    }

    /**
     * @return Collection<int, array{comprador: Comprador, valor: int, remisiones: int}>
     */
    private function mejoresCompradores(User $usuario, CarbonInterface $desde, CarbonInterface $hasta): Collection
    {
        return $this->ingresos($usuario)
            ->whereBetween('emitida_en', [$desde, $hasta])
            ->selectRaw('comprador_id, sum(total) as valor, count(*) as remisiones')
            ->groupBy('comprador_id')
            ->orderByDesc('valor')
            ->limit(5)
            ->with('comprador')
            ->get()
            ->map(fn (Remision $fila): array => [
                'comprador' => $fila->comprador,
                'valor' => (int) $fila->valor,
                'remisiones' => (int) $fila->remisiones,
            ]);
    }

    /**
     * @return array{deuda: int, deudores: int, anticipos: int, stock_bajo: Collection<int, Material>}
     */
    private function resumenAdmin(): array
    {
        $deudaPorComprador = MovimientoCartera::query()
            ->where('cuenta', CuentaCartera::Credito)
            ->selectRaw('comprador_id, sum(valor) as saldo')
            ->groupBy('comprador_id')
            ->havingRaw('sum(valor) > 0')
            ->toBase()
            ->get();

        return [
            'deuda' => (int) $deudaPorComprador->sum('saldo'),
            'deudores' => $deudaPorComprador->count(),
            'anticipos' => (int) MovimientoCartera::query()->where('cuenta', CuentaCartera::Anticipo)->sum('valor'),
            'stock_bajo' => Material::query()->activos()->whereColumn('stock_m3', '<', 'stock_minimo_m3')->orderBy('stock_m3')->get(),
        ];
    }
}
