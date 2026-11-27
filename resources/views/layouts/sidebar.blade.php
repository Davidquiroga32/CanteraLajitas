@php
    $menu = [
        ['dashboard', 'dashboard', 'Inicio', 'inicio', false],
        ['remisiones.index', 'remisiones.index', 'Remisiones', 'lista', false],
        ['compradores.index', 'compradores.*', 'Compradores', 'usuarios', false],
        ['materiales.index', 'materiales.*', 'Materiales', 'capas', false],
        ['cartera.index', 'cartera.*', 'Cartera', 'cartera', true],
        ['equipo.index', 'equipo.*', 'Equipo y vendedores', 'carnet', true],
        ['reportes.index', 'reportes.*', 'Reportes', 'grafico', true],
        ['auditoria.index', 'auditoria.*', 'Auditoría', 'escudo', true],
    ];
    $usuario = auth()->user();
@endphp

<aside class="sb" :class="{ 'open': menuAbierto }">
    <a class="logo" href="{{ route('dashboard') }}" aria-label="Cantera Lajitas — Inicio">
        <img src="{{ asset('images/logo-cantera-lajitas.png') }}" alt="Cantera Lajitas S.A.S." width="128" height="120">
    </a>

    <a class="cta" href="{{ route('remisiones.create') }}">
        <x-icono nombre="mas" class="w-5 h-5" stroke-width="1.9" />
        Nueva remisión
    </a>

    <h6>Gestión</h6>
    <nav>
        @foreach ($menu as [$ruta, $patron, $texto, $icono, $soloAdmin])
            @if (! $soloAdmin || $usuario->can('admin'))
                <a @class(['nv', 'on' => request()->routeIs($patron)]) href="{{ route($ruta) }}" @if (request()->routeIs($patron)) aria-current="page" @endif>
                    <x-icono :nombre="$icono" />
                    {{ $texto }}
                </a>
            @endif
        @endforeach
    </nav>

    <div class="me">
        <div class="row">
            <div class="av">{{ $usuario->iniciales() }}</div>
            <div>
                <b>{{ $usuario->name }}</b>
                <small>{{ $usuario->role->label() }}</small>
            </div>
        </div>
        <div class="acc">
            <a href="{{ route('profile.edit') }}">Mi perfil</a>
            <form method="POST" action="{{ route('logout') }}" style="display:contents">
                @csrf
                <button type="submit">Cerrar sesión</button>
            </form>
        </div>
    </div>
</aside>
