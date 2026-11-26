@use('App\Support\Formato')
@php
    $esAdmin = auth()->user()->can('admin');
    $vacio = ['id' => null, 'nombre' => '', 'precio_m3' => '', 'stock_minimo_m3' => '', 'color' => '#955515', 'activo' => true];
    $editandoId = old('_editando');
    $formulario = $errors->material->any()
        ? ['id' => $editandoId ? (int) $editandoId : null, 'nombre' => old('nombre'), 'precio_m3' => old('precio_m3'), 'stock_m3' => old('stock_m3'), 'stock_minimo_m3' => old('stock_minimo_m3'), 'color' => old('color', '#955515'), 'activo' => (bool) old('activo', true)]
        : $vacio;
    $ajuste = ['id' => old('_material') ? (int) old('_material') : null, 'nombre' => old('_nombre', ''), 'tipo' => old('tipo', 'entrada'), 'cantidad_m3' => old('cantidad_m3', ''), 'nota' => old('nota', '')];
@endphp

<x-app-layout>
    <x-slot name="titulo">Materiales</x-slot>

    <div x-data="{
            form: @js($formulario),
            ajuste: @js($ajuste),
            nuevo() { this.form = @js($vacio); this.$dispatch('open-modal', 'material') },
            editar(m) { this.form = { ...m }; this.$dispatch('open-modal', 'material') },
            stock(m) { this.ajuste = { id: m.id, nombre: m.nombre, tipo: 'entrada', cantidad_m3: '', nota: '' }; this.$dispatch('open-modal', 'stock') },
        }">
        <x-encabezado ceja="Inventario"
                      :subtitulo="'Precio por metro cúbico y existencias en patio.'.($esAdmin ? ' Un cambio de precio se aplica a las remisiones nuevas; las ya emitidas conservan su precio.' : ' Los productos sin existencias no se pueden vender.')">
            Materiales <em>y productos</em>
            @if ($esAdmin)
                <x-slot name="acciones">
                    <button type="button" class="btn pri" @click="nuevo()">+ Nuevo material</button>
                </x-slot>
            @endif
        </x-encabezado>

        <div class="mgrid">
            @forelse ($materiales as $material)
                @php
                    $stock = (float) $material->stock_m3;
                    $minimo = max(1, (float) $material->stock_minimo_m3);
                    $porcentaje = min(100, $stock / ($minimo * 2) * 100);
                    $estado = $stock <= 0 ? 'agotado' : ($material->stockBajo() ? 'bajo' : '');
                    $datos = ['id' => $material->id, 'nombre' => $material->nombre, 'precio_m3' => $material->precio_m3, 'stock_minimo_m3' => (float) $material->stock_minimo_m3, 'color' => $material->color, 'activo' => $material->activo];
                @endphp
                <article @class(['mc', 'inactivo' => ! $material->activo])>
                    <div class="img" style="--c: {{ $material->color }}">
                        @if (! $material->activo)
                            <span class="tag g">Inactivo</span>
                        @elseif ($estado === 'agotado')
                            <span class="tag c">Sin existencias</span>
                        @elseif ($estado === 'bajo')
                            <span class="tag o">Stock bajo</span>
                        @endif
                    </div>
                    <div class="bd">
                        <h3>{{ $material->nombre }}</h3>
                        <div class="pr">{{ Formato::dinero($material->precio_m3) }} <small>/ m³</small></div>
                        <div class="bar" title="Mínimo: {{ Formato::m3($material->stock_minimo_m3) }} m³"><i @class([$estado]) style="width: {{ $porcentaje }}%"></i></div>
                        <div class="mt">
                            <span>Patio: <b @style(['color: var(--bad)' => $estado !== ''])>{{ Formato::m3($stock) }} m³</b></span>
                            <span>{{ Formato::m3($vendidoMes[$material->id] ?? 0) }} m³ vendidos/mes</span>
                        </div>
                        @if ($esAdmin)
                            <div class="acc">
                                <button type="button" class="btn sm" @click="editar(@js($datos))">Editar</button>
                                <button type="button" class="btn sm" @click="stock(@js($datos))">Ajustar stock</button>
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <section class="card col-span-full"><div class="empty">Aún no hay materiales registrados.</div></section>
            @endforelse
        </div>

        @if ($esAdmin)
            {{-- Crear / editar material --}}
            <x-modal name="material" :show="$errors->material->any()" maxWidth="lg" focusable>
                <form method="POST" :action="form.id ? @js(url('/materiales')) + '/' + form.id : @js(route('materiales.store'))">
                    @csrf
                    <input type="hidden" name="_method" value="PATCH" :disabled="! form.id">
                    <input type="hidden" name="_editando" :value="form.id">
                    <div class="md-hd">
                        <div class="ey" x-text="form.id ? 'Editar material' : 'Nuevo material'"></div>
                        <h2 x-text="form.id ? form.nombre : 'Agregar producto al inventario'"></h2>
                    </div>
                    <div class="md-bd">
                        <x-alerta-grande titulo="Revisa estos datos" :errores="$errors->material->all()" />
                        <div class="field">
                            <label for="m-nombre">Nombre del producto</label>
                            <input id="m-nombre" name="nombre" class="inp" x-model="form.nombre" required maxlength="100" placeholder="Ej. Recebo morado">
                        </div>
                        <div class="f2">
                            <div class="field">
                                <label for="m-precio">Precio por m³ (pesos)</label>
                                <input id="m-precio" name="precio_m3" class="inp mono" x-model="form.precio_m3" inputmode="numeric" required placeholder="70000">
                            </div>
                            <div class="field">
                                <label for="m-minimo">Stock mínimo (m³)</label>
                                <input id="m-minimo" name="stock_minimo_m3" class="inp mono" type="number" min="0" step="0.01" x-model="form.stock_minimo_m3" required>
                            </div>
                        </div>
                        <div class="f2">
                            <div class="field" x-show="! form.id">
                                <label for="m-stock">Stock inicial en patio (m³)</label>
                                <input id="m-stock" name="stock_m3" class="inp mono" type="number" min="0" step="0.01" x-model="form.stock_m3" :disabled="!! form.id" :required="! form.id">
                            </div>
                            <div class="field">
                                <label for="m-color">Color en pantalla</label>
                                <input id="m-color" name="color" type="color" class="inp h-[44px] p-1" x-model="form.color">
                            </div>
                        </div>
                        <label class="flex cursor-pointer items-center gap-2 text-sm" x-show="form.id">
                            <input type="hidden" name="activo" value="0">
                            <input type="checkbox" name="activo" value="1" class="rounded border-linea-2 text-navy focus:ring-navy" x-model="form.activo">
                            Producto activo (si se desactiva, no aparece para vender)
                        </label>
                    </div>
                    <div class="md-ft">
                        <button type="button" class="btn" @click="$dispatch('close-modal', 'material')">Cancelar</button>
                        <button type="submit" class="btn pri">Guardar</button>
                    </div>
                </form>
            </x-modal>

            {{-- Ajuste de stock --}}
            <x-modal name="stock" :show="$errors->stock->any()" maxWidth="lg" focusable>
                <form method="POST" :action="@js(url('/materiales')) + '/' + ajuste.id + '/stock'">
                    @csrf
                    <input type="hidden" name="_material" :value="ajuste.id">
                    <input type="hidden" name="_nombre" :value="ajuste.nombre">
                    <div class="md-hd">
                        <div class="ey" x-text="ajuste.nombre"></div>
                        <h2>Ajustar stock en patio</h2>
                    </div>
                    <div class="md-bd">
                        <x-alerta-grande titulo="Revisa estos datos" :errores="$errors->stock->all()" />
                        <div class="field">
                            <span class="lbl">¿Qué vas a registrar?</span>
                            <div class="seg">
                                <label><input type="radio" name="tipo" value="entrada" x-model="ajuste.tipo"> Entrada al patio (suma)</label>
                                <label><input type="radio" name="tipo" value="conteo" x-model="ajuste.tipo"> Conteo físico (reemplaza)</label>
                            </div>
                        </div>
                        <div class="field">
                            <label for="s-cantidad" x-text="ajuste.tipo === 'entrada' ? 'Metros cúbicos que entran' : 'Metros cúbicos que hay en patio'"></label>
                            <input id="s-cantidad" name="cantidad_m3" class="inp mono" type="number" min="0" step="0.01" x-model="ajuste.cantidad_m3" required>
                        </div>
                        <div class="field">
                            <label for="s-nota">Nota (opcional)</label>
                            <input id="s-nota" name="nota" class="inp" x-model="ajuste.nota" maxlength="255" placeholder="Ej. Producción de la semana">
                        </div>
                    </div>
                    <div class="md-ft">
                        <button type="button" class="btn" @click="$dispatch('close-modal', 'stock')">Cancelar</button>
                        <button type="submit" class="btn pri">Guardar stock</button>
                    </div>
                </form>
            </x-modal>
        @endif
    </div>
</x-app-layout>
