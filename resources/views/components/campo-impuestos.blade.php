@props(['modelo', 'prefijo' => null])
@php
    $nombre = fn (string $campo): string => $prefijo ? "{$prefijo}[{$campo}]" : $campo;
    $retenciones = \App\Support\Retenciones::vigentes();
@endphp

{{--
    Pregunta si el comprador paga impuestos y cuáles (Retención en la fuente / ICA).
    `modelo` es el objeto de Alpine con impuestos, retefuente y reteica.
--}}
<div class="field">
    <span class="lbl">¿Paga impuestos (retenciones)?</span>
    <div class="seg">
        <label><input type="radio" name="{{ $nombre('impuestos') }}" value="no" x-model="{{ $modelo }}.impuestos"> No</label>
        <label><input type="radio" name="{{ $nombre('impuestos') }}" value="si" x-model="{{ $modelo }}.impuestos"> Sí</label>
    </div>
</div>
<div class="grid gap-3 rounded-lg p-3" style="background: #f3efe5" x-show="{{ $modelo }}.impuestos === 'si'" x-cloak>
    <span class="text-[14px]">Marca cuál descuenta (puede ser uno o los dos):</span>
    <label class="flex cursor-pointer items-center gap-3 text-[15px]">
        <input type="checkbox" name="{{ $nombre('retefuente') }}" value="1" x-model="{{ $modelo }}.retefuente" class="h-5 w-5 rounded border-linea-2 text-navy focus:ring-navy">
        {{ $retenciones->etiquetaRetefuente() }}
    </label>
    <label class="flex cursor-pointer items-center gap-3 text-[15px]">
        <input type="checkbox" name="{{ $nombre('reteica') }}" value="1" x-model="{{ $modelo }}.reteica" class="h-5 w-5 rounded border-linea-2 text-navy focus:ring-navy">
        {{ $retenciones->etiquetaReteica() }}
    </label>
    <p class="muted text-[13px]">Se descuentan del total de cada remisión de este comprador.</p>
</div>
