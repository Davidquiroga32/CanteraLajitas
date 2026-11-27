@use('App\Support\Formato')
@use('App\Enums\FormaPago')
@php
    $usuario = auth()->user();
    $retenciones = \App\Support\Retenciones::vigentes();
    $config = [
        'urlBuscar' => route('compradores.buscar'),
        'tasas' => ['retefuente' => $retenciones->porcentajeRetefuente, 'reteica' => $retenciones->porMilReteica],
        'materiales' => $materiales->map(fn ($m) => [
            'id' => $m->id,
            'nombre' => $m->nombre,
            'precio_m3' => $m->precio_m3,
            'stock_m3' => (float) $m->stock_m3,
            'color' => $m->color,
        ])->values(),
        'comprador' => $comprador ? [
            'id' => $comprador->id,
            'nombre' => $comprador->nombre,
            'tipo_documento' => $comprador->tipo_documento->value,
            'documento' => $comprador->documento,
            'ciudad' => $comprador->ciudad,
            'retefuente' => $comprador->retefuente,
            'reteica' => $comprador->reteica,
            'deuda' => (int) $comprador->saldo_credito,
            'anticipo' => (int) $comprador->saldo_anticipo,
        ] : (old('comprador') ? [
            'id' => null,
            'nombre' => old('comprador.nombre', ''),
            'tipo_documento' => old('comprador.tipo_documento', 'NIT'),
            'documento' => old('comprador.documento', ''),
            'ciudad' => old('comprador.ciudad', 'San José del Guaviare'),
            'impuestos' => old('comprador.impuestos', 'no'),
            'retefuente' => (bool) old('comprador.retefuente'),
            'reteica' => (bool) old('comprador.reteica'),
            'deuda' => 0,
            'anticipo' => 0,
        ] : null),
        'lineas' => collect(old('lineas', []))->map(fn ($l) => ['material_id' => (string) ($l['material_id'] ?? ''), 'cantidad_m3' => (string) ($l['cantidad_m3'] ?? '')])->values(),
        'valores' => [
            'conductor' => old('conductor', ''),
            'placa' => old('placa', ''),
            'maquina' => old('maquina', ''),
            'destino' => old('destino', ''),
            'forma_pago' => old('forma_pago', ''),
            'pago_diferencia' => old('pago_diferencia', ''),
            'observaciones' => old('observaciones', ''),
        ],
    ];
    $pagos = [
        FormaPago::Efectivo->value => 'Efectivo',
        FormaPago::Transferencia->value => 'Transferencia',
        FormaPago::Credito->value => 'Crédito',
        FormaPago::Anticipo->value => 'Anticipo',
        FormaPago::Donacion->value => 'Donación',
    ];
@endphp

