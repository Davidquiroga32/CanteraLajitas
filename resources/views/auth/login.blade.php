<x-guest-layout>
    <div>
        <div class="ey">Bienvenido</div>
        <h2 style="font-size: 1.9rem">Iniciar sesión</h2>
    </div>

    <x-auth-session-status :status="session('status')" />

    {{-- "Recordar mis datos": mantiene la sesión abierta y guarda el correo en este equipo.
         La contraseña la guarda el propio navegador o celular si el usuario acepta. --}}
    <form method="POST" action="{{ route('login') }}" class="grid gap-4" x-data="recordarIngreso" @submit="guardar">
        @csrf

        <div class="field">
            <x-input-label for="email" value="Correo" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" x-ref="correo" required autofocus autocomplete="username" @class(['err' => $errors->has('email')]) />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="field">
            <x-input-label for="password" value="Contraseña" />
            <x-campo-contrasena id="password" required autocomplete="current-password" :error="$errors->has('password')" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label for="remember_me" class="flex cursor-pointer items-center gap-2 text-sm" style="color: #4f4a3e">
            <input id="remember_me" type="checkbox" name="remember" value="1" x-ref="recordar" class="h-5 w-5 rounded border-linea-2 text-navy focus:ring-navy" @checked(old('remember', true))>
            Recordar mis datos en este equipo
        </label>

        <button type="submit" class="btn pri grande w-full">Ingresar</button>

        <p class="muted text-center text-sm">¿Olvidaste tu contraseña? Pídele al administrador que te asigne una nueva.</p>
    </form>
</x-guest-layout>
