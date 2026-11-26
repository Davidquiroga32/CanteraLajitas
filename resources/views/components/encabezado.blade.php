@props(['ceja' => null, 'subtitulo' => null, 'destacado' => false])

{{--
    Encabezado de página: ceja (texto pequeño), título con <em> opcional, subtítulo y acciones.
    Con `destacado` se muestra como banda azul (usado en el inicio).
--}}
<div @class(['ph', 'hero' => $destacado])>
    <div>
        @if ($ceja)
            <div class="ey">{{ $ceja }}</div>
        @endif
        <h1>{{ $slot }}</h1>
        @if ($subtitulo)
            <p class="sub">{{ $subtitulo }}</p>
        @endif
    </div>
    @isset($acciones)
        <div class="acts">{{ $acciones }}</div>
    @endisset
</div>
