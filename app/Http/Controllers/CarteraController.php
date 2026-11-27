<?php

namespace App\Http\Controllers;

use App\Enums\CuentaCartera;
use App\Enums\FormaPago;
use App\Models\Comprador;
use App\Services\CarteraService;
use App\Support\ExcelExport;
use App\Support\Formato;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Cartera (solo administrador): quién debe, quién tiene anticipo, abonos y
 * anticipos recibidos, y estados de cuenta.
 */
class CarteraController extends Controller
{
    public function __construct(private CarteraService $cartera) {}

    public function index(Request $request): View
    {
        $vista = $request->query('ver') === 'anticipos' ? 'anticipos' : 'deudores';
        $busqueda = trim((string) $request->query('q', ''));
        $deudores = $this->compradoresConSaldo('deudores', '');
        $conAnticipo = $this->compradoresConSaldo('anticipos', '');

        return view('cartera.index', [
            'vista' => $vista,
            'busqueda' => $busqueda,
            'compradores' => $busqueda === '' ? ($vista === 'anticipos' ? $conAnticipo : $deudores) : $this->compradoresConSaldo($vista, $busqueda),
            'totalDeuda' => (int) $deudores->sum('saldo_credito'),
            'totalAnticipos' => (int) $conAnticipo->sum('saldo_anticipo'),
            'deudores' => $deudores->count(),
            'conAnticipo' => $conAnticipo->count(),
        ]);
    }

    public function show(Comprador $comprador): View
    {
        return view('cartera.show', [
            'comprador' => $comprador,
            'deuda' => $comprador->saldoCredito(),
            'anticipo' => $comprador->saldoAnticipo(),
            'credito' => array_reverse($this->cartera->estadoCuenta($comprador, CuentaCartera::Credito)),
            'anticipos' => array_reverse($this->cartera->estadoCuenta($comprador, CuentaCartera::Anticipo)),
        ]);
    }

    public function abono(Request $request, Comprador $comprador): RedirectResponse
    {
        $datos = $this->validarPago($request, 'abono');

        try {
            $this->cartera->registrarAbono($comprador, $datos['valor'], FormaPago::from($datos['medio_pago']), $request->user(), $datos['nota'] ?? null, $datos['fecha']);
        } catch (ValidationException $e) {
            throw $e->errorBag('abono');
        }

        return to_route('cartera.show', $comprador)->with('ok', 'Abono de '.Formato::dinero($datos['valor']).' registrado. Ahora debe '.Formato::dinero($comprador->saldoCredito()).'.');
    }

    public function anticipo(Request $request, Comprador $comprador): RedirectResponse
    {
        $datos = $this->validarPago($request, 'anticipo');

        try {
            $this->cartera->registrarAnticipo($comprador, $datos['valor'], FormaPago::from($datos['medio_pago']), $request->user(), $datos['nota'] ?? null, $datos['fecha']);
        } catch (ValidationException $e) {
            throw $e->errorBag('anticipo');
        }

        return to_route('cartera.show', $comprador)->with('ok', 'Anticipo de '.Formato::dinero($datos['valor']).' registrado. Saldo a favor: '.Formato::dinero($comprador->saldoAnticipo()).'.');
    }

    public function exportarExcel(Request $request): BinaryFileResponse
    {
        $vista = $request->query('ver') === 'anticipos' ? 'anticipos' : 'deudores';
        $compradores = $this->compradoresConSaldo($vista, '');
        $campo = $vista === 'anticipos' ? 'saldo_anticipo' : 'saldo_credito';

        return ExcelExport::descargar(
            'cartera-'.$vista.'-'.now()->format('Ymd').'.xlsx',
            $vista === 'anticipos' ? 'Compradores con anticipo · Cantera Lajitas S.A.S.' : 'Compradores que deben · Cantera Lajitas S.A.S.',
            'Corte al '.now()->format('d/m/Y h:i a'),
            [
                ['titulo' => 'Comprador', 'ancho' => 38],
                ['titulo' => 'Documento', 'ancho' => 20],
                ['titulo' => 'Municipio', 'ancho' => 16],
                ['titulo' => $vista === 'anticipos' ? 'Saldo a favor' : 'Debe', 'tipo' => ExcelExport::DINERO, 'ancho' => 16],
            ],
            $compradores->map(fn (Comprador $c): array => [$c->nombre, $c->documentoCompleto(), $c->ciudad, (int) $c->{$campo}]),
            ['TOTAL ('.$compradores->count().')', '', '', (int) $compradores->sum($campo)],
        );
    }

    public function exportarPdf(Request $request): Response
    {
        $vista = $request->query('ver') === 'anticipos' ? 'anticipos' : 'deudores';

        return Pdf::loadView('cartera.listado-pdf', [
            'vista' => $vista,
            'compradores' => $this->compradoresConSaldo($vista, ''),
        ])->setPaper('letter')->download('cartera-'.$vista.'-'.now()->format('Ymd').'.pdf');
    }

