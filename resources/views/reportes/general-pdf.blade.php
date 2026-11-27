@use('App\Support\Formato')
@php
    $ventas = $reporte['ventas'];
    $anterior = $reporte['anterior'];
    $donaciones = $reporte['donaciones'];
    $porSemana = $reporte['agrupacion'] === 'semana';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de ventas</title>
    @include('reportes.partials.estilos-pdf')
</head>
<body>
    @include('reportes.partials.cabecera-pdf', ['titulo' => 'Reporte de ventas', 'subtitulo' => $rango->descripcion().' · Sin anuladas · Donaciones aparte'])

    <table class="resumen">
        <tr>
            <td>
                <small>Total vendido</small><b>{{ Formato::dinero($ventas['valor']) }}</b>
                <small>
                    @if ($anterior['variacion'] !== null)
                        {{ $anterior['variacion'] >= 0 ? '+' : '−' }}{{ Formato::numero(abs($anterior['variacion']), 1) }}% vs. {{ $anterior['rango']->descripcion() }}
                    @else
                        Sin ventas en el periodo anterior
                    @endif
                </small>
            </td>
            <td><small>Remisiones vendidas</small><b>{{ Formato::numero($ventas['remisiones']) }}</b><small>Promedio {{ Formato::dinero($ventas['ticket']) }}</small></td>
            <td><small>Material despachado</small><b>{{ Formato::m3($ventas['m3'] + $donaciones['m3']) }} m³</b><small>{{ Formato::m3($ventas['m3']) }} m³ vendidos</small></td>
            <td><small>Donaciones (no suman)</small><b>{{ Formato::dinero($donaciones['valor']) }}</b><small>{{ $donaciones['remisiones'] }} remisión(es) · {{ Formato::m3($donaciones['m3']) }} m³</small></td>
        </tr>
    </table>

    @if ($reporte['anuladas'] > 0)
        <p>Remisiones anuladas en el periodo (no cuentan): {{ $reporte['anuladas'] }}.</p>
    @endif

    <h3>Por material (sin donaciones)</h3>
    <table class="datos">
        <thead><tr><th>Material</th><th class="der">m³</th><th class="der">Valor</th><th class="der">%</th></tr></thead>
        <tbody>
            @forelse ($reporte['materiales'] as $fila)
                <tr><td>{{ $fila['material']->nombre }}</td><td class="der">{{ Formato::m3($fila['m3']) }}</td><td class="der">{{ Formato::dinero($fila['valor']) }}</td><td class="der">{{ Formato::numero($fila['porcentaje'], 1) }}%</td></tr>
            @empty
                <tr><td colspan="4" class="centro">Sin ventas en el periodo.</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td>TOTAL</td><td class="der">{{ Formato::m3($reporte['materiales']->sum('m3')) }}</td><td class="der">{{ Formato::dinero($reporte['materiales']->sum('valor')) }}</td><td></td></tr></tfoot>
    </table>
    @if ($reporte['retenciones'] > 0)
        <p>Valores por material antes de retenciones. Retenciones descontadas: {{ Formato::dinero($reporte['retenciones']) }}.</p>
    @endif

    <h3>Por vendedor (sin donaciones)</h3>
    <table class="datos">
        <thead><tr><th>Vendedor</th><th class="der">Remisiones</th><th class="der">m³</th><th class="der">Valor</th><th class="der">%</th></tr></thead>
        <tbody>
            @forelse ($reporte['vendedores'] as $fila)
                <tr><td>{{ $fila['vendedor']->name }}</td><td class="der">{{ $fila['remisiones'] }}</td><td class="der">{{ Formato::m3($fila['m3']) }}</td><td class="der">{{ Formato::dinero($fila['valor']) }}</td><td class="der">{{ Formato::numero($fila['porcentaje'], 1) }}%</td></tr>
            @empty
                <tr><td colspan="5" class="centro">Sin ventas en el periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Por forma de pago</h3>
    <table class="datos">
        <thead><tr><th>Forma de pago</th><th class="der">Remisiones</th><th class="der">Valor</th><th class="der">%</th></tr></thead>
        <tbody>
            @foreach ($reporte['formas_pago'] as $fila)
                <tr><td>{{ $fila['forma']->label() }}</td><td class="der">{{ $fila['remisiones'] }}</td><td class="der">{{ Formato::dinero($fila['valor']) }}</td><td class="der">{{ Formato::numero($fila['porcentaje'], 1) }}%</td></tr>
            @endforeach
        </tbody>
        <tfoot><tr><td>TOTAL</td><td class="der">{{ $ventas['remisiones'] }}</td><td class="der">{{ Formato::dinero($ventas['valor']) }}</td><td></td></tr></tfoot>
    </table>

    <h3>Ventas por {{ $porSemana ? 'semana' : 'día' }}</h3>
    <table class="datos">
        <thead><tr><th>{{ $porSemana ? 'Semana' : 'Día' }}</th><th class="der">Remisiones</th><th class="der">Valor</th></tr></thead>
        <tbody>
            @foreach ($reporte['serie'] as $punto)
                <tr>
                    <td>{{ $punto['desde']->format('d/m/Y') }}{{ $porSemana && ! $punto['desde']->isSameDay($punto['hasta']) ? ' al '.$punto['hasta']->format('d/m/Y') : '' }}</td>
                    <td class="der">{{ $punto['remisiones'] }}</td>
                    <td class="der">{{ Formato::dinero($punto['valor']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
