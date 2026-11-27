@props(['comprador', 'tipo', 'ayuda', 'volver' => null])

{{--
    Ventana para registrar un abono o un anticipo de un comprador.
    $tipo: 'abono' | 'anticipo'. $volver: 'compradores' para regresar al panel del comprador.
--}}
@php
    $hoy = now()->format('Y-m-d');
    $conErrores = $errors->{$tipo}->any();
    $titulo = $tipo === 'abono' ? 'Registrar abono' : 'Registrar anticipo';
    $ruta = $tipo === 'abono' ? route('cartera.abono', $comprador) : route('cartera.anticipo', $comprador);
@endphp

<x-modal :name="$tipo" :show="$conErrores" maxWidth="lg" focusable>
    <form method="POST" action="{{ $ruta }}">
        @csrf
        @if ($volver)
            <input type="hidden" name="_volver" value="{{ $volver }}">
        @endif
        <div class="md-hd">
            <div class="ey">{{ $comprador->nombre }}</div>
            <h2>{{ $titulo }}</h2>
        </div>
        <div class="md-bd">
            <x-alerta-grande titulo="Revisa estos datos" :errores="$errors->{$tipo}->all()" />
            <p class="aviso text-[14px]">{{ $ayuda }}</p>
            <div class="field">
                <label for="{{ $tipo }}-valor">Valor recibido (pesos)</label>
                <input id="{{ $tipo }}-valor" name="valor" class="inp mono text-lg" inputmode="numeric" autocomplete="off" required x-data x-miles value="{{ $conErrores ? old('valor') : '' }}" placeholder="Ej. 500.000">
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
                    <label for="{{ $tipo }}-fecha">Fecha</label>
                    <input id="{{ $tipo }}-fecha" type="date" name="fecha" class="inp" max="{{ $hoy }}" value="{{ old('fecha', $hoy) }}" required>
                </div>
                <div class="field">
                    <label for="{{ $tipo }}-nota">Nota (opcional)</label>
                    <input id="{{ $tipo }}-nota" name="nota" class="inp" maxlength="255" value="{{ $conErrores ? old('nota') : '' }}" placeholder="Ej. Consignación Bancolombia">
                </div>
            </div>
        </div>
        <div class="md-ft">
            <button type="button" class="btn" @click="$dispatch('close-modal', '{{ $tipo }}')">Cancelar</button>
            <button type="submit" class="btn pri">{{ $tipo === 'abono' ? 'Guardar abono' : 'Guardar anticipo' }}</button>
        </div>
    </form>
</x-modal>
