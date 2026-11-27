<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Periodo de un reporte: atajos (hoy, 7, 15, 30 días, este mes, mes anterior)
 * o fechas libres, y el periodo anterior con el que se compara.
 */
class RangoFechas
{
    /** @var array<string, string> */
    public const PRESETS = [
        'hoy' => 'Hoy',
        '7' => '7 días',
        '15' => '15 días',
        '30' => '30 días',
        'mes' => 'Este mes',
        'mes-anterior' => 'Mes anterior',
    ];

    public function __construct(
        public readonly CarbonImmutable $desde,
        public readonly CarbonImmutable $hasta,
        public readonly ?string $preset = null,
    ) {}

    /**
     * Lee `rango`, `desde` y `hasta` de la URL. Sin nada, usa el mes actual.
     */
    public static function desdeRequest(Request $request): self
    {
        $hoy = CarbonImmutable::today();
        $preset = $request->query('rango');
        $preset = is_string($preset) && array_key_exists($preset, self::PRESETS) ? $preset : null;
        $desdeLibre = self::fecha($request->query('desde'));
        $hastaLibre = self::fecha($request->query('hasta'));

        if ($preset === null && $desdeLibre === null && $hastaLibre === null) {
            $preset = 'mes';
        }

        [$desde, $hasta] = match ($preset) {
            'hoy' => [$hoy, $hoy],
            '7', '15', '30' => [$hoy->subDays((int) $preset - 1), $hoy],
            'mes' => [$hoy->startOfMonth(), $hoy],
            'mes-anterior' => [$hoy->subMonthNoOverflow()->startOfMonth(), $hoy->subMonthNoOverflow()->endOfMonth()->startOfDay()],
            default => [$desdeLibre ?? $hastaLibre ?? $hoy, $hastaLibre ?? $hoy],
        };

        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return new self($desde, $hasta, $preset);
    }

    /**
     * Inicio del primer día y final del último día, para consultas `whereBetween`.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function limites(): array
    {
        return [$this->desde->startOfDay(), $this->hasta->endOfDay()];
    }

    /**
     * Cantidad de días del periodo (incluye el primero y el último).
     */
    public function dias(): int
    {
        return (int) $this->desde->diffInDays($this->hasta) + 1;
    }

    /**
     * Periodo con el que se compara: el mismo tramo del mes anterior cuando el
     * rango es un mes; si no, los mismos días justo antes del rango.
     */
    public function anterior(): self
    {
        if ($this->preset === 'mes') {
            return new self($this->desde->subMonthNoOverflow(), $this->hasta->subMonthNoOverflow());
        }

        if ($this->preset === 'mes-anterior') {
            $mes = $this->desde->subMonthNoOverflow();

            return new self($mes->startOfMonth(), $mes->endOfMonth()->startOfDay());
        }

        return new self($this->desde->subDays($this->dias()), $this->desde->subDay());
    }

    /**
     * Texto del periodo: "Del 01/11/2026 al 27/11/2026" o "27/11/2026".
     */
    public function descripcion(): string
    {
        if ($this->desde->isSameDay($this->hasta)) {
            return $this->desde->format('d/m/Y');
        }

        return 'Del '.$this->desde->format('d/m/Y').' al '.$this->hasta->format('d/m/Y');
    }

    /**
     * Parámetros para enlaces (exportaciones y pestañas).
     *
     * @return array<string, string>
     */
    public function parametros(): array
    {
        return $this->preset !== null
            ? ['rango' => $this->preset]
            : ['desde' => $this->desde->toDateString(), 'hasta' => $this->hasta->toDateString()];
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