    public function estadoCuentaExcel(Comprador $comprador): BinaryFileResponse
    {
        $filas = collect([CuentaCartera::Credito, CuentaCartera::Anticipo])
            ->flatMap(fn (CuentaCartera $cuenta) => collect($this->cartera->estadoCuenta($comprador, $cuenta))
                ->map(fn (array $fila): array => [
                    $cuenta === CuentaCartera::Credito ? 'Deuda' : 'Anticipo',
                    $fila['movimiento']->registrado_en->format('d/m/Y H:i'),
                    $fila['movimiento']->tipo->label(),
                    $fila['movimiento']->remision?->numeroFormateado() ?? '',
                    $fila['movimiento']->medio_pago?->label() ?? '',
                    $fila['movimiento']->nota ?? '',
                    $fila['movimiento']->valor > 0 ? $fila['movimiento']->valor : null,
                    $fila['movimiento']->valor < 0 ? -$fila['movimiento']->valor : null,
                    $fila['saldo'],
                ]));

        return ExcelExport::descargar(
            'estado-cuenta-'.str($comprador->nombre)->slug().'.xlsx',
            'Estado de cuenta · '.$comprador->nombre.' ('.$comprador->documentoCompleto().')',
            'Debe '.Formato::dinero($comprador->saldoCredito()).' · Anticipo a favor '.Formato::dinero($comprador->saldoAnticipo()).' · Corte al '.now()->format('d/m/Y'),
            [
                ['titulo' => 'Cuenta', 'ancho' => 10],
                ['titulo' => 'Fecha', 'ancho' => 17],
                ['titulo' => 'Movimiento', 'ancho' => 24],
                ['titulo' => 'Remisión', 'ancho' => 11],
                ['titulo' => 'Medio', 'ancho' => 14],
                ['titulo' => 'Nota', 'ancho' => 34],
                ['titulo' => 'Suma', 'tipo' => ExcelExport::DINERO, 'ancho' => 15],
                ['titulo' => 'Resta', 'tipo' => ExcelExport::DINERO, 'ancho' => 15],
                ['titulo' => 'Saldo', 'tipo' => ExcelExport::DINERO, 'ancho' => 15],
            ],
            $filas,
        );
    }

    public function estadoCuentaPdf(Comprador $comprador): Response
    {
        return Pdf::loadView('cartera.estado-pdf', [
            'comprador' => $comprador,
            'deuda' => $comprador->saldoCredito(),
            'anticipo' => $comprador->saldoAnticipo(),
            'credito' => $this->cartera->estadoCuenta($comprador, CuentaCartera::Credito),
            'anticipos' => $this->cartera->estadoCuenta($comprador, CuentaCartera::Anticipo),
        ])->setPaper('letter')->download('estado-cuenta-'.str($comprador->nombre)->slug().'.pdf');
    }

    /**
     * @return Collection<int, Comprador>
     */
    private function compradoresConSaldo(string $vista, string $busqueda): Collection
    {
        $campo = $vista === 'anticipos' ? 'saldo_anticipo' : 'saldo_credito';

        return Comprador::query()
            ->buscar($busqueda)
            ->conSaldos()
            ->get()
            ->filter(fn (Comprador $c): bool => (int) $c->{$campo} > 0)
            ->sortByDesc(fn (Comprador $c): int => (int) $c->{$campo})
            ->values();
    }

    /**
     * @return array{valor: int, medio_pago: string, fecha: CarbonImmutable, nota?: string|null}
     */
    private function validarPago(Request $request, string $bolsa): array
    {
        $request->merge([
            'valor' => is_string($request->valor) ? preg_replace('/\D/', '', $request->valor) : $request->valor,
        ]);

        $datos = $request->validateWithBag($bolsa, [
            'valor' => ['required', 'integer', 'min:1', 'max:10000000000'],
            'medio_pago' => ['required', Rule::in([FormaPago::Efectivo->value, FormaPago::Transferencia->value])],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'nota' => ['nullable', 'string', 'max:255'],
        ], [
            'valor.required' => 'Escribe el valor.',
            'valor.min' => 'El valor debe ser mayor a cero.',
            'medio_pago.required' => 'Elige si fue en efectivo o por transferencia.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
        ], [
            'medio_pago' => 'el medio de pago',
            'fecha' => 'la fecha',
            'nota' => 'la nota',
        ]);

        $fecha = CarbonImmutable::createFromFormat('Y-m-d', $datos['fecha']);
        $datos['fecha'] = $fecha->isToday() ? CarbonImmutable::now() : $fecha->setTime(12, 0);
        $datos['valor'] = (int) $datos['valor'];

        return $datos;
    }
}
