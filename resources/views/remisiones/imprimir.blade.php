<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Imprimir remisión {{ $remision->numeroFormateado() }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon-64.png') }}">
    @include('remisiones.partials.estilos-documento')
    <style>
        @page { size: letter; margin: 12mm; }
        body { margin: 0; background: #ece8de; font-family: 'DejaVu Sans', Arial, sans-serif; }
        .barra { position: sticky; top: 0; background: #162342; color: #fff; padding: 12px 16px; display: flex; gap: 10px; align-items: center; justify-content: space-between; flex-wrap: wrap; z-index: 5; }
        .barra b { font-size: 15px; }
        .barra a, .barra button { font: 600 15px Arial, sans-serif; border-radius: 8px; padding: 12px 18px; border: 0; cursor: pointer; text-decoration: none; }
        .barra .imp { background: #da7d15; color: #1c1306; }
        .barra .vol { background: #fff; color: #162342; }
        .hojas { max-width: 820px; margin: 16px auto 40px; padding: 0 10px; }
        .copia { background: #fff; padding: 14px; margin-bottom: 16px; box-shadow: 0 4px 14px rgba(0, 0, 0, .12); }
        @media print {
            body { background: #fff; }
            .barra { display: none; }
            .hojas { margin: 0; max-width: none; padding: 0; }
            .copia { box-shadow: none; padding: 0; margin: 0; break-after: page; page-break-after: always; }
            .copia:last-child { break-after: auto; page-break-after: auto; }
        }
    </style>
</head>
<body>
    <div class="barra">
        <b>Remisión {{ $remision->numeroFormateado() }} · {{ count($copias) }} copias</b>
        <span style="display: flex; gap: 8px">
            <a class="vol" href="{{ route('remisiones.show', $remision) }}">Volver</a>
            <button class="imp" type="button" onclick="window.print()">Imprimir</button>
        </span>
    </div>

    <div class="hojas">
        @foreach ($copias as $copia)
            <div class="copia">
                @include('remisiones.partials.documento', ['copia' => $copia, 'paraPdf' => false])
            </div>
        @endforeach
    </div>

    @if (request()->boolean('auto'))
        <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
    @endif
</body>
</html>
