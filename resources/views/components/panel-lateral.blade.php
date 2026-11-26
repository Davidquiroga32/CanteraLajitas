@props(['nombre', 'ceja' => null, 'titulo' => '', 'detalle' => null])

{{-- Panel que entra por la derecha (drawer del boceto). Se abre con $dispatch('open-panel', 'nombre'). --}}
<div
    x-data="{ abierto: false }"
    x-on:open-panel.window="$event.detail === '{{ $nombre }}' && (abierto = true)"
    x-on:close-panel.window="abierto = false"
    x-on:keydown.escape.window="abierto = false"
    x-show="abierto"
    x-cloak
    class="fixed inset-0 z-50 flex justify-end"
>
    <div class="absolute inset-0" style="background: rgba(22, 35, 66, .35)" x-on:click="abierto = false" x-show="abierto" x-transition.opacity></div>
    <aside class="relative h-full w-full max-w-[480px] overflow-auto bg-tarjeta shadow-2xl"
           x-show="abierto"
           x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-10 opacity-0" x-transition:enter-end="translate-x-0 opacity-100">
        <div class="relative bg-navy px-7 py-7 text-white">
            <button type="button" class="absolute right-4 top-4 grid h-8 w-8 place-items-center rounded-full border border-white/30" x-on:click="abierto = false" aria-label="Cerrar">
                <x-icono nombre="cerrar" class="h-4 w-4" />
            </button>
            @if ($ceja)
                <div class="ey" style="color: var(--orange)">{{ $ceja }}</div>
            @endif
            <h2 class="text-white" style="font-size: 1.7rem">{{ $titulo }}</h2>
            @if ($detalle)
                <p class="mt-1.5 text-sm" style="color: #c9d0e0">{{ $detalle }}</p>
            @endif
        </div>
        <div class="px-7 py-6">
            {{ $slot }}
        </div>
    </aside>
</div>
