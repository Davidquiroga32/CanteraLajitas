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
    $retenciones = \App\Support\Retenciones::vigentes();
    $conRetenciones = $remision->retefuente + $remision->reteica > 0;
@endphp

<div class="rm">
    <table class="rm-cab">
        <tr>
            <td class="rm-logo"><img src="{{ $logo }}" alt="Cantera Lajitas S.A.S." width="124"></td>
            <td class="rm-titulo">
                <div class="rm-caja">
                    <div class="rm-t1">{{ $config['remision_titulo'] }}</div>
                    <div class="rm-t1">{{ $config['empresa_mina'] }}</div>
                    <div class="rm-t2">NIT. {{ $config['empresa_nit'] }}</div>
                </div>
                <div class="rm-legal">{{ $config['remision_legal'] }}</div>
            </td>
            <td class="rm-control">
                <div class="rm-numcaja">
                    <div class="rm-et">REMISIÓN N°:</div>
                    <div class="rm-numero">{{ $remision->numeroFormateado() }}</div>
                </div>
                @if ($copia)
                    <div class="rm-copia">{{ $copia }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="rm-fila rm-num">
        <tr>
            <td><span class="rm-et">FECHA:</span> {{ $remision->emitida_en->format('d/m/Y') }} <span class="rm-hora">{{ $remision->emitida_en->format('h:i a') }}</span></td>
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
            @if ($conRetenciones)
                <tr>
                    <td colspan="3" class="rm-der">SUBTOTAL</td>
                    <td>{{ Formato::dinero($remision->subtotal) }}</td>
                </tr>
                @if ($remision->retefuente > 0)
                    <tr>
                        <td colspan="3" class="rm-der">{{ mb_strtoupper($retenciones->etiquetaRetefuente()) }}</td>
                        <td>− {{ Formato::dinero($remision->retefuente) }}</td>
                    </tr>
                @endif
                @if ($remision->reteica > 0)
                    <tr>
                        <td colspan="3" class="rm-der">{{ mb_strtoupper($retenciones->etiquetaReteica()) }}</td>
                        <td>− {{ Formato::dinero($remision->reteica) }}</td>
                    </tr>
                @endif
            @endif
            <tr>
                <td colspan="3" class="rm-der">{{ $conRetenciones ? 'TOTAL A PAGAR' : 'TOTAL' }}</td>
                <td class="rm-total">{{ Formato::dinero($remision->total) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="rm-fila rm-pago">
        <tr>
            <td class="rm-et" style="width: 21%; white-space: nowrap">FORMA DE PAGO:</td>
            @foreach ([FormaPago::Efectivo, FormaPago::Transferencia, FormaPago::Credito, FormaPago::Anticipo, FormaPago::Donacion] as $forma)
                <td>{{ mb_strtoupper($forma->label()) }} <span class="rm-check">{{ $remision->forma_pago === $forma ? 'X' : ' ' }}</span></td>
            @endforeach
        </tr>
    </table>

    @if ($remision->diferencia_valor > 0)
        <table class="rm-fila">
            <tr>
                <td style="width: 45%"><span class="rm-et">PAGADO CON ANTICIPO:</span> {{ Formato::dinero($remision->total - $remision->diferencia_valor) }}</td>
                <td>
                    <span class="rm-et">DIFERENCIA {{ Formato::dinero($remision->diferencia_valor) }}:</span>
                    {{ $remision->diferencia_forma_pago === FormaPago::Credito ? 'QUEDA A CRÉDITO' : mb_strtoupper($remision->diferencia_forma_pago->label()) }}
                </td>
            </tr>
        </table>
    @endif

    <table class="rm-fila">
        <tr>
            <td style="width: 45%"><span class="rm-et">MÁQUINA QUE CARGA MATERIAL:</span> {{ $remision->maquina }}</td>
            <td><span class="rm-et">DESTINO:</span> {{ $remision->destino }}</td>
        </tr>
    </table>

    <table class="rm-fila rm-obs">
        <tr><td><span class="rm-et">OBSERVACIONES:</span> {{ $remision->observaciones }}</td></tr>
    </table>

    <table class="rm-firmas">
        <tr>
            <td>
                <div class="rm-linea"></div>
                <div class="rm-et">QUIEN DESPACHA</div>
                <div>{{ $vendedor->name }}</div>
            </td>
            <td class="rm-sep"></td>
            <td>
                <div class="rm-linea"></div>
                <div class="rm-et">CLIENTE</div>
                <div>{{ $remision->conductor }}</div>
            </td>
        </tr>
    </table>

    <div class="rm-pie">{{ $config['empresa_direccion'] }} / Cel {{ $config['empresa_telefonos'] }}</div>
</div>
