@use('App\Support\Formato')
@use('App\Enums\FormaPago')
{{--
    Una copia de la remisión. Se usa en la impresión y en el PDF (dompdf),
    por eso usa tablas y estilos simples en lugar de flex/grid.
    Variables: $remision, $config, $copia, $paraPdf.
--}}
@php
    $logo = ($paraPdf ?? false) ? public_path('images/logo-cantera-lajitas.png') : asset('images/logo-cantera-lajitas.png');
    $comprador = $remision->comprador;
    $vendedor = $remision->vendedor;
@endphp

<div class="rm">
    <table class="rm-cab">
        <tr>
            <td class="rm-logo"><img src="{{ $logo }}" alt="Cantera Lajitas S.A.S." width="96"></td>
            <td class="rm-titulo">
                <div class="rm-caja">
                    <div class="rm-t1">{{ $config['remision_titulo'] }}</div>
                    <div class="rm-t1">{{ $config['empresa_mina'] }}</div>
                    <div class="rm-t2">NIT. {{ $config['empresa_nit'] }}</div>
                </div>
                <div class="rm-legal">{{ $config['remision_legal'] }}</div>
            </td>
            <td class="rm-control">
                <div class="rm-copia">{{ $copia }}</div>
                <table class="rm-ctl">
                    <tr><td>CÓDIGO: {{ $config['remision_codigo'] }}</td></tr>
                    <tr><td class="rm-ctl-ver">VERSIÓN: {{ $config['remision_version'] }}</td></tr>
                    <tr><td>APROBADO: {{ $config['remision_aprobado'] }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="rm-fila rm-num">
        <tr>
            <td><span class="rm-et">FECHA:</span> {{ $remision->emitida_en->format('d/m/Y') }} <span class="rm-hora">{{ $remision->emitida_en->format('h:i a') }}</span></td>
            <td class="rm-der"><span class="rm-et">REMISIÓN N°:</span> <span class="rm-numero">{{ $remision->numeroFormateado() }}</span></td>
        </tr>
    </table>

    @if ($remision->estaAnulada())
        <div class="rm-anulada">ANULADA — {{ $remision->motivo_anulacion }}</div>
    @endif

    <table class="rm-fila">
        <tr>
            <td style="width: 64%"><span class="rm-et">CLIENTE:</span> {{ $comprador->nombre }}</td>
            <td><span class="rm-et">{{ $comprador->tipo_documento->value }}:</span> {{ $comprador->documento }}</td>
        </tr>
    </table>
    <table class="rm-fila">
        <tr>
            <td style="width: 64%"><span class="rm-et">CONDUCTOR:</span> {{ $remision->conductor }}</td>
            <td><span class="rm-et">PLACA:</span> {{ $remision->placa }}</td>
        </tr>
    </table>

    <table class="rm-mat">
        <thead>
            <tr>
                <th style="text-align: left">TIPO DE MATERIAL</th>
                <th>CANTIDAD M³</th>
                <th>VR/UNIT</th>
                <th>VR/TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($remision->lineas as $linea)
                <tr>
                    <td style="text-align: left">{{ $linea->material->nombre }}</td>
                    <td>{{ Formato::m3($linea->cantidad_m3) }}</td>
                    <td>{{ Formato::dinero($linea->precio_m3) }}</td>
                    <td>{{ Formato::dinero($linea->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="rm-der">TOTAL</td>
                <td class="rm-total">{{ Formato::dinero($remision->total) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="rm-fila rm-pago">
        <tr>
            <td class="rm-et" style="width: 18%">FORMA DE PAGO:</td>
            @foreach ([FormaPago::Efectivo, FormaPago::Transferencia, FormaPago::Credito, FormaPago::Anticipo, FormaPago::Donacion] as $forma)
                <td>{{ mb_strtoupper($forma->label()) }} <span class="rm-check">{{ $remision->forma_pago === $forma ? 'X' : ' ' }}</span></td>
            @endforeach
        </tr>
    </table>

    <table class="rm-fila">
        <tr>
            <td style="width: 45%"><span class="rm-et">MÁQUINA QUE CARGA MATERIAL:</span> {{ $remision->maquina }}</td>
            <td><span class="rm-et">DESTINO:</span> {{ $remision->destino }}</td>
        </tr>
    </table>

    @if ($remision->observaciones)
        <table class="rm-fila">
            <tr><td><span class="rm-et">OBSERVACIONES:</span> {{ $remision->observaciones }}</td></tr>
        </table>
    @endif

    <table class="rm-firmas">
        <tr>
            <td>
                <div class="rm-linea"></div>
                <div class="rm-et">QUIEN DESPACHA</div>
                <div>{{ $vendedor->name }}</div>
                <div>C.C. {{ $vendedor->cedula ?? '—' }}</div>
            </td>
            <td class="rm-sep"></td>
            <td>
                <div class="rm-linea"></div>
                <div class="rm-et">CLIENTE</div>
                <div>{{ $comprador->nombre }}</div>
                <div>{{ $comprador->tipo_documento->value === 'NIT' ? 'NIT' : 'C.C.' }} {{ $comprador->documento }}</div>
            </td>
        </tr>
    </table>

    <div class="rm-pie">{{ $config['empresa_direccion'] }} / Cel {{ $config['empresa_telefonos'] }}</div>
</div>
