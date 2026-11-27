{{-- Pestañas de los reportes. Variables: $activa ('general'|'cliente'|'deudores'), $parametros (rango para conservarlo). --}}
@php($parametros ??= [])
<nav class="pills mb-5" aria-label="Tipo de reporte">
    <a @class(['pill', 'on' => $activa === 'general']) href="{{ route('reportes.index', $parametros) }}">Ventas</a>
    <a @class(['pill', 'on' => $activa === 'cliente']) href="{{ route('reportes.cliente', $parametros) }}">Por cliente</a>
    <a @class(['pill', 'on' => $activa === 'deudores']) href="{{ route('reportes.deudores') }}">Clientes que deben</a>
</nav>
