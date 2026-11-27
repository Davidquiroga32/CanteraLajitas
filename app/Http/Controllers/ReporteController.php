<?php

namespace App\Http\Controllers;

use App\Models\Comprador;
use App\Models\Remision;
use App\Services\ReporteService;
use App\Support\ExcelExport;
use App\Support\Formato;
use App\Support\RangoFechas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Reportes (solo administrador): ventas del periodo, reporte por cliente y
 * clientes que deben, cada uno con exportación a Excel y PDF.
 */
class ReporteController extends Controller
{
    public function __construct(private ReporteService $reportes) {}

    public function index(Request $request): View
    {
        $rango = RangoFechas::desdeRequest($request);

        return view('reportes.index', [
            'rango' => $rango,
            'reporte' => $this->reportes->general($rango),
        ]);
    }

    public function exportarExcel(Request $request): BinaryFileResponse
    {
        $rango = RangoFechas::desdeRequest($request);
        $reporte = $this->reportes->general($rango);
        $periodo = $rango->descripcion();
        $agrupacion = $reporte['agrupacion'] === 'semana' ? 'semana' : 'día';

        return ExcelExport::descargarHojas('reporte-ventas-'.$rango->desde->format('Ymd').'-'.$rango->hasta->format('Ymd').'.xlsx', [
            [
                'nombre' => 'Resumen',
                'titulo' => 'Reporte de ventas · Cantera Lajitas S.A.S.',
                'subtitulo' => $periodo.' · Sin anuladas',
                'columnas' => [
                    ['titulo' => 'Concepto', 'ancho' => 34],
                    ['titulo' => 'Remisiones', 'ancho' => 12],
                    ['titulo' => 'm³', 'tipo' => ExcelExport::M3, 'ancho' => 12],
                    ['titulo' => 'Valor', 'tipo' => ExcelExport::DINERO, 'ancho' => 18],
                ],
                'filas' => [
                    ['Ventas', $reporte['ventas']['remisiones'], $reporte['ventas']['m3'], $reporte['ventas']['valor']],
                    ['Donaciones (no suman como venta)', $reporte['donaciones']['remisiones'], $reporte['donaciones']['m3'], $reporte['donaciones']['valor']],
                    ['Periodo anterior ('.$reporte['anterior']['rango']->descripcion().')', $reporte['anterior']['remisiones'], null, $reporte['anterior']['valor']],
                    ['Promedio por remisión', null, null, $reporte['ventas']['ticket']],
                    ['Retenciones descontadas', null, null, $reporte['retenciones']],
                    ['Remisiones anuladas (no cuentan)', $reporte['anuladas'], null, null],
                ],
            ],
            [
                'nombre' => 'Por '.$agrupacion,
                'titulo' => 'Ventas por '.$agrupacion,
                'subtitulo' => $periodo,
                'columnas' => [
                    ['titulo' => 'Desde', 'ancho' => 13],
                    ['titulo' => 'Hasta', 'ancho' => 13],
                    ['titulo' => 'Remisiones', 'ancho' => 12],
                    ['titulo' => 'Valor', 'tipo' => ExcelExport::DINERO, 'ancho' => 18],
                ],
                'filas' => collect($reporte['serie'])->map(fn (array $p): array => [$p['desde']->format('d/m/Y'), $p['hasta']->format('d/m/Y'), $p['remisiones'], $p['valor']]),
                'totales' => ['TOTAL', '', $reporte['ventas']['remisiones'], $reporte['ventas']['valor']],
            ],
            [
                'nombre' => 'Por material',
                'titulo' => 'Ventas por material (valor antes de retenciones)',
                'subtitulo' => $periodo.' · Sin donaciones',
                'columnas' => [
                    ['titulo' => 'Material', 'ancho' => 26],
                    ['titulo' => 'm³', 'tipo' => ExcelExport::M3, 'ancho' => 12],
                    ['titulo' => 'Valor', 'tipo' => ExcelExport::DINERO, 'ancho' => 18],
                    ['titulo' => '% del valor', 'ancho' => 12],
                ],
                'filas' => $reporte['materiales']->map(fn (array $f): array => [$f['material']->nombre, $f['m3'], $f['valor'], $f['porcentaje']]),
                'totales' => ['TOTAL', (float) $reporte['materiales']->sum('m3'), (int) $reporte['materiales']->sum('valor'), 100],
            ],
            [
                'nombre' => 'Por vendedor',
                'titulo' => 'Ventas por vendedor',
                'subtitulo' => $periodo.' · Sin donaciones',
                'columnas' => [
                    ['titulo' => 'Vendedor', 'ancho' => 30],
                    ['titulo' => 'Remisiones', 'ancho' => 12],
                    ['titulo' => 'm³', 'tipo' => ExcelExport::M3, 'ancho' => 12],
                    ['titulo' => 'Valor', 'tipo' => ExcelExport::DINERO, 'ancho' => 18],
                    ['titulo' => '% del valor', 'ancho' => 12],
                ],
                'filas' => $reporte['vendedores']->map(fn (array $f): array => [$f['vendedor']->name, $f['remisiones'], $f['m3'], $f['valor'], $f['porcentaje']]),
                'totales' => ['TOTAL', $reporte['ventas']['remisiones'], $reporte['ventas']['m3'], $reporte['ventas']['valor'], 100],
            ],
            [
                'nombre' => 'Por forma de pago',
                'titulo' => 'Ventas por forma de pago',
                'subtitulo' => $periodo.' · Las donaciones van aparte en el resumen',
                'columnas' => [
                    ['titulo' => 'Forma de pago', 'ancho' => 20],
                    ['titulo' => 'Remisiones', 'ancho' => 12],
                    ['titulo' => 'Valor', 'tipo' => ExcelExport::DINERO, 'ancho' => 18],
                    ['titulo' => '% del valor', 'ancho' => 12],
                ],
                'filas' => $reporte['formas_pago']->map(fn (array $f): array => [$f['forma']->label(), $f['remisiones'], $f['valor'], $f['porcentaje']]),
                'totales' => ['TOTAL', $reporte['ventas']['remisiones'], $reporte['ventas']['valor'], 100],
            ],
        ]);
    }

