@use('App\Support\Formato')
@php($esAnticipos = $vista === 'anticipos')

<x-app-layout>
    <x-slot name="titulo">Cartera</x-slot>

    <x-encabezado ceja="Cartera" subtitulo="Compradores que deben (compras a crédito) y compradores con dinero a favor (anticipos). Toca uno para ver sus movimientos y registrar abonos o anticipos.">
        Cartera <em>y anticipos</em>
        <x-slot name="acciones">
            <a class="btn" href="{{ route('cartera.excel', ['ver' => $vista]) }}">Exportar Excel</a>
            <a class="btn" href="{{ route('cartera.pdf', ['ver' => $vista]) }}">Exportar PDF</a>
        </x-slot>
    </x-encabezado>

    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <a href="{{ route('cartera.index') }}" @class(['kp block', 'ring-2 ring-navy' => ! $esAnticipos]) style="--c: var(--bad)">
            <div class="l">Por cobrar</div>
            <div class="v">{{ Formato::dinero($totalDeuda) }}</div>
            <div class="n">{{ $deudores }} comprador(es) deben</div>
        </a>
        <a href="{{ route('cartera.index', ['ver' => 'anticipos']) }}" @class(['kp block', 'ring-2 ring-navy' => $esAnticipos]) style="--c: var(--ok)">
            <div class="l">Anticipos a favor</div>
            <div class="v">{{ Formato::dinero($totalAnticipos) }}</div>
            <div class="n">{{ $conAnticipo }} comprador(es) con saldo a favor</div>
        </a>
    </div>

    <section class="card">
        <div class="flex flex-wrap items-end justify-between gap-3 border-b px-[22px] py-[18px]" style="border-color: var(--line)">
            <div class="pills">
                <a @class(['pill', 'on' => ! $esAnticipos]) href="{{ route('cartera.index') }}">Deudores</a>
                <a @class(['pill', 'on' => $esAnticipos]) href="{{ route('cartera.index', ['ver' => 'anticipos']) }}">Anticipos</a>
            </div>
            <form method="GET" action="{{ route('cartera.index') }}" class="flex min-w-[240px] flex-1 gap-2 sm:max-w-sm" x-data="filtroEnVivo" role="search">
                @if ($esAnticipos)<input type="hidden" name="ver" value="anticipos">@endif
                <input type="search" name="q" class="inp" value="{{ $busqueda }}" placeholder="Escribe para buscar un comprador…" aria-label="Buscar comprador" autocomplete="off">
            </form>
        </div>

        <div class="tw" data-vivo="lista">
            <table class="t t-movil">
                <thead>
                    <tr>
                        <th>Comprador</th>
                        <th>Documento</th>
                        <th>Municipio</th>
                        <th class="num">{{ $esAnticipos ? 'Saldo a favor' : 'Debe' }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($compradores as $comprador)
                        @php($url = route('cartera.show', $comprador))
                        <tr class="lk" onclick="window.location='{{ $url }}'">
                            <td class="principal"><a href="{{ $url }}" class="font-medium" style="color: var(--navy)">{{ $comprador->nombre }}</a></td>
                            <td class="mono text-[13px]" data-label="Documento">{{ $comprador->documentoCompleto() }}</td>
                            <td data-label="Municipio">{{ $comprador->ciudad ?? '—' }}</td>
                            <td class="num" data-label="{{ $esAnticipos ? 'Saldo a favor' : 'Debe' }}">
                                <b @style(['color: var(--bad)' => ! $esAnticipos, 'color: var(--ok)' => $esAnticipos])>{{ Formato::dinero($esAnticipos ? $comprador->saldo_anticipo : $comprador->saldo_credito) }}</b>
                            </td>
                            <td class="num" data-label="">
                                <a class="btn sm" href="{{ $url }}">{{ $esAnticipos ? 'Ver movimientos' : 'Registrar abono' }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">
                                {{ $esAnticipos ? 'Ningún comprador tiene anticipo a favor.' : 'Nadie debe. ¡Cartera al día!' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <p class="muted mt-4 text-[13px]">¿Vas a recibir un anticipo de un comprador que no aparece aquí? Búscalo en <a class="underline" href="{{ route('compradores.index') }}">Compradores</a> y usa "Ver cartera".</p>
</x-app-layout>
