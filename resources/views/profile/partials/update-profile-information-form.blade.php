<section>
    <header>
        <h2>Datos personales</h2>
        <p class="sub mt-1 text-sm">Actualiza tu nombre y el correo con el que ingresas.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-5 grid gap-4">
        @csrf
        @method('patch')

        <div class="field">
            <x-input-label for="name" value="Nombre completo" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="field">
            <x-input-label for="email" value="Correo" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-sm font-medium" style="color: var(--ok)">Datos guardados.</p>
            @endif
        </div>
    </form>
</section>
