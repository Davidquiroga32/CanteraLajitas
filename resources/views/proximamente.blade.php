<x-app-layout>
    <x-slot name="titulo">{{ $modulo }}</x-slot>

    <x-encabezado ceja="En construcción">
        {{ $modulo }}
    </x-encabezado>

    <section class="card">
        <div class="empty">
            <p class="text-base">Este módulo se construye en la <b>Fase {{ $fase }}</b> del plan de desarrollo.</p>
            <a class="btn mt-4" href="{{ route('dashboard') }}">Volver al inicio</a>
        </div>
    </section>
</x-app-layout>
