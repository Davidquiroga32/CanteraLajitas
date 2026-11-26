<x-guest-layout>
    <div>
        <div class="ey">Bienvenido</div>
        <h2 style="font-size: 1.9rem">Iniciar sesión</h2>
    </div>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="grid gap-4">
        @csrf

        <div class="field">
            <x-input-label for="email" value="Correo" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" @class(['err' => $errors->has('email')]) />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="field">
            <x-input-label for="password" value="Contraseña" />
            <x-campo-contrasena id="password" required autocomplete="current-password" :error="$errors->has('password')" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label for="remember_me" class="flex cursor-pointer items-center gap-2 text-sm" style="color: #4f4a3e">
            <input id="remember_me" type="checkbox" name="remember" class="rounded border-linea-2 text-navy focus:ring-navy">
            Mantener la sesión iniciada en este equipo
        </label>

        <button type="submit" class="btn pri grande w-full">Ingresar</button>

        <p class="muted text-center text-sm">¿Olvidaste tu contraseña? Pídele al administrador que te asigne una nueva.</p>
    </form>
</x-guest-layout>
