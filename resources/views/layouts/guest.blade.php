<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Cantera Lajitas') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/favicon-64.png') }}">

        @include('layouts.partials.fuentes')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="lg">
            <div class="box">
                <section class="l">
                    <div>
                        <x-application-logo class="h-auto w-40" width="160" height="150" />
                        <h1>Cada metro cúbico, <em>remisionado y a la vista.</em></h1>
                        <p>Registra la venta, imprime la remisión al instante y consulta cuánto se vendió en cualquier rango de fechas.</p>
                    </div>
                    <div class="pie mono text-xs">Cantera Lajitas S.A.S.</div>
                </section>

                <div class="r">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
