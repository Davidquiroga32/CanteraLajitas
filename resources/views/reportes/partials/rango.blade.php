@use('App\Support\RangoFechas')
{{-- Selector del periodo. Variables: $rango (RangoFechas), $accion (URL de la página actual). --}}
<section class="card mb-5">
    <div class="pd grid gap-4">
        <div class="pills">
            @foreach (RangoFechas::PRESETS as $clave => $texto)
                <a @class(['pill', 'on' => $rango->preset === (string) $clave]) href="{{ $accion }}?rango={{ $clave }}">{{ $texto }}</a>
            @endforeach
        </div>

        <form method="GET" action="{{ $accion }}" class="grid grid-cols-2 items-end gap-3 md:grid-cols-[1fr_1fr_auto]">
            <div class="field">
                <label for="desde">Desde</label>
                <input id="desde" type="date" name="desde" class="inp" value="{{ $rango->desde->toDateString() }}" max="{{ today()->toDateString() }}">
            </div>
            <div class="field">
                <label for="hasta">Hasta</label>
                <input id="hasta" type="date" name="hasta" class="inp" value="{{ $rango->hasta->toDateString() }}" max="{{ today()->toDateString() }}">
            </div>
            <button type="submit" class="btn pri col-span-2 md:col-span-1">Ver estas fechas</button>
        </form>

        <p class="muted text-[13px]">Mostrando: <b style="color: var(--navy)">{{ $rango->descripcion() }}</b> ({{ $rango->dias() }} {{ $rango->dias() === 1 ? 'día' : 'días' }})</p>
    </div>
</section>
