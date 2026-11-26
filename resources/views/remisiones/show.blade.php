@use('App\Support\Formato')
@php($recienEmitida = session('emitida'))

<x-app-layout>
    <x-slot name="titulo">Remisión {{ $remision->numeroFormateado() }}</x-slot>

    @include('remisiones.partials.estilos-documento')

    @if ($recienEmitida)
        <section class="card mb-6">
            <div class="pd text-center">
                <div class="mx-auto mb-3 grid h-16 w-16 place-items-center rounded-full" style="background: var(--ok)">
                    <svg viewBox="0 0 24 24" class="h-9 w-9" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
                </div>
                <h1 style="font-size: 2rem">¡Remisión generada!</h1>
                <p class="sub mx-auto">
                    Remisión <b class="mono" style="color: var(--navy)">{{ $remision->numeroFormateado() }}</b> por
                    <b style="color: var(--navy)">{{ Formato::dinero($remision->total) }}</b> quedó registrada.
                    Ahora imprímela: salen <b>{{ (int) $config['remision_copias'] }} copias</b> (una para ti y dos para el cliente) para firmarlas.
                </p>
            </div>
        </section>
    @else
        <x-encabezado ceja="Remisión" :subtitulo="$remision->comprador->nombre.' · '.Formato::fecha($remision->emitida_en)">
            N° <em>{{ $remision->numeroFormateado() }}</em>
        </x-encabezado>
    @endif

    <div class="mb-6 grid gap-3 sm:flex sm:flex-wrap">
        <a class="btn acento grande" href="{{ route('remisiones.imprimir', [$remision, 'auto' => 1]) }}">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6" /><rect x="3" y="9" width="18" height="8" rx="2" /><path d="M6 14h12v7H6z" /></svg>
            Imprimir {{ (int) $config['remision_copias'] }} copias
        </a>
        <a class="btn grande" href="{{ route('remisiones.pdf', $remision) }}">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12" /><path d="M7 10l5 5 5-5" /><path d="M4 19h16" /></svg>
            Descargar PDF
        </a>
        <a class="btn pri grande" href="{{ route('remisiones.create') }}">+ Nueva remisión</a>
    </div>

    <section class="card">
        <div class="hd">
            <h2>Así queda la remisión</h2>
            <x-tag-pago :forma="$remision->forma_pago" />
        </div>
        <div class="pd">
            <div class="overflow-x-auto">
                <div class="min-w-[640px] bg-white p-3">
                    @include('remisiones.partials.documento', ['copia' => 'VISTA PREVIA', 'paraPdf' => false])
                </div>
            </div>
            <p class="muted mt-2 text-[12.5px] sm:hidden">Desliza a los lados para ver toda la remisión.</p>
        </div>
    </section>
</x-app-layout>
