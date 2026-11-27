<x-guest-layout>
    <div>
        <div class="ey">Zona protegida</div>
        <h2 style="font-size: 1.9rem">Confirma tu contraseña</h2>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="grid gap-4">
        @csrf

        <p class="text-[15px]" style="color: #4f4a3e">Por seguridad, escribe de nuevo tu contraseña antes de continuar.</p>

        <div class="field">
            <x-input-label for="password" value="Contraseña" />
            <x-campo-contrasena id="password" required autofocus autocomplete="current-password" :error="$errors->has('password')" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <button type="submit" class="btn pri grande w-full">Confirmar</button>
    </form>
</x-guest-layout>
