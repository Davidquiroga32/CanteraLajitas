<section>
    <header>
        <h2>Cambiar contraseña</h2>
        <p class="sub mt-1 text-sm">Usa una contraseña de al menos 8 caracteres que solo tú conozcas.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-5 grid gap-4">
        @csrf
        @method('put')

        <div class="field">
            <x-input-label for="update_password_current_password" value="Contraseña actual" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="field">
            <x-input-label for="update_password_password" value="Contraseña nueva" />
            <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div class="field">
            <x-input-label for="update_password_password_confirmation" value="Repite la contraseña nueva" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Cambiar contraseña</x-primary-button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-sm font-medium" style="color: var(--ok)">Contraseña actualizada.</p>
            @endif
        </div>
    </form>
</section>
