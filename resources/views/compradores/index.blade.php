@use('App\Support\Formato')
@use('App\Enums\TipoDocumento')
@php
    $esAdmin = auth()->user()->can('admin');
    $vacio = ['id' => null, 'nombre' => '', 'tipo_documento' => 'NIT', 'documento' => '', 'ciudad' => 'Villavicencio'];
    $formulario = $errors->comprador->any()
        ? ['id' => old('_editando') ? (int) old('_editando') : null, 'nombre' => old('nombre'), 'tipo_documento' => old('tipo_documento', 'NIT'), 'documento' => old('documento'), 'ciudad' => old('ciudad')]
        : $vacio;
    $urlLista = route('compradores.index', request()->except('ver'));
@endphp

<x-app-layout>
    <x-slot name="titulo">Compradores</x-slot>

    <div x-data="{
            form: @js($formulario),
            nuevo() { this.form = @js($vacio); this.$dispatch('open-modal', 'comprador') },
            editar(c) { this.form = { ...c }; this.$dispatch('close-panel'); this.$dispatch('open-modal', 'comprador') },
        }">
        <x-encabezado ceja="Directorio" subtitulo="Empresas y personas a las que se les ha vendido. Sus datos se reutilizan al hacer una remisión.">
            Compradores
            <x-slot name="acciones">
                <button type="button" class="btn pri" @click="nuevo()">+ Nuevo comprador</button>
            </x-slot>
        </x-encabezado>

        <section class="card">
            <form method="GET" action="{{ route('compradores.index') }}" class="flex flex-wrap items-end gap-3 border-b px-[22px] py-[18px]" style="border-color: var(--line)">
                <div class="field min-w-[240px] flex-1">
                    <label for="q">Buscar</label>
                    <input id="q" name="q" class="inp" value="{{ $busqueda }}" placeholder="Nombre, documento o ciudad…" autocomplete="off">
                </div>
                <button type="submit" class="btn pri">Buscar</button>
                @if ($busqueda !== '')
                    <a href="{{ route('compradores.index') }}" class="btn">Limpiar</a>
                @endif
            </form>

            <div class="tw">
                <table class="t t-movil">
                    <thead>
                        <tr>
                            <th>Comprador</th>
                            <th>Documento</th>
                            <th>Ciudad</th>
                            <th class="num">Compras</th>
                            <th class="num">Total comprado</th>
                            <th class="num">Cartera</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($compradores as $comprador)
                            @php($url = route('compradores.index', [...request()->except('ver'), 'ver' => $comprador->id]))
                            <tr class="lk" onclick="window.location='{{ $url }}'">
                                <td class="principal">
                                    <a href="{{ $url }}" class="font-medium" style="color: var(--navy)">{{ $comprador->nombre }}</a>
                                    <div><span @class(['tag mt-1', 'g' => $comprador->tipo_documento === TipoDocumento::CC])>{{ $comprador->tipo_documento->tipoComprador() }}</span></div>
                                </td>
                                <td class="mono whitespace-nowrap text-[13px]" data-label="Documento">{{ $comprador->documentoCompleto() }}</td>
                                <td data-label="Ciudad">{{ $comprador->ciudad ?? '—' }}</td>
                                <td class="num" data-label="Compras">{{ $comprador->compras }}</td>
                                <td class="num" data-label="Total comprado">{{ Formato::dinero($comprador->total_comprado) }}</td>
                                <td class="num" data-label="Cartera">
                                    @if ((int) $comprador->saldo_credito > 0)
                                        <span class="tag c">Debe {{ Formato::dinero($comprador->saldo_credito) }}</span>
                                    @elseif ((int) $comprador->saldo_anticipo > 0)
                                        <span class="tag v">A favor {{ Formato::dinero($comprador->saldo_anticipo) }}</span>
                                    @else
                                        <span class="muted">Al día</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty">
                                    {{ $busqueda !== '' ? 'No se encontraron compradores con "'.$busqueda.'".' : 'Aún no hay compradores registrados.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($compradores->hasPages())
                <div class="px-[22px] py-4">{{ $compradores->links() }}</div>
            @endif
        </section>

        {{-- Panel con el historial del comprador --}}
        @if ($seleccionado)
            <x-panel-lateral nombre="comprador" abierto :al-cerrar="$urlLista"
                             :ceja="$seleccionado->tipo_documento->tipoComprador()"
                             :titulo="$seleccionado->nombre"
                             :detalle="$seleccionado->documentoCompleto().' · '.($seleccionado->ciudad ?? '—')">
                <div class="mb-5 grid grid-cols-2 gap-4">
                    <div>
                        <small class="muted">Compras realizadas</small>
                        <div class="cifra">{{ $seleccionado->compras }}</div>
                    </div>
                    <div>
                        <small class="muted">Total comprado</small>
                        <div class="cifra">{{ Formato::dinero($seleccionado->total_comprado) }}</div>
                    </div>
                    <div class="rounded-lg p-3" style="background: var(--badl)">
                        <small style="color: #7d3720">Debe</small>
                        <div class="mono text-lg font-medium" style="color: #7d3720">{{ Formato::dinero($seleccionado->saldo_credito) }}</div>
                    </div>
                    <div class="rounded-lg p-3" style="background: var(--okl)">
                        <small style="color: #2e4a2a">Anticipo a favor</small>
                        <div class="mono text-lg font-medium" style="color: #2e4a2a">{{ Formato::dinero($seleccionado->saldo_anticipo) }}</div>
                    </div>
                </div>

                <div class="mb-6 flex flex-wrap gap-2">
                    <a class="btn acento" href="{{ route('remisiones.create', ['comprador' => $seleccionado->id]) }}">Vender a este comprador →</a>
                    @if ($esAdmin)
                        <button type="button" class="btn" @click="editar(@js(['id' => $seleccionado->id, 'nombre' => $seleccionado->nombre, 'tipo_documento' => $seleccionado->tipo_documento->value, 'documento' => $seleccionado->documento, 'ciudad' => $seleccionado->ciudad]))">Editar datos</button>
                    @endif
                </div>

                <h2 class="mb-2" style="font-size: 1.1rem">Historial de compras</h2>
                @forelse ($seleccionado->historial as $remision)
                    <div class="flex items-start justify-between gap-3 border-b py-3" style="border-color: var(--line)">
                        <div class="min-w-0">
                            <span class="mono font-medium" style="color: var(--navy)">{{ $remision->numeroFormateado() }}</span>
                            <x-tag-pago :forma="$remision->forma_pago" class="ml-1" />
                            <div class="muted text-[12.5px]">
                                {{ Formato::fecha($remision->emitida_en) }} ·
                                {{ $remision->lineas->map(fn ($l) => $l->material->nombre.' '.Formato::m3($l->cantidad_m3).' m³')->implode(', ') }}
                            </div>
                        </div>
                        <b class="mono whitespace-nowrap text-[13px]">{{ Formato::dinero($remision->total) }}</b>
                    </div>
                @empty
                    <p class="muted">Sin compras todavía.</p>
                @endforelse
            </x-panel-lateral>
        @endif

        {{-- Crear / editar comprador --}}
        <x-modal name="comprador" :show="$errors->comprador->any()" maxWidth="lg" focusable>
            <form method="POST" :action="form.id ? @js(url('/compradores')) + '/' + form.id : @js(route('compradores.store'))">
                @csrf
                <input type="hidden" name="_method" value="PATCH" :disabled="! form.id">
                <input type="hidden" name="_editando" :value="form.id">
                <div class="md-hd">
                    <div class="ey" x-text="form.id ? 'Editar comprador' : 'Nuevo registro'"></div>
                    <h2 x-text="form.id ? form.nombre : 'Crear comprador'"></h2>
                </div>
                <div class="md-bd">
                    <x-alerta-grande titulo="Revisa estos datos" :errores="$errors->comprador->all()" />
                    <div class="field">
                        <label for="c-nombre">Nombre o razón social</label>
                        <input id="c-nombre" name="nombre" class="inp" x-model="form.nombre" required maxlength="150">
                    </div>
                    <div class="field">
                        <span class="lbl">Tipo de documento</span>
                        <div class="seg">
                            <label><input type="radio" name="tipo_documento" value="NIT" x-model="form.tipo_documento"> NIT (empresa)</label>
                            <label><input type="radio" name="tipo_documento" value="CC" x-model="form.tipo_documento"> Cédula (persona)</label>
                        </div>
                    </div>
                    <div class="f2">
                        <div class="field">
                            <label for="c-documento" x-text="form.tipo_documento === 'NIT' ? 'Número de NIT' : 'Número de cédula'"></label>
                            <input id="c-documento" name="documento" class="inp mono" x-model="form.documento" required maxlength="30" inputmode="numeric" :placeholder="form.tipo_documento === 'NIT' ? '900.412.118-3' : '17.345.220'">
                        </div>
                        <div class="field">
                            <label for="c-ciudad">Ciudad</label>
                            <input id="c-ciudad" name="ciudad" class="inp" x-model="form.ciudad" required maxlength="80">
                        </div>
                    </div>
                </div>
                <div class="md-ft">
                    <button type="button" class="btn" @click="$dispatch('close-modal', 'comprador')">Cancelar</button>
                    <button type="submit" class="btn pri">Guardar comprador</button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
