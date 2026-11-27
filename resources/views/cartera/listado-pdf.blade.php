@use('App\Support\Formato')
@php
    $esAnticipos = $vista === 'anticipos';
    $campo = $esAnticipos ? 'saldo_anticipo' : 'saldo_credito';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cartera</title>
    @include('reportes.partials.estilos-pdf')
</head>
<body>
    @include('reportes.partials.cabecera-pdf', [
        'titulo' => $esAnticipos ? 'Compradores con anticipo a favor' : 'Compradores que deben',
        'subtitulo' => 'Corte al '.now()->format('d/m/Y h:i a'),
    ])

    <table class="datos">
        <thead>
            <tr>
                <th>#</th>
                <th>Comprador</th>
                <th>Documento</th>
                <th>Municipio</th>
                <th class="der">{{ $esAnticipos ? 'Saldo a favor' : 'Debe' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($compradores as $i => $c)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $c->nombre }}</td>
                    <td>{{ $c->documentoCompleto() }}</td>
                    <td>{{ $c->ciudad }}</td>
                    <td class="der">{{ Formato::dinero($c->{$campo}) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="centro">Sin registros.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">TOTAL ({{ $compradores->count() }})</td>
                <td class="der">{{ Formato::dinero($compradores->sum($campo)) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
