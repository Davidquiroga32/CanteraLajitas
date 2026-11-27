<x-guest-layout>
    <div>
        <div class="ey">Recuperar acceso</div>
        <h2 style="font-size: 1.9rem">Contraseña nueva</h2>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="grid gap-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="field">
            <x-input-label for="email" value="Correo" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" @class(['err' => $errors->has('email')]) />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="field">
            <x-input-label for="password" value="Contraseña nueva" />
            <x-campo-contrasena id="password" required autocomplete="new-password" :error="$errors->has('password')" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="field">
            <x-input-label for="password_confirmation" value="Repite la contraseña nueva" />
            <x-campo-contrasena id="password_confirmation" name="password_confirmation" required autocomplete="new-password" :error="$errors->has('password_confirmation')" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <button type="submit" class="btn pri grande w-full">Guardar contraseña</button>
    </form>
</x-guest-layout>
