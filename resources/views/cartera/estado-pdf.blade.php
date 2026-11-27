@use('App\Support\Formato')
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estado de cuenta</title>
    @include('reportes.partials.estilos-pdf')
</head>
<body>
    @include('reportes.partials.cabecera-pdf', [
        'titulo' => 'Estado de cuenta · '.$comprador->nombre,
        'subtitulo' => $comprador->documentoCompleto().' · '.$comprador->ciudad.' · Corte al '.now()->format('d/m/Y'),
    ])

    <table class="resumen">
        <tr>
            <td style="border-left-color: #a8553a"><small>Debe</small><b class="rojo">{{ Formato::dinero($deuda) }}</b></td>
            <td style="border-left-color: #3f5c3a"><small>Anticipo a favor</small><b class="verde">{{ Formato::dinero($anticipo) }}</b></td>
        </tr>
    </table>

    @foreach ([['Deuda (compras a crédito)', $credito, 'Cargo', 'Abono'], ['Anticipos (saldo a favor)', $anticipos, 'Entregó', 'Usó']] as [$titulo, $filas, $suma, $resta])
        <h3>{{ $titulo }}</h3>
        <table class="datos">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Movimiento</th>
                    <th>Remisión</th>
                    <th>Medio / nota</th>
                    <th class="der">{{ $suma }}</th>
                    <th class="der">{{ $resta }}</th>
                    <th class="der">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    @php($m = $fila['movimiento'])
                    <tr>
                        <td>{{ $m->registrado_en->format('d/m/Y') }}</td>
                        <td>{{ $m->tipo->label() }}</td>
                        <td>{{ $m->remision?->numeroFormateado() }}</td>
                        <td>{{ trim(($m->medio_pago?->label() ?? '').' '.($m->nota ?? '')) }}</td>
                        <td class="der">{{ $m->valor > 0 ? Formato::dinero($m->valor) : '' }}</td>
                        <td class="der">{{ $m->valor < 0 ? Formato::dinero(-$m->valor) : '' }}</td>
                        <td class="der">{{ Formato::dinero($fila['saldo']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="centro">Sin movimientos.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
