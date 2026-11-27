@use('App\Support\Formato')
@php
    $cuentas = [
        ['titulo' => 'Deuda (compras a crédito)', 'filas' => $credito, 'vacio' => 'No tiene compras a crédito.', 'sumaTexto' => 'Debe más', 'restaTexto' => 'Abonó'],
        ['titulo' => 'Anticipos (saldo a favor)', 'filas' => $anticipos, 'vacio' => 'No ha dejado anticipos.', 'sumaTexto' => 'Entregó', 'restaTexto' => 'Usó'],
    ];
    $anterior = url()->previous();
    $origenes = [
        route('cartera.index') => 'Volver a cartera',
        route('compradores.index') => 'Volver a compradores',
        route('reportes.deudores') => 'Volver a clientes que deben',
        route('reportes.cliente', $comprador) => 'Volver al reporte del cliente',
    ];
    [$urlVolver, $textoVolver] = [route('cartera.index'), 'Volver a cartera'];
    foreach ($origenes as $url => $texto) {
        if ($anterior === $url || str_starts_with($anterior, $url.'?')) {
            [$urlVolver, $textoVolver] = [$anterior, $texto];
        }
    }
@endphp

<x-app-layout>
    <x-slot name="titulo">Cartera · {{ $comprador->nombre }}</x-slot>

    <a href="{{ $urlVolver }}" class="btn mb-4 w-fit">
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6" /></svg>
        {{ $textoVolver }}
    </a>

    <x-encabezado :ceja="'Cartera · '.$comprador->documentoCompleto()" :subtitulo="$comprador->ciudad">
        {{ $comprador->nombre }}
        <x-slot name="acciones">
            <a class="btn" href="{{ route('cartera.estado-excel', $comprador) }}">Estado de cuenta Excel</a>
            <a class="btn" href="{{ route('cartera.estado-pdf', $comprador) }}">Estado de cuenta PDF</a>
        </x-slot>
    </x-encabezado>

    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="kp" style="--c: var(--bad)">
            <div class="l">Debe</div>
            <div class="v" style="color: {{ $deuda > 0 ? 'var(--bad)' : 'var(--navy)' }}">{{ Formato::dinero($deuda) }}</div>
            <button type="button" class="btn pri mt-2 w-full sm:w-auto" x-data @click="$dispatch('open-modal', 'abono')" @disabled($deuda <= 0)>Registrar abono</button>
        </div>
        <div class="kp" style="--c: var(--ok)">
            <div class="l">Anticipo a favor</div>
            <div class="v" style="color: {{ $anticipo > 0 ? 'var(--ok)' : 'var(--navy)' }}">{{ Formato::dinero($anticipo) }}</div>
            <button type="button" class="btn mt-2 w-full sm:w-auto" x-data @click="$dispatch('open-modal', 'anticipo')">Registrar anticipo</button>
        </div>
    </div>

    <div class="grid gap-5">
        @foreach ($cuentas as $cuenta)
            <section class="card">
                <div class="hd"><h2>{{ $cuenta['titulo'] }}</h2><span class="muted text-xs">Lo más reciente primero</span></div>
                <div class="tw mt-3">
                    <table class="t t-movil">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Movimiento</th>
                                <th>Detalle</th>
                                <th class="num">{{ $cuenta['sumaTexto'] }}</th>
                                <th class="num">{{ $cuenta['restaTexto'] }}</th>
                                <th class="num">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cuenta['filas'] as $fila)
                                @php($m = $fila['movimiento'])
                                <tr>
                                    <td class="whitespace-nowrap" data-label="Fecha">{{ $m->registrado_en->format('d/m/Y') }}</td>
                                    <td class="principal">{{ $m->tipo->label() }}</td>
                                    <td data-label="Detalle">
                                        @if ($m->remision)
                                            <a class="mono underline" style="color: var(--navy)" href="{{ route('remisiones.show', $m->remision) }}">{{ $m->remision->numeroFormateado() }}</a>
                                        @endif
                                        {{ $m->medio_pago?->label() }}
                                        <span class="muted">{{ $m->nota }}</span>
                                    </td>
                                    <td class="num" data-label="{{ $cuenta['sumaTexto'] }}">{{ $m->valor > 0 ? Formato::dinero($m->valor) : '—' }}</td>
                                    <td class="num" data-label="{{ $cuenta['restaTexto'] }}">{{ $m->valor < 0 ? Formato::dinero(-$m->valor) : '—' }}</td>
                                    <td class="num" data-label="Saldo"><b>{{ Formato::dinero($fila['saldo']) }}</b></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty">{{ $cuenta['vacio'] }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>

    {{-- Registrar abono / anticipo --}}
    <x-modal-pago-cartera :comprador="$comprador" tipo="abono" :ayuda="'Debe actualmente '.Formato::dinero($deuda).'. El abono no puede ser mayor.'" />
    <x-modal-pago-cartera :comprador="$comprador" tipo="anticipo" ayuda='El dinero queda a favor del comprador y se descuenta cuando paga una remisión con "Anticipo".' />
</x-app-layout>
