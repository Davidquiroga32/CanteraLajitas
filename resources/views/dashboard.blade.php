@use('App\Support\Formato')
@php
    $esAdmin = $usuario->isAdmin();
    $hora = now()->hour;
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 18 ? 'Buenas tardes' : 'Buenas noches');
    $mes = $resumen['mes'];
    $nombreMes = $mes['inicio']->translatedFormat('F');
    $nombreMesAnterior = $mes['inicio']->subMonthNoOverflow()->translatedFormat('F');
    $serie = collect($resumen['serie'])->map(fn ($d) => [
        'etiqueta' => Formato::fechaCorta($d['dia']),
        'valor' => $d['valor'],
        'detalle' => $d['remisiones'].' remisión(es)',
    ])->all();
    $maxM3 = max(1, $resumen['materiales']->max('m3') ?? 1);
@endphp

<x-app-layout>
    <x-slot name="titulo">Inicio</x-slot>

    <x-encabezado destacado :ceja="$esAdmin ? 'Resumen de la operación' : 'Tu resumen de ventas'"
                  :subtitulo="ucfirst(now()->translatedFormat('l j \d\e F \d\e Y')).'. '.($esAdmin ? 'Así va la cantera hoy.' : 'Estas son tus ventas registradas.')">
        {{ $saludo }}, <em>{{ explode(' ', $usuario->name)[0] }}</em>
        <x-slot name="acciones">
            <a class="btn acento grande" href="{{ route('remisiones.create') }}">+ Nueva remisión</a>
        </x-slot>
    </x-encabezado>

    <div class="g g4 mb-[22px]">
        <x-kpi etiqueta="Ventas de hoy" :valor="Formato::dinero($resumen['hoy']['valor'])" color="var(--navy)">
            {{ $resumen['hoy']['remisiones'] }} remisión(es) emitidas
        </x-kpi>
        <x-kpi :etiqueta="'Ventas de '.$nombreMes" :valor="Formato::dinero($mes['valor'])" color="var(--orange)">
            @if ($mes['variacion'] !== null)
                <span @class(['dl', 'up' => $mes['variacion'] >= 0, 'dn' => $mes['variacion'] < 0])>
                    {{ $mes['variacion'] >= 0 ? '▲' : '▼' }} {{ Formato::numero(abs($mes['variacion']), 1) }}%
                </span>
                vs. {{ $nombreMesAnterior }} a la fecha
            @else
                Sin ventas en {{ $nombreMesAnterior }} para comparar
            @endif
        </x-kpi>
        <x-kpi etiqueta="Material despachado (mes)" color="var(--brown)">
            <x-slot name="valor">{{ Formato::m3($mes['m3']) }} <small>m³</small></x-slot>
            {{ $resumen['materiales']->first()['material']->nombre ?? '—' }} es el más vendido
        </x-kpi>
        <x-kpi etiqueta="Ticket promedio" :valor="Formato::dinero($mes['ticket'])" color="var(--slate)">
            {{ $mes['remisiones'] }} remisiones en el mes
        </x-kpi>
    </div>

    @if ($resumen['admin'])
        @php($admin = $resumen['admin'])
        <div class="g g4 mb-[22px]">
            <x-kpi etiqueta="Cartera por cobrar" :valor="Formato::dinero($admin['deuda'])" color="var(--bad)">
                {{ $admin['deudores'] }} comprador(es) con deuda
            </x-kpi>
            <x-kpi etiqueta="Anticipos disponibles" :valor="Formato::dinero($admin['anticipos'])" color="var(--ok)">
                Saldo a favor de los compradores
            </x-kpi>
            <section class="card col-ancha">
                <div class="pd">
                    <div class="l mb-2 text-[13px]" style="color: var(--muted)">Stock bajo en patio</div>
                    @forelse ($admin['stock_bajo'] as $material)
                        <div class="flex justify-between border-b py-1.5 text-sm last:border-0" style="border-color: var(--line)">
                            <span><i class="sw" style="--c: {{ $material->color }}"></i>{{ $material->nombre }}</span>
                            <span class="num" style="color: var(--bad)">{{ Formato::m3($material->stock_m3) }} m³ <span class="muted">/ mín. {{ Formato::m3($material->stock_minimo_m3) }}</span></span>
                        </div>
                    @empty
                        <p class="text-sm" style="color: var(--ok)">Todos los materiales están por encima del mínimo.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif

    <div class="g g2 mb-[22px]">
        <section class="card">
            <div class="hd">
                <div>
                    <h2>Ventas de los últimos 30 días</h2>
                    <p class="muted mt-0.5 text-[12.5px]">Pasa el cursor por el gráfico para ver cada día</p>
                </div>
                @if ($esAdmin)
                    <a class="btn sm" href="{{ route('reportes.index') }}">Ver reportes</a>
                @endif
            </div>
            <div class="pd">
                <x-grafico-lineas :serie="$serie" />
            </div>
        </section>

        <section class="card">
            <div class="hd"><h2>Material más vendido</h2><span class="muted text-xs">{{ $nombreMes }}</span></div>
            <div class="pd">
                @forelse ($resumen['materiales'] as $fila)
                    <div class="hb" style="--c: {{ $fila['material']->color }}">
                        <span><i class="sw"></i>{{ $fila['material']->nombre }}</span>
                        <div class="t"><i style="width: {{ $fila['m3'] / $maxM3 * 100 }}%"></i></div>
                        <span class="num">{{ Formato::m3($fila['m3']) }} m³</span>
                    </div>
                @empty
                    <div class="empty">Sin ventas en el mes</div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="g g2">
        <section class="card">
            <div class="hd">
                <h2>Últimas remisiones</h2>
                <a class="btn sm" href="{{ route('remisiones.index') }}">Ver todas</a>
            </div>
            <div class="tw">
                <table class="t t-movil">
                    <thead>
                        <tr><th>Remisión</th><th>Comprador</th><th>Fecha</th><th>Pago</th><th class="num">Total</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($resumen['ultimas'] as $remision)
                            <tr class="lk" onclick="window.location='{{ route('remisiones.show', $remision) }}'">
                                <td class="mono" data-label="Remisión">{{ $remision->numeroFormateado() }}</td>
                                <td class="principal">{{ $remision->comprador->nombre }}</td>
                                <td class="whitespace-nowrap" data-label="Fecha">{{ Formato::fechaCorta($remision->emitida_en) }} · {{ $remision->emitida_en->format('H:i') }}</td>
                                <td data-label="Pago"><x-tag-pago :forma="$remision->forma_pago" /></td>
                                <td class="num" data-label="Total"><b>{{ Formato::dinero($remision->total) }}</b></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty">Aún no hay remisiones.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="hd"><h2>Mejores compradores</h2><span class="muted text-xs">{{ $nombreMes }}</span></div>
            <div class="pd">
                @forelse ($resumen['compradores'] as $i => $fila)
                    <div class="flex items-center gap-3.5 py-[11px] {{ ! $loop->last ? 'border-b' : '' }}" style="border-color: var(--line)">
                        <span @class(['rank', 'top' => $i === 0])>{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="truncate">{{ $fila['comprador']->nombre }}</div>
                            <small class="muted">{{ $fila['remisiones'] }} compra(s)</small>
                        </div>
                        <b class="mono text-[13px]">{{ Formato::dinero($fila['valor']) }}</b>
                    </div>
                @empty
                    <div class="empty">Sin datos este mes</div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
