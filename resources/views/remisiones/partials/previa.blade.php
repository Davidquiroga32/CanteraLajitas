{{-- Vista previa en vivo (Alpine). Usa el estado de nuevaRemision. --}}
<div class="hoja">
    <div class="flex items-start justify-between gap-3">
        <img src="{{ asset('images/logo-cantera-lajitas.png') }}" alt="Cantera Lajitas S.A.S." class="h-auto w-[78px]" width="78" height="73">
        <div class="text-right">
            <div class="mono muted text-[10px] tracking-[0.14em]">REMISIÓN DE COMPRA</div>
            <div class="mono text-[20px] font-semibold" style="color: var(--bad)">{{ str_pad((string) $numero, 6, '0', STR_PAD_LEFT) }}</div>
            <div class="muted text-[11.5px]">{{ now()->format('d/m/Y') }} · provisional</div>
        </div>
    </div>

    <div class="mt-3">
        <div class="fila"><span>Cliente</span><span :class="! comprador.nombre && 'pend'" x-text="comprador.nombre || 'Pendiente…'"></span></div>
        <div class="fila"><span>NIT / CC</span><span :class="! comprador.documento && 'pend'" x-text="comprador.documento ? comprador.tipo_documento + ' ' + comprador.documento : 'Pendiente…'"></span></div>
        <div class="fila"><span>Conductor</span><span :class="! conductor && 'pend'" x-text="conductor || 'Pendiente…'"></span></div>
        <div class="fila"><span>Placa</span><span class="mono" :class="! placa && 'pend'" x-text="placa ? placa.toUpperCase() : 'Pendiente…'"></span></div>
    </div>

    <table class="mt-3 w-full text-[12.5px]">
        <thead>
            <tr class="mono muted text-[10px] tracking-[0.08em]">
                <th class="py-1 text-left font-medium">MATERIAL</th>
                <th class="py-1 text-right font-medium">M³</th>
                <th class="py-1 text-right font-medium">VR/UNIT</th>
                <th class="py-1 text-right font-medium">VR/TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <template x-for="(l, i) in lineasValidas" :key="i">
                <tr class="border-t" style="border-color: #ebe6da">
                    <td class="py-1.5" x-text="material(l.material_id).nombre"></td>
                    <td class="mono py-1.5 text-right" x-text="m3(l.cantidad_m3)"></td>
                    <td class="mono py-1.5 text-right" x-text="dinero(precio(l))"></td>
                    <td class="mono py-1.5 text-right" x-text="dinero(subtotal(l))"></td>
                </tr>
            </template>
            <tr x-show="! lineasValidas.length"><td colspan="4" class="pend py-4 text-center">Agrega un material con su cantidad</td></tr>
        </tbody>
    </table>

    @php($retencionesPrevia = \App\Support\Retenciones::vigentes())
    <template x-if="retefuente + reteica > 0">
        <div class="mt-2 grid gap-0.5 border-t-2 pt-2 text-[12.5px]" style="border-color: #1c1a16">
            <div class="flex justify-between"><span class="mono muted text-[11px] tracking-[0.08em]">SUBTOTAL</span><span class="mono" x-text="dinero(subtotalGeneral)"></span></div>
            <div class="flex justify-between" x-show="retefuente > 0"><span class="mono muted text-[11px] tracking-[0.08em]">{{ mb_strtoupper($retencionesPrevia->etiquetaRetefuente()) }}</span><span class="mono" x-text="'− ' + dinero(retefuente)"></span></div>
            <div class="flex justify-between" x-show="reteica > 0"><span class="mono muted text-[11px] tracking-[0.08em]">{{ mb_strtoupper($retencionesPrevia->etiquetaReteica()) }}</span><span class="mono" x-text="'− ' + dinero(reteica)"></span></div>
        </div>
    </template>
    <div class="mt-2 flex items-baseline justify-end gap-4 border-t-2 pt-2" style="border-color: #1c1a16">
        <span class="mono text-[11px] tracking-[0.08em]" x-text="retefuente + reteica > 0 ? 'TOTAL A PAGAR' : 'TOTAL'">TOTAL</span>
        <b class="text-[20px]" style="font-family: var(--serif)" x-text="dinero(total)"></b>
    </div>

    <div class="mt-3">
        <div class="fila"><span>Forma de pago</span><span :class="! forma_pago && 'pend'" x-text="({efectivo: 'Efectivo', transferencia: 'Transferencia', credito: 'Crédito', anticipo: 'Anticipo', donacion: 'Donación'}[forma_pago] || 'Pendiente…') + (debeElegirPagoDiferencia && pago_diferencia ? ' + ' + dinero(diferenciaAnticipo) + ' ' + {efectivo: 'en efectivo', transferencia: 'por transferencia', credito: 'a crédito'}[pago_diferencia] : '')"></span></div>
        <div class="fila"><span>Máquina</span><span :class="! maquina && 'pend'" x-text="maquina || 'Pendiente…'"></span></div>
        <div class="fila"><span>Destino</span><span :class="! destino && 'pend'" x-text="destino || 'Pendiente…'"></span></div>
        <div class="fila"><span>Observaciones</span><span class="text-right" :class="! observaciones && 'pend'" x-text="observaciones || 'Espacio para escribir a mano'"></span></div>
        <div class="fila"><span>Despacha</span><span>{{ auth()->user()->name }}</span></div>
    </div>
</div>
