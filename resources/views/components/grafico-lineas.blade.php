@use('App\Support\Formato')
@props(['serie', 'alto' => 250])

{{--
    Gráfico de línea en SVG generado en el servidor (como el boceto).
    $serie: lista de ['etiqueta' => string, 'valor' => int, 'detalle' => string].
--}}
@php

    $ancho = 760;
    $m = ['l' => 58, 'r' => 14, 't' => 14, 'b' => 30];
    $iw = $ancho - $m['l'] - $m['r'];
    $ih = $alto - $m['t'] - $m['b'];
    $n = count($serie);
    $maximo = max(1, ...array_column($serie, 'valor')) * 1.12;
    $x = fn (int $i): float => $m['l'] + ($n > 1 ? $i / ($n - 1) : 0.5) * $iw;
    $y = fn (float $v): float => $m['t'] + $ih - $v / $maximo * $ih;
    $puntos = collect($serie)->map(fn ($d, $i) => [$x($i), $y($d['valor'])]);
    $linea = $puntos->map(fn ($p, $i) => ($i ? 'L' : 'M').number_format($p[0], 1, '.', '').' '.number_format($p[1], 1, '.', ''))->implode(' ');
    $paso = (int) ceil($n / 7);
    $franja = $iw / max(1, $n);
@endphp

<div class="chart" x-data="{ tip: null, tx: 0, ty: 0 }">
    <svg viewBox="0 0 {{ $ancho }} {{ $alto }}" width="100%" role="img" aria-label="Ventas por día">
        @foreach ([0, .25, .5, .75, 1] as $f)
            <line x1="{{ $m['l'] }}" x2="{{ $ancho - $m['r'] }}" y1="{{ $y($maximo * $f) }}" y2="{{ $y($maximo * $f) }}" stroke="#e3ddcd" />
            <text x="{{ $m['l'] - 10 }}" y="{{ $y($maximo * $f) + 4 }}" text-anchor="end" font-size="10.5" fill="#7a7466" font-family="JetBrains Mono">{{ Formato::millones($maximo * $f) }}</text>
        @endforeach

        @if ($n > 1)
            <path d="{{ $linea }} L{{ $x($n - 1) }} {{ $m['t'] + $ih }} L{{ $x(0) }} {{ $m['t'] + $ih }}Z" fill="rgba(218,125,21,.14)" />
            <path d="{{ $linea }}" fill="none" stroke="#162342" stroke-width="2.2" stroke-linejoin="round" />
        @endif

        @foreach ($serie as $i => $d)
            @if ($i % $paso === 0 || $i === $n - 1)
                <text x="{{ $x($i) }}" y="{{ $alto - 8 }}" text-anchor="middle" font-size="10.5" fill="#7a7466">{{ $d['etiqueta'] }}</text>
            @endif
            <circle cx="{{ $x($i) }}" cy="{{ $y($d['valor']) }}" r="3.5" fill="#da7d15" :opacity="tip && tip.i === {{ $i }} ? 1 : 0" />
            <rect x="{{ $x($i) - $franja / 2 }}" y="{{ $m['t'] }}" width="{{ $franja }}" height="{{ $ih }}" fill="transparent"
                  @mousemove="tip = { i: {{ $i }}, e: @js($d['etiqueta']), v: @js(Formato::dinero($d['valor'])), d: @js($d['detalle']) }; tx = $event.clientX; ty = $event.clientY"
                  @mouseleave="tip = null" />
        @endforeach
    </svg>

    <div class="tip" x-show="tip" x-cloak :style="`left:${Math.min(tx + 14, window.innerWidth - 190)}px;top:${ty - 58}px`">
        <span x-text="tip?.e"></span>
        <b x-text="tip?.v"></b>
        <span x-text="tip?.d"></span>
    </div>
</div>
