@props(['nombre', 'ceja' => null, 'titulo' => '', 'detalle' => null, 'abierto' => false, 'alCerrar' => null])

{{--
    Panel que entra por la derecha (drawer del boceto).
    Se abre con $dispatch('open-panel', 'nombre') o con :abierto="true" al cargar.
    `alCerrar`: URL a la que se reemplaza la dirección al cerrarlo (opcional).
--}}
<div
    x-data="{
        abierto: @js((bool) $abierto),
        cerrar() {
            this.abierto = false;
            @if ($alCerrar) history.replaceState(null, '', @js($alCerrar)); @endif
        },
    }"
    x-on:open-panel.window="$event.detail === '{{ $nombre }}' && (abierto = true)"
    x-on:close-panel.window="cerrar()"
    x-on:keydown.escape.window="abierto && cerrar()"
    x-show="abierto"
    x-cloak
    class="fixed inset-0 z-40 flex justify-end"
>
    <div class="absolute inset-0" style="background: rgba(22, 35, 66, .35)" x-on:click="cerrar()" x-show="abierto" x-transition.opacity></div>
    <aside class="relative h-full w-full max-w-[500px] overflow-auto bg-tarjeta shadow-2xl" role="dialog" aria-modal="true" aria-label="{{ $titulo }}"
           x-show="abierto"
           x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-10 opacity-0" x-transition:enter-end="translate-x-0 opacity-100">
        <div class="relative bg-navy px-7 py-7 text-white">
            <button type="button" class="absolute right-4 top-4 grid h-9 w-9 place-items-center rounded-full border border-white/30 hover:bg-white/10" x-on:click="cerrar()" aria-label="Cerrar">
                <x-icono nombre="cerrar" class="h-4 w-4" />
            </button>
            @if ($ceja)
                <div class="ey" style="color: #f0a54a">{{ $ceja }}</div>
            @endif
            <h2 class="pr-10 text-white" style="font-size: 1.7rem">{{ $titulo }}</h2>
            @if ($detalle)
                <p class="mono mt-1.5 text-sm" style="color: #c3cbe0">{{ $detalle }}</p>
            @endif
        </div>
        <div class="px-7 py-6">
            {{ $slot }}
        </div>
    </aside>
</div>
