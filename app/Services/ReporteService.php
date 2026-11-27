<?php

namespace App\Services;

use App\Enums\CuentaCartera;
use App\Enums\EstadoRemision;
use App\Enums\FormaPago;
use App\Enums\TipoMovimiento;
use App\Models\Comprador;
use App\Models\Material;
use App\Models\Remision;
use App\Models\RemisionLinea;
use App\Models\User;
use App\Support\Formato;
use App\Support\RangoFechas;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Cifras de los reportes (solo administrador). Las ventas son remisiones
 * emitidas sin donaciones; las donaciones se cuentan aparte y las anuladas no cuentan.
 */
class ReporteService
{
    /**
     * Si el rango supera estos días, el gráfico agrupa por semana.
     */
    public const DIAS_MAXIMOS_POR_DIA = 31;

    /**
     * @return array{
     *     ventas: array{valor: int, remisiones: int, m3: float, ticket: int},
     *     anterior: array{valor: int, remisiones: int, variacion: float|null, rango: RangoFechas},
     *     donaciones: array{valor: int, remisiones: int, m3: float},
     *     anuladas: int,
     *     retenciones: int,
     *     agrupacion: 'dia'|'semana',
     *     serie: list<array{etiqueta: string, desde: CarbonImmutable, hasta: CarbonImmutable, valor: int, remisiones: int, detalle: string}>,
     *     materiales: Collection<int, array{material: Material, m3: float, valor: int, porcentaje: float}>,
     *     vendedores: Collection<int, array{vendedor: User, remisiones: int, m3: float, valor: int, porcentaje: float}>,
     *     formas_pago: Collection<int, array{forma: FormaPago, remisiones: int, valor: int, porcentaje: float}>,
     * }
     */
    public function general(RangoFechas $rango): array
    {
        $ventas = $this->ventas($rango);
        $valor = (int) (clone $ventas)->sum('total');
        $remisiones = (clone $ventas)->count();

        $anterior = $rango->anterior();
        $valorAnterior = (int) $this->ventas($anterior)->sum('total');

        $donaciones = $this->emitidas($rango)->where('forma_pago', FormaPago::Donacion);
        $agrupacion = $rango->dias() > self::DIAS_MAXIMOS_POR_DIA ? 'semana' : 'dia';

        return [
            'ventas' => [
                'valor' => $valor,
                'remisiones' => $remisiones,
                'm3' => $this->m3($ventas),
                'ticket' => $remisiones ? intdiv($valor, $remisiones) : 0,
            ],
            'anterior' => [
                'valor' => $valorAnterior,
                'remisiones' => $this->ventas($anterior)->count(),
                'variacion' => $valorAnterior ? round(($valor - $valorAnterior) / $valorAnterior * 100, 1) : null,
                'rango' => $anterior,
            ],
            'donaciones' => [
                'valor' => (int) (clone $donaciones)->sum('total'),
                'remisiones' => (clone $donaciones)->count(),
                'm3' => $this->m3($donaciones),
            ],
            'anuladas' => Remision::query()->whereBetween('emitida_en', $rango->limites())->where('estado', EstadoRemision::Anulada)->count(),
            'retenciones' => (int) (clone $ventas)->sum('retefuente') + (int) (clone $ventas)->sum('reteica'),
            'agrupacion' => $agrupacion,
            'serie' => $this->serie($rango, $agrupacion),
            'materiales' => $this->porMaterial($ventas),
            'vendedores' => $this->porVendedor($ventas, $valor),
            'formas_pago' => $this->porFormaPago($ventas, $valor),
        ];
    }

    /**
     * Compras de un comprador en el periodo y su situación de cartera hoy.
     *
     * @return array{
     *     remisiones: Collection<int, Remision>,
     *     ventas: array{valor: int, remisiones: int, m3: float},
     *     donaciones: int,
     *     materiales: Collection<int, array{material: Material, m3: float, valor: int, porcentaje: float}>,
     *     abonos: int,
     *     anticipos_recibidos: int,
     *     deuda: int,
     *     anticipo: int,
     * }
     */
    public function cliente(Comprador $comprador, RangoFechas $rango): array
    {
        $ventas = $this->ventas($rango)->whereBelongsTo($comprador);
        $movimientosPeriodo = $comprador->movimientosCartera()->whereBetween('registrado_en', $rango->limites());

        return [
            'remisiones' => $this->emitidas($rango)
                ->whereBelongsTo($comprador)
                ->with(['lineas.material', 'vendedor'])
                ->latest('emitida_en')
                ->latest('id')
                ->get(),
            'ventas' => [
                'valor' => (int) (clone $ventas)->sum('total'),
                'remisiones' => (clone $ventas)->count(),
                'm3' => $this->m3($ventas),
            ],
            'donaciones' => $this->emitidas($rango)->whereBelongsTo($comprador)->where('forma_pago', FormaPago::Donacion)->count(),
            'materiales' => $this->porMaterial($ventas),
            'abonos' => (int) -(clone $movimientosPeriodo)->where('tipo', TipoMovimiento::Abono)->sum('valor'),
            'anticipos_recibidos' => (int) (clone $movimientosPeriodo)->where('tipo', TipoMovimiento::AnticipoRecibido)->sum('valor'),
            'deuda' => $comprador->saldoCredito(),
            'anticipo' => $comprador->saldoAnticipo(),
        ];
    }

