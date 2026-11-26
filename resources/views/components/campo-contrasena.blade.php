@props(['id', 'name' => 'password', 'error' => false])

{{-- Campo de contraseña con botón para mostrar u ocultar lo que se escribe. --}}
<div class="inp-pass" x-data="{ visible: false }">
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="password"
        :type="visible ? 'text' : 'password'"
        {{ $attributes->merge(['class' => 'inp'.($error ? ' err' : '')]) }}
    >
    <button
        type="button"
        class="ver"
        @click="visible = ! visible; $nextTick(() => document.getElementById(@js($id)).focus())"
        :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
        :title="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
        :aria-pressed="visible.toString()"
        aria-controls="{{ $id }}"
    >
        <x-icono nombre="ojo" class="h-5 w-5" x-show="! visible" />
        <x-icono nombre="ojo-cerrado" class="h-5 w-5" x-show="visible" x-cloak />
    </button>
</div>
