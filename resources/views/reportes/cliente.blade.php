@use('App\Support\Formato')
@php($params = $rango->parametros())

<x-app-layout>
    <x-slot name="titulo">Reporte por cliente</x-slot>

    <x-encabezado ceja="Reportes" :subtitulo="$comprador ? 'Lo que compró en el periodo y cómo está su cuenta hoy.' : 'Busca un comprador para ver todo lo que ha comprado en el periodo que elijas.'">
        Reporte <em>por cliente</em>
        @if ($comprador)
            <x-slot name="acciones">
                <a class="btn" href="{{ route('reportes.cliente-excel', [$comprador, ...$params]) }}">Exportar Excel</a>
                <a class="btn" href="{{ route('reportes.cliente-pdf', [$comprador, ...$params]) }}">Exportar PDF</a>
            </x-slot>
        @endif
    </x-encabezado>

    @include('reportes.partials.pestanas', ['activa' => 'cliente', 'parametros' => $params])

    @if (! $comprador)
        <section class="card">
            <div class="border-b px-[22px] py-[18px]" style="border-color: var(--line)">
                <form method="GET" action="{{ route('reportes.cliente') }}" x-data="filtroEnVivo" role="search">
                    @foreach ($params as $clave => $valor)
                        <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                    @endforeach
                    <label for="q" class="lbl mb-1 block">¿De qué cliente quieres el reporte?</label>
                    <input id="q" type="search" name="q" class="inp" value="{{ $busqueda }}" placeholder="Nombre, NIT o cédula" autocomplete="off" autofocus>
                </form>
            </div>
            <div class="tw" data-vivo="resultados">
                <table class="t t-movil">
                    <thead>
                        <tr><th>Comprador</th><th>Documento</th><th>Municipio</th><th class="num">Remisiones</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($resultados as $resultado)
                            @php($url = route('reportes.cliente', [$resultado, ...$params]))
                            <tr class="lk" onclick="window.location='{{ $url }}'">
                                <td class="principal"><a href="{{ $url }}" class="font-medium" style="color: var(--navy)">{{ $resultado->nombre }}</a></td>
                                <td class="mono text-[13px]" data-label="Documento">{{ $resultado->documentoCompleto() }}</td>
                                <td data-label="Municipio">{{ $resultado->ciudad ?? '—' }}</td>
                                <td class="num" data-label="Remisiones">{{ $resultado->remisiones_count }}</td>
                                <td class="text-right"><a href="{{ $url }}" class="btn sm">Ver reporte</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty">No hay compradores con esa búsqueda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <section class="card mb-5">
            <div class="pd flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-[18px] font-semibold" style="color: var(--navy)">{{ $comprador->nombre }}</div>
                    <div class="muted mono text-[13px]">{{ $comprador->documentoCompleto() }}{{ $comprador->ciudad ? ' · '.$comprador->ciudad : '' }}</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a class="btn sm" href="{{ route('cartera.show', $comprador) }}">Ver cartera</a>
                    <a class="btn sm" href="{{ route('reportes.cliente', $params) }}">Cambiar cliente</a>
                </div>
            </div>
        </section>

        @include('reportes.partials.rango', ['accion' => route('reportes.cliente', $comprador)])

        <div class="g g4 mb-5">
            <x-kpi etiqueta="Compró en el periodo" :valor="Formato::dinero($reporte['ventas']['valor'])" color="var(--orange)">
                {{ $reporte['ventas']['remisiones'] }} remisión(es){{ $reporte['donaciones'] ? ' · '.$reporte['donaciones'].' donación(es) aparte' : '' }}
            </x-kpi>
            <x-kpi etiqueta="Material comprado" color="var(--brown)">
                <x-slot name="valor">{{ Formato::m3($reporte['ventas']['m3']) }} <small>m³</small></x-slot>
                Sin donaciones
            </x-kpi>
            <x-kpi etiqueta="Abonó en el periodo" :valor="Formato::dinero($reporte['abonos'])" color="var(--ok)">
                Anticipos recibidos: {{ Formato::dinero($reporte['anticipos_recibidos']) }}
            </x-kpi>
            <x-kpi etiqueta="Debe hoy" :color="$reporte['deuda'] > 0 ? 'var(--bad)' : 'var(--ok)'">
                <x-slot name="valor">{{ Formato::dinero($reporte['deuda']) }}</x-slot>
                Anticipo a favor: {{ Formato::dinero($reporte['anticipo']) }}
            </x-kpi>
        </div>

        <div class="g g2">
            <section class="card">
                <div class="hd"><h2>Material comprado</h2><span class="muted text-xs">Sin donaciones</span></div>
                <div class="tw">
                    <table class="t t-movil">
                        <thead>
                            <tr><th>Material</th><th class="num">m³</th><th class="num">Valor</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($reporte['materiales'] as $fila)
                                <tr>
                                    <td class="principal"><i class="sw" style="--c: {{ $fila['material']->color }}"></i>{{ $fila['material']->nombre }}</td>
                                    <td class="num" data-label="m³">{{ Formato::m3($fila['m3']) }}</td>
                                    <td class="num" data-label="Valor"><b>{{ Formato::dinero($fila['valor']) }}</b></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty">No compró en este periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <div class="hd"><h2>Remisiones</h2><span class="muted text-xs">{{ $reporte['remisiones']->count() }} en el periodo</span></div>
                <div class="tw">
                    <table class="t t-movil">
                        <thead>
                            <tr><th>Remisión</th><th>Fecha</th><th>Materiales</th><th>Pago</th><th class="num">Total</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($reporte['remisiones'] as $remision)
                                @php($url = route('remisiones.show', $remision))
                                <tr class="lk" onclick="window.location='{{ $url }}'">
                                    <td class="mono principal"><a href="{{ $url }}" class="font-medium" style="color: var(--navy)">{{ $remision->numeroFormateado() }}</a></td>
                                    <td class="whitespace-nowrap" data-label="Fecha">{{ Formato::fecha($remision->emitida_en) }}</td>
                                    <td data-label="Materiales">
                                        <span class="inline-flex flex-wrap justify-end gap-1 md:justify-start">
                                            @foreach ($remision->lineas as $linea)
                                                <span class="tag g">{{ $linea->material->nombre }} · {{ Formato::m3($linea->cantidad_m3) }} m³</span>
                                            @endforeach
                                        </span>
                                    </td>
                                    <td data-label="Pago"><x-tag-pago :forma="$remision->forma_pago" /></td>
                                    <td class="num" data-label="Total"><b>{{ Formato::dinero($remision->total) }}</b></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="empty">No tiene remisiones en este periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @endif
</x-app-layout>
