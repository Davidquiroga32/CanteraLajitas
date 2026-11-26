<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($titulo) ? $titulo.' · ' : '' }}{{ config('app.name', 'Cantera Lajitas') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/favicon-64.png') }}">

        @include('layouts.partials.fuentes')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body x-data="{ menuAbierto: false }">
        <div class="mtop noprint">
            <img src="{{ asset('images/logo-cantera-lajitas.png') }}" alt="Cantera Lajitas S.A.S." width="54" height="51">
            <button type="button" class="btn sm" @click="menuAbierto = ! menuAbierto" :aria-expanded="menuAbierto" aria-label="Abrir menú">
                <x-icono nombre="menu" class="w-5 h-5" />
                Menú
            </button>
        </div>

        <div class="app">
            @include('layouts.sidebar')

            <div class="ct">
                <main class="pg">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @if (session('ok'))
            <div class="toast noprint" role="status" x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 3500)" x-transition>
                {{ session('ok') }}
            </div>
        @endif
    </body>
</html>
