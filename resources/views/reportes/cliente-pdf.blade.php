@use('App\Support\Formato')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de cliente</title>
    @include('reportes.partials.estilos-pdf')
</head>
<body>
    @include('reportes.partials.cabecera-pdf', [
        'titulo' => $comprador->nombre.' · '.$comprador->documentoCompleto(),
        'subtitulo' => 'Reporte por cliente · '.$rango->descripcion(),
    ])

    <table class="resumen">
        <tr>
            <td><small>Compró en el periodo</small><b>{{ Formato::dinero($reporte['ventas']['valor']) }}</b><small>{{ $reporte['ventas']['remisiones'] }} remisión(es)</small></td>
            <td><small>Material comprado</small><b>{{ Formato::m3($reporte['ventas']['m3']) }} m³</b><small>Sin donaciones</small></td>
            <td><small>Abonó en el periodo</small><b>{{ Formato::dinero($reporte['abonos']) }}</b><small>Anticipos recibidos {{ Formato::dinero($reporte['anticipos_recibidos']) }}</small></td>
            <td><small>Debe hoy</small><b>{{ Formato::dinero($reporte['deuda']) }}</b><small>Anticipo a favor {{ Formato::dinero($reporte['anticipo']) }}</small></td>
        </tr>
    </table>

    <h3>Material comprado (sin donaciones)</h3>
    <table class="datos">
        <thead><tr><th>Material</th><th class="der">m³</th><th class="der">Valor</th></tr></thead>
        <tbody>
            @forelse ($reporte['materiales'] as $fila)
                <tr><td>{{ $fila['material']->nombre }}</td><td class="der">{{ Formato::m3($fila['m3']) }}</td><td class="der">{{ Formato::dinero($fila['valor']) }}</td></tr>
            @empty
                <tr><td colspan="3" class="centro">No compró en este periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Remisiones</h3>
    <table class="datos">
        <thead><tr><th>Remisión</th><th>Fecha</th><th>Materiales</th><th>Placa</th><th>Pago</th><th class="der">Total</th></tr></thead>
        <tbody>
            @forelse ($reporte['remisiones'] as $r)
                <tr>
                    <td>{{ $r->numeroFormateado() }}</td>
                    <td>{{ $r->emitida_en->format('d/m/Y H:i') }}</td>
                    <td>{{ $r->lineas->map(fn ($l) => $l->material->nombre.' '.Formato::m3($l->cantidad_m3).' m³')->implode(', ') }}</td>
                    <td>{{ $r->placa }}</td>
                    <td>{{ $r->forma_pago->label() }}</td>
                    <td class="der">{{ Formato::dinero($r->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="centro">No tiene remisiones en este periodo.</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td colspan="5">VENTAS ({{ $reporte['ventas']['remisiones'] }}, sin donaciones)</td><td class="der">{{ Formato::dinero($reporte['ventas']['valor']) }}</td></tr></tfoot>
    </table>
</body>
</html>
