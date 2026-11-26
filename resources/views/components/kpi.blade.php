@props(['etiqueta', 'valor' => null, 'color' => 'var(--navy)'])

<div {{ $attributes->merge(['class' => 'kp']) }} style="--c: {{ $color }}">
    <div class="l">{{ $etiqueta }}</div>
    <div class="v">{{ $valor }}</div>
    <div class="n">{{ $slot }}</div>
</div>
