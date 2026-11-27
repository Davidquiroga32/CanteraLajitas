@use('App\Support\Formato')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Clientes que deben</title>
    @include('reportes.partials.estilos-pdf')
</head>
<body>
    @include('reportes.partials.cabecera-pdf', ['titulo' => 'Clientes que deben', 'subtitulo' => 'Corte al '.now()->format('d/m/Y h:i a')])

    <table class="datos">
        <thead>
            <tr>
                <th>#</th>
                <th>Comprador</th>
                <th>Documento</th>
                <th>Municipio</th>
                <th>Última compra a crédito</th>
                <th>Último abono</th>
                <th class="der">Días sin abonar</th>
                <th class="der">Debe</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($deudores as $i => $fila)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $fila['comprador']->nombre }}</td>
                    <td>{{ $fila['comprador']->documentoCompleto() }}</td>
                    <td>{{ $fila['comprador']->ciudad }}</td>
                    <td>{{ $fila['ultima_compra']?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $fila['ultimo_abono']?->format('d/m/Y') ?? 'Nunca' }}</td>
                    <td @class(['der', 'rojo' => $fila['dias_sin_abonar'] > 30])>{{ $fila['dias_sin_abonar'] }}</td>
                    <td class="der">{{ Formato::dinero($fila['deuda']) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="centro">Nadie debe en este momento.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7">TOTAL ({{ $deudores->count() }})</td>
                <td class="der">{{ Formato::dinero($deudores->sum('deuda')) }}</td>
            </tr>
        </tfoot>
    </table>
    <p>Si el comprador nunca ha abonado, los días se cuentan desde su primera compra a crédito.</p>
</body>
</html>