<x-app-layout>
    <x-slot name="titulo">Nueva remisión</x-slot>

    <div x-data="nuevaRemision(@js($config))">
        <x-encabezado ceja="Remisión N° {{ str_pad((string) $siguienteNumero, 6, '0', STR_PAD_LEFT) }}"
                      subtitulo="Llena los cuatro pasos. Todos los campos son obligatorios. A la derecha (o abajo en el celular) ves cómo queda la remisión.">
            Nueva <em>remisión</em>
        </x-encabezado>

        @if ($materiales->isEmpty())
            <div class="alerta-grande mb-6" role="alert">
                <h3>No hay materiales con existencias</h3>
                <p class="text-base">Ningún producto tiene stock en patio. Pídele al administrador que actualice el inventario en <a class="underline" href="{{ route('materiales.index') }}">Materiales</a>.</p>
            </div>
        @endif

        {{-- Errores devueltos por el servidor --}}
        <x-alerta-grande class="mb-6" titulo="No se pudo generar la remisión. Revisa:" :errores="$errors->all()" />

        {{-- Lo que falta, revisado en el navegador antes de enviar --}}
        <div id="aviso-faltantes" class="alerta-grande mb-6" role="alert" tabindex="-1" x-show="faltantes.length" x-cloak>
            <h3>Falta llenar esto:</h3>
            <ul>
                <template x-for="f in faltantes" :key="f.clave + f.texto">
                    <li x-text="f.texto"></li>
                </template>
            </ul>
        </div>

        <form method="POST" action="{{ route('remisiones.store') }}" novalidate @submit="enviar($event)">
            @csrf
            <div class="nv-grid">
                <div class="grid gap-5">
                    {{-- 1. Comprador --}}
                    <section class="card">
                        <div class="pd">
                            <div class="paso"><span class="n">1</span><h2>Comprador</h2></div>

                            <template x-if="compradorElegido">
                                <div class="elegido">
                                    <input type="hidden" name="comprador_id" :value="comprador.id">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="text-[17px] font-semibold" style="color: var(--navy)" x-text="comprador.nombre"></div>
                                            <div class="mono text-[13px]" style="color: #4f5468" x-text="comprador.tipo_documento + ' ' + comprador.documento + ' · ' + (comprador.ciudad || '—')"></div>
                                        </div>
                                        <button type="button" class="btn sm" @click="cambiarComprador()">Cambiar</button>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span class="tag c" x-show="comprador.deuda > 0" x-text="'Debe ' + dinero(comprador.deuda)"></span>
                                        <span class="tag v" x-show="comprador.anticipo > 0" x-text="'Anticipo a favor ' + dinero(comprador.anticipo)"></span>
                                        <span class="tag g" x-show="!comprador.deuda && !comprador.anticipo">Al día</span>
                                        <span class="tag" x-show="comprador.retefuente">{{ $retenciones->etiquetaRetefuente() }}</span>
                                        <span class="tag" x-show="comprador.reteica">{{ $retenciones->etiquetaReteica() }}</span>
                                    </div>
                                </div>
                            </template>

                            <template x-if="! compradorElegido">
                                <div>
                                    <div class="field">
                                        <label for="buscar-comprador">Buscar comprador registrado</label>
                                        <input id="buscar-comprador" class="inp" x-model="busqueda" @input.debounce.300ms="buscar()" @keydown.escape="sugerencias = []" placeholder="Escribe nombre o documento…" autocomplete="off">
                                    </div>
                                    <div class="sug" x-show="sugerencias.length" x-cloak>
                                        <template x-for="c in sugerencias" :key="c.id">
                                            <button type="button" @click="elegir(c)">
                                                <span x-text="c.nombre"></span>
                                                <span class="mono muted text-[12px]" x-text="c.tipo_documento + ' ' + c.documento"></span>
                                            </button>
                                        </template>
                                    </div>
                                    <p class="muted mt-1 text-[13px]" x-show="busqueda.trim().length >= 2 && ! buscando && ! sugerencias.length" x-cloak>No está registrado. Llena sus datos abajo y se guardará al generar la remisión.</p>

                                    <div class="mt-4 grid gap-3">
                                        <div class="lbl">Comprador nuevo</div>
                                        <div class="field">
                                            <label for="c-nombre">Nombre o razón social</label>
                                            <input id="c-nombre" name="comprador[nombre]" class="inp" :class="falta('comprador') && 'con-error'" x-model="comprador.nombre" @input="quitarFaltante('comprador')" maxlength="150">
                                        </div>
                                        <div class="seg">
                                            <label><input type="radio" name="comprador[tipo_documento]" value="NIT" x-model="comprador.tipo_documento"> NIT (empresa)</label>
                                            <label><input type="radio" name="comprador[tipo_documento]" value="CC" x-model="comprador.tipo_documento"> Cédula (persona)</label>
                                        </div>
                                        <div class="f2">
                                            <div class="field">
                                                <label for="c-documento" x-text="comprador.tipo_documento === 'NIT' ? 'Número de NIT' : 'Número de cédula'"></label>
                                                <input id="c-documento" name="comprador[documento]" class="inp mono" :class="falta('documento') && 'con-error'" x-model="comprador.documento" @input="quitarFaltante('documento')" inputmode="numeric" maxlength="30">
                                            </div>
                                            <div class="field">
                                                <label for="c-ciudad">Municipio</label>
                                                <input id="c-ciudad" name="comprador[ciudad]" class="inp" :class="falta('ciudad') && 'con-error'" x-model="comprador.ciudad" @input="quitarFaltante('ciudad')" maxlength="80">
                                            </div>
                                        </div>
                                        <div class="grid gap-3" :class="falta('impuestos') && 'rounded-xl p-2 con-error'" @change="quitarFaltante('impuestos')">
                                            <x-campo-impuestos modelo="comprador" prefijo="comprador" />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </section>

                    {{-- 2. Materiales --}}
                    <section class="card">
                        <div class="pd">
                            <div class="paso"><span class="n">2</span><h2>Materiales</h2></div>

                            <template x-for="(linea, i) in lineas" :key="i">
                                <div class="ln">
                                    <div class="field">
                                        <label :for="'mat-' + i" x-text="'Material ' + (lineas.length > 1 ? (i + 1) : '')"></label>
                                        <select :id="'mat-' + i" :name="'lineas[' + i + '][material_id]'" class="inp" :class="falta('material-' + i) && 'con-error'" x-model="linea.material_id" @change="quitarFaltante('material-' + i)">
                                            <option value="">Elige…</option>
                                            <template x-for="m in disponiblesPara(i)" :key="m.id">
                                                <option :value="m.id" x-text="m.nombre" :selected="String(m.id) === String(linea.material_id)"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label :for="'cant-' + i">Cantidad m³</label>
                                        <input :id="'cant-' + i" :name="'lineas[' + i + '][cantidad_m3]'" class="inp mono" :class="(falta('cantidad-' + i) || excedeStock(linea)) && 'con-error'" type="number" min="0" step="0.5" inputmode="decimal" x-model="linea.cantidad_m3" @input="quitarFaltante('cantidad-' + i)">
                                    </div>
                                    <button type="button" class="quitar" @click="quitarLinea(i)" :aria-label="'Quitar línea ' + (i + 1)" x-show="lineas.length > 1">
                                        <x-icono nombre="cerrar" class="h-5 w-5" />
                                    </button>
                                    <div class="pie">
                                        <span>
                                            <template x-if="material(linea.material_id)">
                                                <span>
                                                    <span x-text="dinero(precio(linea)) + ' / m³'"></span> ·
                                                    <span :style="excedeStock(linea) ? 'color: var(--bad); font-weight: 600' : ''" x-text="'Patio: ' + m3(material(linea.material_id).stock_m3) + ' m³'"></span>
                                                </span>
                                            </template>
                                        </span>
                                        <b x-text="dinero(subtotal(linea))"></b>
                                    </div>
                                </div>
                            </template>

                            <button type="button" class="btn" @click="agregarLinea()" x-show="lineas.length < materiales.length">+ Agregar otro material</button>
                        </div>
                    </section>

                    {{-- 3. Despacho --}}
                    <section class="card">
                        <div class="pd">
                            <div class="paso"><span class="n">3</span><h2>Despacho</h2></div>
                            <div class="grid gap-4">
                                <div class="f2">
                                    <div class="field">
                                        <label for="conductor">Conductor</label>
                                        <input id="conductor" name="conductor" class="inp" :class="falta('conductor') && 'con-error'" x-model="conductor" @input="quitarFaltante('conductor')" maxlength="120" autocomplete="off">
                                    </div>
                                    <div class="field">
                                        <label for="placa">Placa del vehículo</label>
                                        <input id="placa" name="placa" class="inp mono uppercase" :class="falta('placa') && 'con-error'" x-model="placa" @input="quitarFaltante('placa')" maxlength="12" placeholder="ABC 123" autocomplete="off">
                                    </div>
                                </div>
                                <div class="field">
                                    <label for="maquina">Máquina que carga el material</label>
                                    <input id="maquina" name="maquina" class="inp" list="lista-maquinas" :class="falta('maquina') && 'con-error'" x-model="maquina" @input="quitarFaltante('maquina')" maxlength="80" autocomplete="off">
                                    <datalist id="lista-maquinas">
                                        @foreach ($maquinas as $m)
                                            <option value="{{ $m }}">
                                        @endforeach
                                    </datalist>
                                </div>
                                <div class="field">
                                    <label for="destino">¿Hacia dónde va el material?</label>
                                    <div class="flex gap-2">
                                        <input id="destino" name="destino" class="inp" :class="falta('destino') && 'con-error'" x-model="destino" @input="quitarFaltante('destino')" maxlength="150" placeholder="Ej. Obra vía Puerto López km 8" autocomplete="off">
                                        <button type="button" class="btn" @click="destino = 'N/A'; quitarFaltante('destino')" title="No aplica">N/A</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- 4. Pago --}}
                    <section class="card">
                        <div class="pd">
                            <div class="paso"><span class="n">4</span><h2>Forma de pago</h2></div>
                            <div class="pagos" :class="falta('forma_pago') && 'rounded-xl p-1 con-error'">
                                @foreach ($pagos as $valor => $texto)
                                    <label><input type="radio" name="forma_pago" value="{{ $valor }}" x-model="forma_pago" @change="quitarFaltante('forma_pago')"> {{ $texto }}</label>
                                @endforeach
                            </div>

                            <div class="aviso mt-3 text-[14px]" x-show="forma_pago === 'anticipo' && compradorElegido" x-cloak>
                                Anticipo disponible: <b x-text="dinero(comprador.anticipo)"></b>.
                                <template x-if="diferenciaAnticipo > 0">
                                    <span>No alcanza: faltan <b x-text="dinero(diferenciaAnticipo)"></b>.</span>
                                </template>
                                <template x-if="diferenciaAnticipo === 0 && total > 0">
                                    <span>Después de esta remisión le quedan <b x-text="dinero(comprador.anticipo - total)"></b>.</span>
                                </template>
                            </div>
                            <div class="mt-4" x-show="debeElegirPagoDiferencia" x-cloak>
                                <div class="lbl mb-2">¿Cómo paga los <span x-text="dinero(diferenciaAnticipo)"></span> que faltan?</div>
                                <div class="pagos" :class="falta('pago_diferencia') && 'rounded-xl p-1 con-error'">
                                    <label><input type="radio" name="pago_diferencia" value="efectivo" x-model="pago_diferencia" :disabled="! debeElegirPagoDiferencia" @change="quitarFaltante('pago_diferencia')"> Efectivo</label>
                                    <label><input type="radio" name="pago_diferencia" value="transferencia" x-model="pago_diferencia" :disabled="! debeElegirPagoDiferencia" @change="quitarFaltante('pago_diferencia')"> Transferencia</label>
                                    <label><input type="radio" name="pago_diferencia" value="credito" x-model="pago_diferencia" :disabled="! debeElegirPagoDiferencia" @change="quitarFaltante('pago_diferencia')"> Queda debiendo</label>
                                </div>
                                <p class="muted mt-2 text-[13.5px]" x-show="pago_diferencia === 'credito'">La diferencia pasa a <b>Cartera</b> y el comprador queda como deudor.</p>
                                <p class="muted mt-2 text-[13.5px]" x-show="pago_diferencia === 'efectivo' || pago_diferencia === 'transferencia'">El comprador paga la diferencia ahora y <b>no queda debiendo</b>.</p>
                            </div>
                            <div class="aviso mt-3 text-[14px]" x-show="forma_pago === 'credito'" x-cloak>
                                Esta remisión queda como <b>deuda</b> del comprador<span x-show="comprador.deuda > 0">, que ya debe <b x-text="dinero(comprador.deuda)"></b></span>.
                            </div>
                            <div class="aviso mt-3 text-[14px]" x-show="forma_pago === 'donacion'" x-cloak>
                                Las donaciones se registran pero <b>no suman como venta</b> en los reportes.
                            </div>

                            <div class="field mt-4">
                                <label for="observaciones">Observaciones (opcional)</label>
                                <textarea id="observaciones" name="observaciones" class="inp" rows="3" x-model="observaciones" maxlength="255" placeholder="Novedades con el cliente o el despacho. Ej. El cliente pidió entregar en la portería."></textarea>
                                <p class="muted text-[13px]">Sale impresa antes de las firmas. Si se deja vacía, queda el espacio para escribir a mano.</p>
                            </div>

                            <div class="mt-4 flex items-center gap-3 text-[14px]">
                                <span class="av">{{ $usuario->iniciales() }}</span>
                                <span>Despacha: <b style="color: var(--navy)">{{ $usuario->name }}</b></span>
                            </div>
                        </div>
                    </section>

                    {{-- Vista previa en celular --}}
                    <div class="previa-movil">
                        <button type="button" class="btn w-full" @click="verPrevia = ! verPrevia" x-text="verPrevia ? 'Ocultar vista previa' : 'Ver cómo queda la remisión'"></button>
                        <div class="mt-3" x-show="verPrevia" x-cloak>
                            @include('remisiones.partials.previa', ['numero' => $siguienteNumero])
                        </div>
                    </div>

                    <div class="bar-total">
                        <div>
                            <small x-text="retefuente + reteica > 0 ? 'Total a pagar (con retenciones)' : 'Total'">Total</small>
                            <b x-text="dinero(total)">$ 0</b>
                        </div>
                        <button type="submit" class="btn acento" :disabled="enviando" @disabled($materiales->isEmpty())>
                            <span x-text="enviando ? 'Generando…' : 'Generar remisión'">Generar remisión</span>
                        </button>
                    </div>
                </div>

                {{-- Vista previa en escritorio --}}
                <div class="previa-escritorio sticky top-5">
                    <div class="mono muted mb-2 text-[11px] tracking-[0.14em]">VISTA PREVIA DE LA REMISIÓN</div>
                    @include('remisiones.partials.previa', ['numero' => $siguienteNumero])
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
