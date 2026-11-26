@props(['titulo' => 'Falta información', 'errores' => []])

{{-- Aviso grande y claro con la lista de lo que falta, pensado para usuarios poco familiarizados con la tecnología. --}}
@if (count($errores))
    <div {{ $attributes->merge(['class' => 'alerta-grande']) }} role="alert" tabindex="-1" x-data x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' }); $el.focus()">
        <h3>{{ $titulo }}</h3>
        <ul>
            @foreach ($errores as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
