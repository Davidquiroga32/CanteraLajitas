<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmitirRemisionRequest;
use App\Models\Comprador;
use App\Models\Configuracion;
use App\Models\Material;
use App\Models\Remision;
use App\Services\RemisionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RemisionController extends Controller
{
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
                'deuda' => (int) $c->saldo_credito,
                'anticipo' => (int) $c->saldo_anticipo,
            ]);

        return response()->json($compradores);
    }

    private function cargar(Remision $remision): Remision
    {
        return $remision->load(['lineas.material', 'comprador', 'vendedor']);
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