    public function exportarPdf(Request $request): Response
    {
        $rango = RangoFechas::desdeRequest($request);

        return Pdf::loadView('reportes.general-pdf', [
            'rango' => $rango,
            'reporte' => $this->reportes->general($rango),
        ])->setPaper('letter')->download('reporte-ventas-'.$rango->desde->format('Ymd').'-'.$rango->hasta->format('Ymd').'.pdf');
    }

    public function cliente(Request $request, ?Comprador $comprador = null): View
    {
        $rango = RangoFechas::desdeRequest($request);
        $busqueda = trim((string) $request->query('q', ''));

        return view('reportes.cliente', [
            'rango' => $rango,
            'busqueda' => $busqueda,
            'comprador' => $comprador,
            'reporte' => $comprador ? $this->reportes->cliente($comprador, $rango) : null,
            'resultados' => $comprador ? collect() : Comprador::query()
                ->buscar($busqueda)
                ->withCount(['remisiones' => fn ($q) => $q->emitidas()])
                ->orderByDesc('remisiones_count')
                ->orderBy('nombre')
                ->limit(30)
                ->get(),
        ]);
    }

    public function clienteExcel(Request $request, Comprador $comprador): BinaryFileResponse
    {
        $rango = RangoFechas::desdeRequest($request);
        $reporte = $this->reportes->cliente($comprador, $rango);
        $subtitulo = $rango->descripcion().' · Debe hoy '.Formato::dinero($reporte['deuda']).' · Anticipo a favor '.Formato::dinero($reporte['anticipo']);

        return ExcelExport::descargarHojas('reporte-cliente-'.str($comprador->nombre)->slug().'.xlsx', [
            [
                'nombre' => 'Remisiones',
                'titulo' => 'Reporte de '.$comprador->nombre.' ('.$comprador->documentoCompleto().')',
                'subtitulo' => $subtitulo,
                'columnas' => [
                    ['titulo' => 'Remisión', 'ancho' => 11],
                    ['titulo' => 'Fecha', 'ancho' => 17],
                    ['titulo' => 'Materiales', 'ancho' => 40],
                    ['titulo' => 'm³', 'tipo' => ExcelExport::M3, 'ancho' => 9],
                    ['titulo' => 'Pago', 'ancho' => 14],
                    ['titulo' => 'Placa', 'ancho' => 10],
                    ['titulo' => 'Total', 'tipo' => ExcelExport::DINERO, 'ancho' => 16],
                ],
                'filas' => $reporte['remisiones']->map(fn (Remision $r): array => [
                    $r->numeroFormateado(),
                    $r->emitida_en->format('d/m/Y H:i'),
                    $r->lineas->map(fn ($l) => $l->material->nombre.' '.Formato::m3($l->cantidad_m3).' m³')->implode(', '),
                    $r->totalM3(),
                    $r->forma_pago->label(),
                    $r->placa,
                    $r->total,
                ]),
                'totales' => ['VENTAS ('.$reporte['ventas']['remisiones'].')', '', 'Sin donaciones', $reporte['ventas']['m3'], '', '', $reporte['ventas']['valor']],
            ],
            [
                'nombre' => 'Por material',
                'titulo' => 'Material comprado por '.$comprador->nombre,
                'subtitulo' => $rango->descripcion().' · Sin donaciones · Valor antes de retenciones',
                'columnas' => [
                    ['titulo' => 'Material', 'ancho' => 26],
                    ['titulo' => 'm³', 'tipo' => ExcelExport::M3, 'ancho' => 12],
                    ['titulo' => 'Valor', 'tipo' => ExcelExport::DINERO, 'ancho' => 18],
                ],
                'filas' => $reporte['materiales']->map(fn (array $f): array => [$f['material']->nombre, $f['m3'], $f['valor']]),
                'totales' => ['TOTAL', (float) $reporte['materiales']->sum('m3'), (int) $reporte['materiales']->sum('valor')],
            ],
        ]);
    }