    /**
     * Compradores que deben hoy, con la fecha de su última compra a crédito, su
     * último abono y los días que llevan sin abonar (si nunca han abonado, desde
     * su primera compra a crédito).
     *
     * @param  'deuda'|'dias'  $orden
     * @return Collection<int, array{comprador: Comprador, deuda: int, ultima_compra: CarbonImmutable|null, ultimo_abono: CarbonImmutable|null, dias_sin_abonar: int}>
     */
    public function deudores(string $orden = 'deuda'): Collection
    {
        $cargos = fn (Builder $q) => $q->where('cuenta', CuentaCartera::Credito)
            ->where('tipo', TipoMovimiento::CargoRemision)
            ->whereHas('remision', fn (Builder $r) => $r->emitidas());
        $hoy = CarbonImmutable::today();

        $deudores = Comprador::query()
            ->conSaldos()
            ->withMax(['movimientosCartera as ultima_compra_en' => $cargos], 'registrado_en')
            ->withMin(['movimientosCartera as primera_compra_en' => $cargos], 'registrado_en')
            ->withMax(['movimientosCartera as ultimo_abono_en' => fn (Builder $q) => $q->where('tipo', TipoMovimiento::Abono)], 'registrado_en')
            ->get()
            ->filter(fn (Comprador $c): bool => (int) $c->saldo_credito > 0)
            ->map(function (Comprador $c) use ($hoy): array {
                $ultimoAbono = $c->ultimo_abono_en ? CarbonImmutable::parse($c->ultimo_abono_en) : null;
                $referencia = $ultimoAbono ?? ($c->primera_compra_en ? CarbonImmutable::parse($c->primera_compra_en) : null);

                return [
                    'comprador' => $c,
                    'deuda' => (int) $c->saldo_credito,
                    'ultima_compra' => $c->ultima_compra_en ? CarbonImmutable::parse($c->ultima_compra_en) : null,
                    'ultimo_abono' => $ultimoAbono,
                    'dias_sin_abonar' => $referencia ? max(0, (int) $referencia->startOfDay()->diffInDays($hoy)) : 0,
                ];
            });

        return ($orden === 'dias'
            ? $deudores->sortBy([['dias_sin_abonar', 'desc'], ['deuda', 'desc']])
            : $deudores->sortByDesc('deuda'))->values();
    }

    /**
     * @return Builder<Remision>
     */
    private function emitidas(RangoFechas $rango): Builder
    {
        return Remision::query()->emitidas()->whereBetween('emitida_en', $rango->limites());
    }

    /**
     * @return Builder<Remision>
     */
    private function ventas(RangoFechas $rango): Builder
    {
        return $this->emitidas($rango)->where('forma_pago', '!=', FormaPago::Donacion);
    }

    /**
     * @param  Builder<Remision>  $remisiones
     */
    private function m3(Builder $remisiones): float
    {
        return (float) RemisionLinea::query()->whereIn('remision_id', (clone $remisiones)->select('id'))->sum('cantidad_m3');
    }

