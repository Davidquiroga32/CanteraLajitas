<?php

namespace App\Support;

use App\Enums\EstadoRemision;
use App\Enums\FormaPago;
use App\Models\Remision;
use App\Models\RemisionLinea;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filtros del historial de remisiones (los mismos para la pantalla y las exportaciones).
 */
class FiltroRemisiones
{
    /** @var array<string, string> */
    public const PRESETS = [
        'hoy' => 'Hoy',
        '7' => '7 días',
        '15' => '15 días',
        '30' => '30 días',
        'mes' => 'Este mes',
    ];

    public function __construct(
        public readonly User $usuario,
        public readonly CarbonImmutable $desde,
        public readonly CarbonImmutable $hasta,
        public readonly ?string $preset,
        public readonly ?int $materialId,
        public readonly ?int $vendedorId,
        public readonly ?FormaPago $formaPago,
        public readonly string $busqueda,
        public readonly bool $incluirAnuladas,
    ) {}

    public static function desdeRequest(Request $request): self
    {
        $usuario = $request->user();
        $hoy = CarbonImmutable::today();
        $preset = $request->query('rango');
        $preset = is_string($preset) && array_key_exists($preset, self::PRESETS) ? $preset : null;

        if (! $request->filled('desde') && ! $request->filled('hasta') && $preset === null) {
            $preset = '15';
        }

        [$desde, $hasta] = match ($preset) {
            'hoy' => [$hoy, $hoy],
            '7', '15', '30' => [$hoy->subDays((int) $preset - 1), $hoy],
            'mes' => [$hoy->startOfMonth(), $hoy],
            default => [
                self::fecha($request->query('desde')) ?? $hoy->subDays(14),
                self::fecha($request->query('hasta')) ?? $hoy,
            ],
        };

        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return new self(
            usuario: $usuario,
            desde: $desde,
            hasta: $hasta,
            preset: $preset,
            materialId: $request->integer('material') ?: null,
            vendedorId: $usuario->isAdmin() ? ($request->integer('vendedor') ?: null) : null,
            formaPago: FormaPago::tryFrom((string) $request->query('pago')),
            busqueda: trim((string) $request->query('q', '')),
            incluirAnuladas: $request->boolean('anuladas'),
        );
    }

    /**
     * @return Builder<Remision>
     */
    public function query(): Builder
    {
        return Remision::query()
            ->visiblesPara($this->usuario)
            ->whereBetween('emitida_en', [$this->desde->startOfDay(), $this->hasta->endOfDay()])
            ->when(! $this->incluirAnuladas, fn (Builder $q) => $q->where('estado', EstadoRemision::Emitida))
            ->when($this->materialId, fn (Builder $q) => $q->whereHas('lineas', fn (Builder $l) => $l->where('material_id', $this->materialId)))
            ->when($this->vendedorId, fn (Builder $q) => $q->where('vendedor_id', $this->vendedorId))
            ->when($this->formaPago, fn (Builder $q) => $q->where('forma_pago', $this->formaPago))
            ->when($this->busqueda !== '', function (Builder $q): void {
                $termino = $this->busqueda;
                $numero = (int) ltrim(preg_replace('/\D/', '', $termino), '0');

                $q->where(function (Builder $w) use ($termino, $numero): void {
                    $w->whereHas('comprador', fn (Builder $c) => $c->where('nombre', 'like', "%{$termino}%")->orWhere('documento', 'like', "%{$termino}%"))
                        ->orWhere('placa', 'like', "%{$termino}%");

                    if ($numero > 0) {
                        $w->orWhere('numero', $numero);
                    }
                });
            });
    }

    /**
     * Totales del filtro: remisiones emitidas, m³ despachados y valor vendido (sin donaciones).
     *
     * @return array{remisiones: int, m3: float, valor: int}
     */
    public function totales(): array
    {
        $emitidas = (clone $this->query())->where('estado', EstadoRemision::Emitida);

        return [
            'remisiones' => (clone $emitidas)->count(),
            'm3' => (float) RemisionLinea::query()->whereIn('remision_id', (clone $emitidas)->select('id'))->sum('cantidad_m3'),
            'valor' => (int) (clone $emitidas)->where('forma_pago', '!=', FormaPago::Donacion)->sum('total'),
        ];
    }

    /**
     * Texto que describe el filtro, para títulos de exportaciones.
     */
    public function descripcion(): string
    {
        $partes = ['Del '.$this->desde->format('d/m/Y').' al '.$this->hasta->format('d/m/Y')];

        if ($this->formaPago) {
            $partes[] = 'Pago: '.$this->formaPago->label();
        }

        if ($this->busqueda !== '') {
            $partes[] = 'Búsqueda: "'.$this->busqueda.'"';
        }

        if (! $this->usuario->isAdmin()) {
            $partes[] = 'Vendedor: '.$this->usuario->name;
        }

        return implode(' · ', $partes);
    }

    /**
     * Parámetros de la URL actual (para paginación y enlaces de exportación).
     *
     * @return array<string, string|int>
     */
    public function parametros(): array
    {
        return array_filter([
            'rango' => $this->preset,
            'desde' => $this->preset ? null : $this->desde->toDateString(),
            'hasta' => $this->preset ? null : $this->hasta->toDateString(),
            'material' => $this->materialId,
            'vendedor' => $this->vendedorId,
            'pago' => $this->formaPago?->value,
            'q' => $this->busqueda !== '' ? $this->busqueda : null,
            'anuladas' => $this->incluirAnuladas ? 1 : null,
        ], fn ($v) => $v !== null);
    }

    private static function fecha(mixed $valor): ?CarbonImmutable
    {
        if (! is_string($valor) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $valor)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