    public function clientePdf(Request $request, Comprador $comprador): Response
    {
        $rango = RangoFechas::desdeRequest($request);

        return Pdf::loadView('reportes.cliente-pdf', [
            'rango' => $rango,
            'comprador' => $comprador,
            'reporte' => $this->reportes->cliente($comprador, $rango),
        ])->setPaper('letter')->download('reporte-cliente-'.str($comprador->nombre)->slug().'.pdf');
    }

    public function deudores(Request $request): View
    {
        $orden = $this->orden($request);

        return view('reportes.deudores', [
            'orden' => $orden,
            'deudores' => $this->reportes->deudores($orden),
        ]);
    }

    public function deudoresExcel(Request $request): BinaryFileResponse
    {
        $deudores = $this->reportes->deudores($this->orden($request));

        return ExcelExport::descargar(
            'clientes-que-deben-'.now()->format('Ymd').'.xlsx',
            'Clientes que deben · Cantera Lajitas S.A.S.',
            'Corte al '.now()->format('d/m/Y h:i a'),
            [
                ['titulo' => 'Comprador', 'ancho' => 36],
                ['titulo' => 'Documento', 'ancho' => 20],
                ['titulo' => 'Municipio', 'ancho' => 16],
                ['titulo' => 'Última compra a crédito', 'ancho' => 22],
                ['titulo' => 'Último abono', 'ancho' => 14],
                ['titulo' => 'Días sin abonar', 'ancho' => 15],
                ['titulo' => 'Debe', 'tipo' => ExcelExport::DINERO, 'ancho' => 16],
            ],
            $deudores->map(fn (array $d): array => [
                $d['comprador']->nombre,
                $d['comprador']->documentoCompleto(),
                $d['comprador']->ciudad,
                $d['ultima_compra']?->format('d/m/Y') ?? '',
                $d['ultimo_abono']?->format('d/m/Y') ?? 'Nunca',
                $d['dias_sin_abonar'],
                $d['deuda'],
            ]),
            ['TOTAL ('.$deudores->count().')', '', '', '', '', '', (int) $deudores->sum('deuda')],
        );
    }

    public function deudoresPdf(Request $request): Response
    {
        return Pdf::loadView('reportes.deudores-pdf', [
            'deudores' => $this->reportes->deudores($this->orden($request)),
        ])->setPaper('letter')->download('clientes-que-deben-'.now()->format('Ymd').'.pdf');
    }

    /**
     * @return 'deuda'|'dias'
     */
    private function orden(Request $request): string
    {
        return $request->query('orden') === 'dias' ? 'dias' : 'deuda';
    }
}