    /**
     * Ventas por día o por semana, con los periodos sin ventas en cero.
     *
     * @param  'dia'|'semana'  $agrupacion
     * @return list<array{etiqueta: string, desde: CarbonImmutable, hasta: CarbonImmutable, valor: int, remisiones: int, detalle: string}>
     */
    private function serie(RangoFechas $rango, string $agrupacion): array
    {
        $porDia = $this->ventas($rango)
            ->selectRaw('date(emitida_en) as dia, sum(total) as valor, count(*) as remisiones')
            ->groupByRaw('date(emitida_en)')
            ->toBase()
            ->get()
            ->keyBy('dia');

        $serie = [];
        $inicio = $rango->desde;

        while ($inicio->lte($rango->hasta)) {
            $fin = $agrupacion === 'semana' ? $inicio->endOfWeek(CarbonInterface::SUNDAY)->startOfDay() : $inicio;
            $fin = $fin->gt($rango->hasta) ? $rango->hasta : $fin;
            $valor = 0;
            $remisiones = 0;

            for ($dia = $inicio; $dia->lte($fin); $dia = $dia->addDay()) {
                $fila = $porDia->get($dia->toDateString());
                $valor += (int) ($fila->valor ?? 0);
                $remisiones += (int) ($fila->remisiones ?? 0);
            }

            $serie[] = [
                'etiqueta' => Formato::fechaCorta($inicio),
                'desde' => $inicio,
                'hasta' => $fin,
                'valor' => $valor,
                'remisiones' => $remisiones,
                'detalle' => ($agrupacion === 'semana' ? 'Semana '.Formato::fechaCorta($inicio).' – '.Formato::fechaCorta($fin).' · ' : '').$remisiones.' remisión(es)',
            ];

            $inicio = $fin->addDay();
        }

        return $serie;
    }

    /**
     * @param  Builder<Remision>  $ventas
     * @return Collection<int, array{material: Material, m3: float, valor: int, porcentaje: float}>
     */
    private function porMaterial(Builder $ventas): Collection
    {
        $filas = RemisionLinea::query()
            ->whereIn('remision_id', (clone $ventas)->select('id'))
            ->selectRaw('material_id, sum(cantidad_m3) as m3, sum(subtotal) as valor')
            ->groupBy('material_id')
            ->with('material')
            ->get();
        $total = max(1, (int) $filas->sum('valor'));

        return $filas
            ->map(fn (RemisionLinea $fila): array => [
                'material' => $fila->material,
                'm3' => (float) $fila->m3,
                'valor' => (int) $fila->valor,
                'porcentaje' => round((int) $fila->valor / $total * 100, 1),
            ])
            ->sortByDesc('valor')
            ->values();
    }

    /**
     * @param  Builder<Remision>  $ventas
     * @return Collection<int, array{vendedor: User, remisiones: int, m3: float, valor: int, porcentaje: float}>
     */
    private function porVendedor(Builder $ventas, int $total): Collection
    {
        $m3PorVendedor = RemisionLinea::query()
            ->join('remisiones', 'remisiones.id', '=', 'remision_lineas.remision_id')
            ->whereIn('remision_lineas.remision_id', (clone $ventas)->select('id'))
            ->groupBy('remisiones.vendedor_id')
            ->selectRaw('remisiones.vendedor_id, sum(remision_lineas.cantidad_m3) as m3')
            ->toBase()
            ->pluck('m3', 'vendedor_id');

        return (clone $ventas)
            ->selectRaw('vendedor_id, count(*) as remisiones, sum(total) as valor')
            ->groupBy('vendedor_id')
            ->with('vendedor')
            ->get()
            ->map(fn (Remision $fila): array => [
                'vendedor' => $fila->vendedor,
                'remisiones' => (int) $fila->remisiones,
                'm3' => (float) ($m3PorVendedor[$fila->vendedor_id] ?? 0),
                'valor' => (int) $fila->valor,
                'porcentaje' => $total ? round((int) $fila->valor / $total * 100, 1) : 0.0,
            ])
            ->sortByDesc('valor')
            ->values();
    }

    /**
     * @param  Builder<Remision>  $ventas
     * @return Collection<int, array{forma: FormaPago, remisiones: int, valor: int, porcentaje: float}>
     */
    private function porFormaPago(Builder $ventas, int $total): Collection
    {
        $filas = (clone $ventas)
            ->selectRaw('forma_pago, count(*) as remisiones, sum(total) as valor')
            ->groupBy('forma_pago')
            ->toBase()
            ->get()
            ->keyBy('forma_pago');

        return collect(FormaPago::cases())
            ->filter(fn (FormaPago $forma): bool => $forma->cuentaComoIngreso())
            ->map(fn (FormaPago $forma): array => [
                'forma' => $forma,
                'remisiones' => (int) ($filas[$forma->value]->remisiones ?? 0),
                'valor' => (int) ($filas[$forma->value]->valor ?? 0),
                'porcentaje' => $total ? round((int) ($filas[$forma->value]->valor ?? 0) / $total * 100, 1) : 0.0,
            ])
            ->values();
    }
}
