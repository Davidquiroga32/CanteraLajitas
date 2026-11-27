<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmitirRemisionRequest;
use App\Models\Comprador;
use App\Models\Configuracion;
use App\Models\Material;
use App\Models\Remision;
use App\Models\User;
use App\Services\RemisionService;
use App\Support\ExcelExport;
use App\Support\FiltroRemisiones;
use App\Support\Formato;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RemisionController extends Controller
{
    /**
     * Historial con filtros por fechas, material, vendedor, forma de pago y búsqueda.
     */
    public function index(Request $request): View
    {
        $filtro = FiltroRemisiones::desdeRequest($request);

        return view('remisiones.index', [
            'filtro' => $filtro,
            'remisiones' => $filtro->query()
                ->with(['comprador', 'vendedor', 'lineas.material'])
                ->latest('emitida_en')
                ->latest('id')
                ->paginate(30)
                ->withQueryString(),
            'totales' => $filtro->totales(),
            'materiales' => Material::query()->orderBy('nombre')->get(['id', 'nombre']),
            'vendedores' => $request->user()->isAdmin() ? User::query()->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function exportarExcel(Request $request): BinaryFileResponse
    {
        $filtro = FiltroRemisiones::desdeRequest($request);
        $remisiones = $filtro->query()->with(['comprador', 'vendedor', 'lineas.material'])->oldest('emitida_en')->oldest('id')->get();
        $totales = $filtro->totales();

        return ExcelExport::descargar(
            'remisiones-'.$filtro->desde->format('Ymd').'-'.$filtro->hasta->format('Ymd').'.xlsx',
            'Remisiones · Cantera Lajitas S.A.S.',
            $filtro->descripcion(),
            [
                ['titulo' => 'Remisión', 'ancho' => 11],
                ['titulo' => 'Fecha', 'ancho' => 17],
                ['titulo' => 'Comprador', 'ancho' => 34],
                ['titulo' => 'Documento', 'ancho' => 18],
                ['titulo' => 'Materiales', 'ancho' => 40],
                ['titulo' => 'm³', 'tipo' => ExcelExport::M3, 'ancho' => 9],
                ['titulo' => 'Vendedor', 'ancho' => 22],
                ['titulo' => 'Forma de pago', 'ancho' => 15],
                ['titulo' => 'Placa', 'ancho' => 10],
                ['titulo' => 'Destino', 'ancho' => 26],
                ['titulo' => 'Estado', 'ancho' => 10],
                ['titulo' => 'Total', 'tipo' => ExcelExport::DINERO, 'ancho' => 15],
            ],
            $remisiones->map(fn (Remision $r): array => [
                $r->numeroFormateado(),
                $r->emitida_en->format('d/m/Y H:i'),
                $r->comprador->nombre,
                $r->comprador->documentoCompleto(),
                $r->lineas->map(fn ($l) => $l->material->nombre.' '.Formato::m3($l->cantidad_m3).' m³')->implode(', '),
                $r->totalM3(),
                $r->vendedor->name,
                $r->forma_pago->label(),
                $r->placa,
                $r->destino,
                $r->estado->label(),
                $r->total,
            ]),
            ['', '', 'TOTAL ('.$totales['remisiones'].' remisiones, sin donaciones ni anuladas)', '', '', $totales['m3'], '', '', '', '', '', $totales['valor']],
        );
    }

    public function exportarPdf(Request $request): Response
    {
        $filtro = FiltroRemisiones::desdeRequest($request);

        return Pdf::loadView('remisiones.listado-pdf', [
            'filtro' => $filtro,
            'remisiones' => $filtro->query()->with(['comprador', 'vendedor', 'lineas.material'])->oldest('emitida_en')->oldest('id')->get(),
            'totales' => $filtro->totales(),
        ])->setPaper('letter', 'landscape')->download('remisiones-'.$filtro->desde->format('Ymd').'-'.$filtro->hasta->format('Ymd').'.pdf');
    }

    /**
     * Anula una remisión (solo administrador): repone stock y reversa la cartera.
     */
    public function anular(Request $request, Remision $remision, RemisionService $remisiones): RedirectResponse
    {
        $datos = $request->validateWithBag('anular', [
            'motivo' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'motivo.required' => 'Escribe el motivo de la anulación.',
            'motivo.min' => 'Explica el motivo con un poco más de detalle.',
        ]);

        try {
            $remisiones->anular($remision, $request->user(), $datos['motivo']);
        } catch (ValidationException $e) {
            throw $e->errorBag('anular');
        }

        return to_route('remisiones.show', $remision)->with('ok', 'Remisión '.$remision->numeroFormateado().' anulada. Se devolvió el stock y se ajustó la cartera.');
    }

    /**
     * Formulario de nueva remisión. Con ?comprador={id} llega el comprador ya elegido.
     */
    public function create(Request $request): View
    {
        $compradorId = $request->old('comprador_id') ?? $request->query('comprador');
        $comprador = $compradorId ? Comprador::query()->conSaldos()->find((int) $compradorId) : null;

        return view('remisiones.create', [
            'materiales' => Material::query()->disponiblesParaVenta()->orderBy('nombre')->get(),
            'comprador' => $comprador,
            'siguienteNumero' => app(RemisionService::class)->siguienteNumero(),
            'maquinas' => Remision::query()->select('maquina')->distinct()->orderBy('maquina')->limit(15)->pluck('maquina'),
        ]);
    }

    public function store(EmitirRemisionRequest $request, RemisionService $remisiones): RedirectResponse
    {
        $remision = $remisiones->emitir($request->user(), $request->validated());

        return to_route('remisiones.show', $remision)->with('emitida', true);
    }

    /**
     * Detalle de la remisión. Justo después de emitirla muestra la confirmación.
     */
    public function show(Remision $remision): View
    {
        Gate::authorize('view', $remision);

        return view('remisiones.show', [
            'remision' => $this->cargar($remision),
            'config' => Configuracion::todas(),
        ]);
    }

    /**
     * Página lista para imprimir las copias (vendedor + cliente).
     */
    public function imprimir(Remision $remision): View
    {
        Gate::authorize('view', $remision);

        return view('remisiones.imprimir', [
            'remision' => $this->cargar($remision),
            'config' => Configuracion::todas(),
            'copias' => $this->copias(),
        ]);
    }

    public function pdf(Remision $remision): Response
    {
        Gate::authorize('view', $remision);

        $pdf = Pdf::loadView('remisiones.pdf', [
            'remision' => $this->cargar($remision),
            'config' => Configuracion::todas(),
            'copias' => $this->copias(),
            'paraPdf' => true,
        ])->setPaper('letter');

        return $pdf->download('remision-'.$remision->numeroFormateado().'.pdf');
    }

    /**
     * Búsqueda de compradores para el autocompletado de la nueva remisión.
     */
    public function buscarCompradores(Request $request): JsonResponse
    {
        $termino = trim((string) $request->query('q', ''));

        if (mb_strlen($termino) < 2) {
            return response()->json([]);
        }

        $compradores = Comprador::query()
            ->where('activo', true)
            ->buscar($termino)
            ->conSaldos()
            ->orderBy('nombre')
            ->limit(8)
            ->get()
            ->map(fn (Comprador $c): array => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'tipo_documento' => $c->tipo_documento->value,
                'documento' => $c->documento,
                'ciudad' => $c->ciudad,
                'retefuente' => $c->retefuente,
                'reteica' => $c->reteica,
                'deuda' => (int) $c->saldo_credito,
                'anticipo' => (int) $c->saldo_anticipo,
            ]);

        return response()->json($compradores);
    }

    private function cargar(Remision $remision): Remision
    {
        return $remision->load(['lineas.material', 'comprador', 'vendedor', 'anuladaPor']);
    }

    /**
     * @return list<string>
     */
    private function copias(): array
    {
        $total = max(1, (int) Configuracion::valor('remision_copias'));

        return array_slice(['COPIA VENDEDOR', 'COPIA CLIENTE', 'COPIA CLIENTE', 'COPIA ADICIONAL', 'COPIA ADICIONAL'], 0, min($total, 5));
    }
}
