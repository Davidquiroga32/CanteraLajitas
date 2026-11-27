@use('App\Support\Formato')
@php
    $atrasados = $deudores->where('dias_sin_abonar', '>', 30);
@endphp

<x-app-layout>
    <x-slot name="titulo">Clientes que deben</x-slot>

    <x-encabezado ceja="Reportes" subtitulo="Todos los compradores que deben hoy, cuándo compraron a crédito por última vez y cuántos días llevan sin abonar. Toca uno para ver su cartera.">
        Clientes <em>que deben</em>
        <x-slot name="acciones">
            <a class="btn" href="{{ route('reportes.deudores-excel', ['orden' => $orden]) }}">Exportar Excel</a>
            <a class="btn" href="{{ route('reportes.deudores-pdf', ['orden' => $orden]) }}">Exportar PDF</a>
        </x-slot>
    </x-encabezado>

    @include('reportes.partials.pestanas', ['activa' => 'deudores'])

    <div class="g g4 mb-5" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))">
        <x-kpi etiqueta="Por cobrar" :valor="Formato::dinero($deudores->sum('deuda'))" color="var(--bad)">
            {{ $deudores->count() }} comprador(es) deben
        </x-kpi>
        <x-kpi etiqueta="Más de 30 días sin abonar" :valor="Formato::dinero($atrasados->sum('deuda'))" color="var(--orange)">
            {{ $atrasados->count() }} comprador(es)
        </x-kpi>
    </div>

    <section class="card">
        <div class="flex flex-wrap items-center gap-3 border-b px-[22px] py-[18px]" style="border-color: var(--line)">
            <span class="muted text-[13px]">Ordenar por:</span>
            <div class="pills">
                <a @class(['pill', 'on' => $orden === 'deuda']) href="{{ route('reportes.deudores') }}">Mayor deuda</a>
                <a @class(['pill', 'on' => $orden === 'dias']) href="{{ route('reportes.deudores', ['orden' => 'dias']) }}">Más días sin abonar</a>
            </div>
        </div>

        <div class="tw">
            <table class="t t-movil">
                <thead>
                    <tr>
                        <th>Comprador</th>
                        <th>Documento</th>
                        <th>Última compra a crédito</th>
                        <th>Último abono</th>
                        <th class="num">Días sin abonar</th>
                        <th class="num">Debe</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deudores as $fila)
                        @php($url = route('cartera.show', $fila['comprador']))
                        <tr class="lk" onclick="window.location='{{ $url }}'">
                            <td class="principal"><a href="{{ $url }}" class="font-medium" style="color: var(--navy)">{{ $fila['comprador']->nombre }}</a></td>
                            <td class="mono text-[13px]" data-label="Documento">{{ $fila['comprador']->documentoCompleto() }}</td>
                            <td data-label="Última compra a crédito">{{ $fila['ultima_compra'] ? Formato::fecha($fila['ultima_compra']) : '—' }}</td>
                            <td data-label="Último abono">{{ $fila['ultimo_abono'] ? Formato::fecha($fila['ultimo_abono']) : 'Nunca ha abonado' }}</td>
                            <td class="num" data-label="Días sin abonar">
                                <span @class(['tag', 'c' => $fila['dias_sin_abonar'] > 60, 'o' => $fila['dias_sin_abonar'] > 30 && $fila['dias_sin_abonar'] <= 60, 'g' => $fila['dias_sin_abonar'] <= 30])>{{ $fila['dias_sin_abonar'] }} {{ $fila['dias_sin_abonar'] === 1 ? 'día' : 'días' }}</span>
                            </td>
                            <td class="num" data-label="Debe"><b style="color: var(--bad)">{{ Formato::dinero($fila['deuda']) }}</b></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">Nadie debe en este momento.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($deudores->isNotEmpty())
            <p class="muted px-[22px] py-3 text-[12.5px]">Si el comprador nunca ha abonado, los días se cuentan desde su primera compra a crédito.</p>
        @endif
    </section>
</x-app-layout>
