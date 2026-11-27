@use('App\Support\Formato')
@use('App\Support\FiltroRemisiones')
@use('App\Enums\FormaPago')
@php
    $esAdmin = auth()->user()->isAdmin();
    $params = $filtro->parametros();
@endphp

<x-app-layout>
    <x-slot name="titulo">Remisiones</x-slot>

    <div x-data="exportarRemisiones">
        <x-encabezado ceja="Historial" :subtitulo="($esAdmin ? 'Todas las remisiones emitidas por la cantera.' : 'Las remisiones que tú has emitido.').' Toca una para verla, reimprimirla o descargarla.'">
            Remisiones <em>emitidas</em>
            <x-slot name="acciones">
                <button type="button" class="btn" @click="abrir('excel')">Exportar Excel</button>
                <button type="button" class="btn" @click="abrir('pdf')">Exportar PDF</button>
                <a class="btn acento" href="{{ route('remisiones.create') }}">+ Nueva remisión</a>
            </x-slot>
        </x-encabezado>

        <section class="card mb-5" x-data="{ filtros: {{ $filtro->materialId || $filtro->vendedorId || $filtro->formaPago || $filtro->incluirAnuladas ? 'true' : 'false' }} }">
            <div class="pd grid gap-4">
                <div class="pills" data-vivo="atajos">
                    @foreach (FiltroRemisiones::PRESETS as $clave => $texto)
                        <a @class(['pill', 'on' => $filtro->preset === $clave]) href="{{ route('remisiones.index', [...\Illuminate\Support\Arr::except($params, ['desde', 'hasta', 'rango']), 'rango' => $clave]) }}">{{ $texto }}</a>
                    @endforeach
                </div>

                <form id="filtro-remisiones" method="GET" action="{{ route('remisiones.index') }}" class="grid gap-3" x-data="filtroEnVivo" role="search">
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div class="field">
                            <label for="desde">Desde</label>
                            <input id="desde" type="date" name="desde" class="inp" value="{{ $filtro->desde->toDateString() }}">
                        </div>
                        <div class="field">
                            <label for="hasta">Hasta</label>
                            <input id="hasta" type="date" name="hasta" class="inp" value="{{ $filtro->hasta->toDateString() }}">
                        </div>
                        <div class="field col-span-2">
                            <label for="q">Buscar</label>
                            <input id="q" type="search" name="q" class="inp" value="{{ $filtro->busqueda }}" placeholder="Escribe un N° de remisión, comprador o placa" autocomplete="off">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4" x-show="filtros" x-cloak>
                        <div class="field">
                            <label for="material">Material</label>
                            <select id="material" name="material" class="inp">
                                <option value="">Todos</option>
                                @foreach ($materiales as $m)
                                    <option value="{{ $m->id }}" @selected($filtro->materialId === $m->id)>{{ $m->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="pago">Forma de pago</label>
                            <select id="pago" name="pago" class="inp">
                                <option value="">Todas</option>
                                @foreach (FormaPago::cases() as $forma)
                                    <option value="{{ $forma->value }}" @selected($filtro->formaPago === $forma)>{{ $forma->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($esAdmin)
                            <div class="field">
                                <label for="vendedor">Vendedor</label>
                                <select id="vendedor" name="vendedor" class="inp">
                                    <option value="">Todos</option>
                                    @foreach ($vendedores as $v)
                                        <option value="{{ $v->id }}" @selected($filtro->vendedorId === $v->id)>{{ $v->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <label class="flex cursor-pointer items-center gap-2 self-end pb-2 text-sm">
                            <input type="checkbox" name="anuladas" value="1" class="rounded border-linea-2 text-navy focus:ring-navy" @checked($filtro->incluirAnuladas)>
                            Mostrar también las anuladas
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="btn sm" @click="filtros = ! filtros" x-text="filtros ? 'Menos filtros' : 'Más filtros (material, pago{{ $esAdmin ? ', vendedor' : '' }})'"></button>
                        <a href="{{ route('remisiones.index') }}" class="btn sm">Limpiar filtros</a>
                        <span class="muted text-[13px]" x-show="cargando" x-cloak>Buscando…</span>
                    </div>
                </form>
            </div>
        </section>

        <x-modal name="exportar" maxWidth="lg" focusable>
            <form method="GET" :action="formato === 'excel' ? @js(route('remisiones.excel')) : @js(route('remisiones.listado-pdf'))" @submit="enviar($event)">
                <div class="md-hd">
                    <div class="ey">Exportar remisiones</div>
                    <h2>¿Qué fechas quieres descargar?</h2>
                </div>
                <div class="md-bd">
                    <div class="alerta-grande" role="alert" x-show="intentado && ! valido()" x-cloak>
                        <h3>Revisa las fechas</h3>
                        <p class="text-base" x-text="! desde || ! hasta ? 'Escoge la fecha desde y la fecha hasta.' : 'La fecha desde no puede ser después de la fecha hasta.'"></p>
                    </div>

                    <div class="field">
                        <span class="lbl">Formato</span>
                        <div class="seg">
                            <label><input type="radio" value="excel" x-model="formato"> Excel</label>
                            <label><input type="radio" value="pdf" x-model="formato"> PDF</label>
                        </div>
                    </div>

                    <div class="field">
                        <span class="lbl">Periodo</span>
                        <div class="pills">
                            @foreach (FiltroRemisiones::PRESETS as $clave => $texto)
                                <button type="button" class="pill" :class="rango === '{{ $clave }}' && 'on'" @click="usarRango('{{ $clave }}')">{{ $texto }}</button>
                            @endforeach
                        </div>
                    </div>

                    <div class="f2">
                        <div class="field">
                            <label for="exp-desde">Desde</label>
                            <input id="exp-desde" type="date" name="desde" class="inp" x-model="desde" @input="rango = null" :max="hoy">
                        </div>
                        <div class="field">
                            <label for="exp-hasta">Hasta</label>
                            <input id="exp-hasta" type="date" name="hasta" class="inp" x-model="hasta" @input="rango = null" :max="hoy">
                        </div>
                    </div>

                    <template x-if="otros.length">
                        <label class="flex cursor-pointer items-start gap-2 text-[14px]">
                            <input type="checkbox" x-model="conFiltros" class="mt-1 rounded border-linea-2 text-navy focus:ring-navy">
                            <span>Aplicar también los filtros de la lista: <b x-text="otros.map((o) => o.texto).join(' · ')"></b></span>
                        </label>
                    </template>
                    <template x-for="o in (conFiltros ? otros : [])" :key="o.nombre">
                        <input type="hidden" :name="o.nombre" :value="o.valor">
                    </template>

                    <p class="rounded-lg p-3 text-[14px]" style="background: #e6e9f1; color: var(--navy)" x-show="valido()">
                        Vas a descargar en <b x-text="formato === 'excel' ? 'Excel' : 'PDF'"></b> las remisiones <b x-text="resumen()"></b><span x-show="conFiltros && otros.length">, con los filtros marcados</span>.
                    </p>
                </div>
                <div class="md-ft">
                    <button type="button" class="btn" @click="$dispatch('close-modal', 'exportar')">Cancelar</button>
                    <button type="submit" class="btn pri" x-text="formato === 'excel' ? 'Descargar Excel' : 'Descargar PDF'"></button>
                </div>
            </form>
        </x-modal>

        <div class="g g4 mb-5" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr))" data-vivo="totales">
            <x-kpi etiqueta="Remisiones" :valor="Formato::numero($totales['remisiones'])" color="var(--navy)">{{ $filtro->descripcion() }}</x-kpi>
            <x-kpi etiqueta="Material despachado" color="var(--brown)">
                <x-slot name="valor">{{ Formato::m3($totales['m3']) }} <small>m³</small></x-slot>
                Incluye donaciones
            </x-kpi>
            <x-kpi etiqueta="Valor vendido" :valor="Formato::dinero($totales['valor'])" color="var(--orange)">Sin donaciones ni anuladas</x-kpi>
        </div>

        <section class="card" data-vivo="tabla">
            <div class="tw">
                <table class="t t-movil">
                    <thead>
                        <tr>
                            <th>Remisión</th>
                            <th>Fecha</th>
                            <th>Comprador</th>
                            <th>Materiales</th>
                            @if ($esAdmin)<th>Vendedor</th>@endif
                            <th>Pago</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($remisiones as $remision)
                            @php($url = route('remisiones.show', $remision))
                            <tr class="lk" onclick="window.location='{{ $url }}'" @style(['opacity: .55' => $remision->estaAnulada()])>
                                <td class="mono" data-label="Remisión">
                                    <a href="{{ $url }}" class="font-medium" style="color: var(--navy)">{{ $remision->numeroFormateado() }}</a>
                                    @if ($remision->estaAnulada())<span class="tag c ml-1">Anulada</span>@endif
                                </td>
                                <td class="whitespace-nowrap" data-label="Fecha">{{ Formato::fechaCorta($remision->emitida_en) }} <span class="mono muted text-[12px]">{{ $remision->emitida_en->format('H:i') }}</span></td>
                                <td class="principal">{{ $remision->comprador->nombre }}</td>
                                <td data-label="Materiales">
                                    <span class="inline-flex flex-wrap justify-end gap-1 md:justify-start">
                                        @foreach ($remision->lineas as $linea)
                                            <span class="tag g">{{ $linea->material->nombre }} · {{ Formato::m3($linea->cantidad_m3) }} m³</span>
                                        @endforeach
                                    </span>
                                </td>
                                @if ($esAdmin)<td data-label="Vendedor">{{ explode(' ', $remision->vendedor->name)[0] }}</td>@endif
                                <td data-label="Pago"><x-tag-pago :forma="$remision->forma_pago" /></td>
                                <td class="num" data-label="Total"><b>{{ Formato::dinero($remision->total) }}</b></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty">No hay remisiones con esos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($remisiones->hasPages())
                <div class="px-[22px] py-4">{{ $remisiones->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
