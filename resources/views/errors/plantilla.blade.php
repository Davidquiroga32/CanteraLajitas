{{-- Página de error en español. Variables: $codigo, $titulo, $mensaje, $volver (opcional: true = regresar a la página anterior). --}}
@php
    $anterior = url()->previous();
    $regresar = ($volver ?? false) && $anterior !== url()->current();
@endphp
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $titulo }} · Cantera Lajitas</title>
        <link rel="icon" type="image/png" href="{{ asset('images/favicon-64.png') }}">
        @include('layouts.partials.fuentes')
        @vite(['resources/css/app.css'])
    </head>
    <body>
        <main class="grid min-h-screen place-items-center px-4 py-8">
            <section class="card w-full max-w-md">
                <div class="pd grid gap-4">
                    <img src="{{ asset('images/logo-cantera-lajitas.png') }}" alt="Cantera Lajitas S.A.S." class="h-auto w-20" width="80" height="75">
                    <div>
                        <div class="ey">Error {{ $codigo }}</div>
                        <h1 class="text-[1.7rem] leading-tight" style="font-family: var(--serif); color: var(--navy)">{{ $titulo }}</h1>
                    </div>
                    <p class="text-[15px]" style="color: #4f4a3e">{{ $mensaje }}</p>

                    @if ($regresar)
                        <a href="{{ $anterior }}" class="btn pri grande w-full justify-center">Volver a la página anterior</a>
                    @endif
                    {{-- Sin sesión, el inicio lleva solo a la pantalla de ingreso. --}}
                    <a href="{{ route('dashboard') }}" @class(['btn grande w-full justify-center', 'pri' => ! $regresar])>Ir al inicio</a>
                </div>
            </section>
        </main>
    </body>
</html>
