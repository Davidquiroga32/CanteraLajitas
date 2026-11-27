@use('App\Support\Formato')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Remisiones</title>
    @include('reportes.partials.estilos-pdf')
</head>
<body>
    @include('reportes.partials.cabecera-pdf', ['titulo' => 'Remisiones emitidas', 'subtitulo' => $filtro->descripcion()])

    <table class="resumen">
        <tr>
            <td><small>Remisiones</small><b>{{ Formato::numero($totales['remisiones']) }}</b></td>
            <td><small>Material despachado</small><b>{{ Formato::m3($totales['m3']) }} m³</b></td>
            <td><small>Valor vendido (sin donaciones)</small><b>{{ Formato::dinero($totales['valor']) }}</b></td>
        </tr>
    </table>

    <table class="datos">
        <thead>
            <tr>
                <th>Remisión</th>
                <th>Fecha</th>
                <th>Comprador</th>
                <th>Materiales</th>
                <th>Vendedor</th>
                <th>Pago</th>
                <th class="der">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($remisiones as $r)
                <tr @class(['anulada' => $r->estaAnulada()])>
                    <td>{{ $r->numeroFormateado() }}{{ $r->estaAnulada() ? ' (ANULADA)' : '' }}</td>
                    <td>{{ $r->emitida_en->format('d/m/Y H:i') }}</td>
                    <td>{{ $r->comprador->nombre }}</td>
                    <td>{{ $r->lineas->map(fn ($l) => $l->material->nombre.' '.Formato::m3($l->cantidad_m3).' m³')->implode(', ') }}</td>
                    <td>{{ $r->vendedor->name }}</td>
                    <td>{{ $r->forma_pago->label() }}</td>
                    <td class="der">{{ Formato::dinero($r->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="centro">No hay remisiones con esos filtros.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
