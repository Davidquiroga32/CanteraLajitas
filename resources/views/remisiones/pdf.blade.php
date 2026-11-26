<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Remisión {{ $remision->numeroFormateado() }}</title>
    @include('remisiones.partials.estilos-documento')
    <style>
        @page { margin: 14mm 12mm; }
        body { margin: 0; }
        .copia { page-break-after: always; }
        .copia:last-child { page-break-after: auto; }
    </style>
</head>
<body>
    @foreach ($copias as $copia)
        <div class="copia">
            @include('remisiones.partials.documento', ['copia' => $copia, 'paraPdf' => $paraPdf ?? false])
        </div>
    @endforeach
</body>
</html>
