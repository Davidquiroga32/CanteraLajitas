@use('App\Support\Formato')
@use('App\Support\TextoAuditoria')
@php
    $filtros = ['usuario' => $usuarioId, 'tipo' => $categoria];
    $parametros = array_filter([...$rango->parametros(), ...$filtros]);
@endphp

<x-app-layout>
    <x-slot name="titulo">Auditoría</x-slot>

    <x-encabezado ceja="Seguridad" subtitulo="Todo lo que se hace en el sistema queda anotado aquí: quién entró, qué remisiones emitió o anuló, cambios de precios, abonos y qué archivos descargó. Nadie puede editar ni borrar estos registros.">
        <em>Auditoría</em>
        <x-slot name="acciones">
            <a class="btn" href="{{ route('auditoria.excel', $parametros) }}">Exportar Excel</a>
        </x-slot>
    </x-encabezado>

    @include('reportes.partials.rango', ['accion' => route('auditoria.index'), 'extra' => $filtros])

    <section class="card mb-5">
        <form method="GET" action="{{ route('auditoria.index') }}" class="pd grid grid-cols-1 gap-3 sm:grid-cols-2">
            @foreach ($rango->parametros() as $nombre => $valor)
                <input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">
            @endforeach
            <div class="field">
                <label for="usuario">Usuario</label>
                <select id="usuario" name="usuario" class="inp" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach ($usuarios as $u)
                        <option value="{{ $u->id }}" @selected($usuarioId === $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="tipo">Tipo de acción</label>
                <select id="tipo" name="tipo" class="inp" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach (TextoAuditoria::CATEGORIAS as $clave => $texto)
                        <option value="{{ $clave }}" @selected($categoria === $clave)>{{ $texto }}</option>
                    @endforeach
                </select>
            </div>
            <noscript><button type="submit" class="btn pri">Filtrar</button></noscript>
        </form>
    </section>

    <section class="card">
        <div class="hd">
            <h2>Registros</h2>
            <span class="muted text-xs">{{ Formato::numero($registros->total()) }} en el periodo</span>
        </div>
        <div class="tw">
            <table class="t t-movil">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Qué hizo</th>
                        <th>Tipo</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $registro)
                        @php($enlace = TextoAuditoria::enlace($registro))
                        <tr>
                            <td class="whitespace-nowrap" data-label="Fecha">{{ Formato::fechaCorta($registro->created_at) }} <span class="mono muted text-[12px]">{{ $registro->created_at->format('H:i') }}</span></td>
                            <td data-label="Usuario">{{ $registro->user->name ?? 'Sin sesión' }}</td>
                            <td class="principal">
                                @if ($enlace)
                                    <a href="{{ $enlace }}" style="color: var(--navy)">{{ TextoAuditoria::describir($registro) }}</a>
                                @else
                                    {{ TextoAuditoria::describir($registro) }}
                                @endif
                            </td>
                            <td data-label="Tipo"><span @class(['tag', 'c' => in_array($registro->accion, ['sesion.fallido', 'sesion.bloqueo', 'remision.anulada'], true), 'g' => ! in_array($registro->accion, ['sesion.fallido', 'sesion.bloqueo', 'remision.anulada'], true)])>{{ TextoAuditoria::categoria($registro) }}</span></td>
                            <td class="mono muted text-[12px]" data-label="IP">{{ $registro->ip ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No hay registros con esos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($registros->hasPages())
            <div class="px-[22px] py-4">{{ $registros->links() }}</div>
        @endif
    </section>
</x-app-layout>
