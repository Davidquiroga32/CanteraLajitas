@use('App\Support\Formato')
@php
    $ventas = $reporte['ventas'];
    $anterior = $reporte['anterior'];
    $donaciones = $reporte['donaciones'];
    $porSemana = $reporte['agrupacion'] === 'semana';
    $params = $rango->parametros();
@endphp

<x-app-layout>
    <x-slot name="titulo">Reportes</x-slot>

    <x-encabezado ceja="Reportes" subtitulo="Cuánto se vendió en el periodo, qué material, quién lo vendió y cómo se pagó. Las donaciones van aparte y las anuladas no cuentan.">
        Reporte de <em>ventas</em>
        <x-slot name="acciones">
            <a class="btn" href="{{ route('reportes.excel', $params) }}">Exportar Excel</a>
            <a class="btn" href="{{ route('reportes.pdf', $params) }}">Exportar PDF</a>
        </x-slot>
    </x-encabezado>

    @include('reportes.partials.pestanas', ['activa' => 'general', 'parametros' => $params])
    @include('reportes.partials.rango', ['accion' => route('reportes.index')])

    <div class="g g4 mb-5">
        <x-kpi etiqueta="Total vendido" :valor="Formato::dinero($ventas['valor'])" color="var(--orange)">
            @if ($anterior['variacion'] !== null)
                <span @class(['dl', 'up' => $anterior['variacion'] >= 0, 'dn' => $anterior['variacion'] < 0])>
                    {{ $anterior['variacion'] >= 0 ? '▲' : '▼' }} {{ Formato::numero(abs($anterior['variacion']), 1) }}%
                </span>
                vs. {{ $anterior['rango']->descripcion() }}
            @else
                Sin ventas en {{ $anterior['rango']->descripcion() }} para comparar
            @endif
        </x-kpi>
        <x-kpi etiqueta="Remisiones vendidas" :valor="Formato::numero($ventas['remisiones'])" color="var(--navy)">
            Promedio {{ Formato::dinero($ventas['ticket']) }} por remisión
        </x-kpi>
        <x-kpi etiqueta="Material despachado" color="var(--brown)">
            <x-slot name="valor">{{ Formato::m3($ventas['m3'] + $donaciones['m3']) }} <small>m³</small></x-slot>
            {{ Formato::m3($ventas['m3']) }} m³ vendidos{{ $donaciones['m3'] > 0 ? ' + '.Formato::m3($donaciones['m3']).' m³ donados' : '' }}
        </x-kpi>
        <x-kpi etiqueta="Donaciones" :valor="Formato::dinero($donaciones['valor'])" color="var(--slate)">
            {{ $donaciones['remisiones'] }} remisión(es) · no suman como venta
        </x-kpi>
    </div>

    @if ($reporte['anuladas'] > 0)
        <p class="aviso mb-5">En este periodo hay {{ $reporte['anuladas'] }} remisión(es) anulada(s). No se cuentan en ninguna cifra.</p>
    @endif

    @if (count($reporte['serie']) > 1)
        <section class="card mb-5">
            <div class="hd">
                <div>
                    <h2>Ventas por {{ $porSemana ? 'semana' : 'día' }}</h2>
                    <p class="muted mt-0.5 text-[12.5px]">{{ $porSemana ? 'El periodo es largo: cada punto es una semana.' : 'Toca o pasa el cursor por el gráfico para ver cada día.' }}</p>
                </div>
            </div>
            <div class="pd">
                <p class="muted mb-2 text-[12.5px] md:hidden">Desliza el gráfico hacia los lados para verlo completo.</p>
                <div class="overflow-x-auto">
                    <div class="min-w-[620px] md:min-w-0">
                        <x-grafico-lineas :serie="$reporte['serie']" :descripcion="'Ventas por '.($porSemana ? 'semana' : 'día')" />
                    </div>
                </div>
            </div>
        </section>
    @endif

    <div class="g g2 mb-5">
        <section class="card">
            <div class="hd"><h2>Por material</h2><span class="muted text-xs">Sin donaciones</span></div>
            <div class="tw">
                <table class="t t-movil">
                    <thead>
                        <tr><th>Material</th><th class="num">m³</th><th class="num">Valor</th><th class="num">%</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($reporte['materiales'] as $fila)
                            <tr>
                                <td class="principal"><i class="sw" style="--c: {{ $fila['material']->color }}"></i>{{ $fila['material']->nombre }}</td>
                                <td class="num" data-label="m³">{{ Formato::m3($fila['m3']) }}</td>
                                <td class="num" data-label="Valor"><b>{{ Formato::dinero($fila['valor']) }}</b></td>
                                <td class="num" data-label="% del valor">{{ Formato::numero($fila['porcentaje'], 1) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty">Sin ventas en el periodo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($reporte['retenciones'] > 0)
                <p class="muted px-[22px] py-3 text-[12.5px]">Valores por material antes de retenciones. Se descontaron {{ Formato::dinero($reporte['retenciones']) }} en retenciones.</p>
            @endif
        </section>

        <section class="card">
            <div class="hd"><h2>Por forma de pago</h2><span class="muted text-xs">Donaciones aparte</span></div>
            <div class="tw">
                <table class="t t-movil">
                    <thead>
                        <tr><th>Forma de pago</th><th class="num">Remisiones</th><th class="num">Valor</th><th class="num">%</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($reporte['formas_pago'] as $fila)
                            <tr>
                                <td class="principal"><x-tag-pago :forma="$fila['forma']" /></td>
                                <td class="num" data-label="Remisiones">{{ $fila['remisiones'] }}</td>
                                <td class="num" data-label="Valor"><b>{{ Formato::dinero($fila['valor']) }}</b></td>
                                <td class="num" data-label="% del valor">{{ Formato::numero($fila['porcentaje'], 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="card">
        <div class="hd"><h2>Por vendedor</h2><span class="muted text-xs">Sin donaciones</span></div>
        <div class="tw">
            <table class="t t-movil">
                <thead>
                    <tr><th>Vendedor</th><th class="num">Remisiones</th><th class="num">m³</th><th class="num">Valor</th><th class="num">%</th></tr>
                </thead>
                <tbody>
                    @forelse ($reporte['vendedores'] as $fila)
                        <tr>
                            <td class="principal">{{ $fila['vendedor']->name }}</td>
                            <td class="num" data-label="Remisiones">{{ $fila['remisiones'] }}</td>
                            <td class="num" data-label="m³">{{ Formato::m3($fila['m3']) }}</td>
                            <td class="num" data-label="Valor"><b>{{ Formato::dinero($fila['valor']) }}</b></td>
                            <td class="num" data-label="% del valor">{{ Formato::numero($fila['porcentaje'], 1) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">Sin ventas en el periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
