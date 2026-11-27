@use('App\Support\Formato')
@php
    $hoy = now()->toDateString();
    $cuentas = [
        ['titulo' => 'Deuda (compras a crédito)', 'filas' => $credito, 'vacio' => 'No tiene compras a crédito.', 'sumaTexto' => 'Debe más', 'restaTexto' => 'Abonó'],
        ['titulo' => 'Anticipos (saldo a favor)', 'filas' => $anticipos, 'vacio' => 'No ha dejado anticipos.', 'sumaTexto' => 'Entregó', 'restaTexto' => 'Usó'],
    ];
@endphp

<x-app-layout>
    <x-slot name="titulo">Cartera · {{ $comprador->nombre }}</x-slot>

    <x-encabezado :ceja="'Cartera · '.$comprador->documentoCompleto()" :subtitulo="$comprador->ciudad">
        {{ $comprador->nombre }}
        <x-slot name="acciones">
            <a class="btn" href="{{ route('cartera.estado-excel', $comprador) }}">Estado de cuenta Excel</a>
            <a class="btn" href="{{ route('cartera.estado-pdf', $comprador) }}">Estado de cuenta PDF</a>
        </x-slot>
    </x-encabezado>

    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="kp" style="--c: var(--bad)">
            <div class="l">Debe</div>
            <div class="v" style="color: {{ $deuda > 0 ? 'var(--bad)' : 'var(--navy)' }}">{{ Formato::dinero($deuda) }}</div>
            <button type="button" class="btn pri mt-2 w-full sm:w-auto" x-data @click="$dispatch('open-modal', 'abono')" @disabled($deuda <= 0)>Registrar abono</button>
        </div>
        <div class="kp" style="--c: var(--ok)">
            <div class="l">Anticipo a favor</div>
            <div class="v" style="color: {{ $anticipo > 0 ? 'var(--ok)' : 'var(--navy)' }}">{{ Formato::dinero($anticipo) }}</div>
            <button type="button" class="btn mt-2 w-full sm:w-auto" x-data @click="$dispatch('open-modal', 'anticipo')">Registrar anticipo</button>
        </div>
    </div>

    <div class="grid gap-5">
        @foreach ($cuentas as $cuenta)
            <section class="card">
                <div class="hd"><h2>{{ $cuenta['titulo'] }}</h2><span class="muted text-xs">Lo más reciente primero</span></div>
                <div class="tw mt-3">
                    <table class="t t-movil">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Movimiento</th>
                                <th>Detalle</th>
                                <th class="num">{{ $cuenta['sumaTexto'] }}</th>
                                <th class="num">{{ $cuenta['restaTexto'] }}</th>
                                <th class="num">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cuenta['filas'] as $fila)
                                @php($m = $fila['movimiento'])
                                <tr>
                                    <td class="whitespace-nowrap" data-label="Fecha">{{ $m->registrado_en->format('d/m/Y') }}</td>
                                    <td class="principal">{{ $m->tipo->label() }}</td>
                                    <td data-label="Detalle">
                                        @if ($m->remision)
                                            <a class="mono underline" style="color: var(--navy)" href="{{ route('remisiones.show', $m->remision) }}">{{ $m->remision->numeroFormateado() }}</a>
                                        @endif
                                        {{ $m->medio_pago?->label() }}
                                        <span class="muted">{{ $m->nota }}</span>
                                    </td>
                                    <td class="num" data-label="{{ $cuenta['sumaTexto'] }}">{{ $m->valor > 0 ? Formato::dinero($m->valor) : '—' }}</td>
                                    <td class="num" data-label="{{ $cuenta['restaTexto'] }}">{{ $m->valor < 0 ? Formato::dinero(-$m->valor) : '—' }}</td>
                                    <td class="num" data-label="Saldo"><b>{{ Formato::dinero($fila['saldo']) }}</b></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty">{{ $cuenta['vacio'] }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>

    {{-- Registrar abono / anticipo --}}
    @foreach ([
        ['nombre' => 'abono', 'titulo' => 'Registrar abono', 'ruta' => route('cartera.abono', $comprador), 'ayuda' => 'Debe actualmente '.Formato::dinero($deuda).'. El abono no puede ser mayor.', 'boton' => 'Guardar abono'],
        ['nombre' => 'anticipo', 'titulo' => 'Registrar anticipo', 'ruta' => route('cartera.anticipo', $comprador), 'ayuda' => 'El dinero queda a favor del comprador y se descuenta cuando paga una remisión con "Anticipo".', 'boton' => 'Guardar anticipo'],
    ] as $form)
        <x-modal :name="$form['nombre']" :show="$errors->{$form['nombre']}->any()" maxWidth="lg" focusable>
            <form method="POST" action="{{ $form['ruta'] }}">
                @csrf
                <div class="md-hd">
                    <div class="ey">{{ $comprador->nombre }}</div>
                    <h2>{{ $form['titulo'] }}</h2>
                </div>
                <div class="md-bd">
                    <x-alerta-grande titulo="Revisa estos datos" :errores="$errors->{$form['nombre']}->all()" />
                    <p class="aviso text-[14px]">{{ $form['ayuda'] }}</p>
                    <div class="field">
                        <label for="{{ $form['nombre'] }}-valor">Valor recibido (pesos)</label>
                        <input id="{{ $form['nombre'] }}-valor" name="valor" class="inp mono text-lg" inputmode="numeric" required value="{{ $errors->{$form['nombre']}->any() ? old('valor') : '' }}" placeholder="Ej. 500.000">
                    </div>
                    <div class="field">
                        <span class="lbl">¿Cómo pagó?</span>
                        <div class="seg">
                            <label><input type="radio" name="medio_pago" value="efectivo" @checked(old('medio_pago', 'efectivo') === 'efectivo')> Efectivo</label>
                            <label><input type="radio" name="medio_pago" value="transferencia" @checked(old('medio_pago') === 'transferencia')> Transferencia</label>
                        </div>
                    </div>
                    <div class="f2">
                        <div class="field">
                            <label for="{{ $form['nombre'] }}-fecha">Fecha</label>
                            <input id="{{ $form['nombre'] }}-fecha" type="date" name="fecha" class="inp" max="{{ $hoy }}" value="{{ old('fecha', $hoy) }}" required>
                        </div>
                        <div class="field">
                            <label for="{{ $form['nombre'] }}-nota">Nota (opcional)</label>
                            <input id="{{ $form['nombre'] }}-nota" name="nota" class="inp" maxlength="255" value="{{ $errors->{$form['nombre']}->any() ? old('nota') : '' }}" placeholder="Ej. Consignación Bancolombia">
                        </div>
                    </div>
                </div>
                <div class="md-ft">
                    <button type="button" class="btn" @click="$dispatch('close-modal', '{{ $form['nombre'] }}')">Cancelar</button>
                    <button type="submit" class="btn pri">{{ $form['boton'] }}</button>
                </div>
            </form>
        </x-modal>
    @endforeach
</x-app-layout>
