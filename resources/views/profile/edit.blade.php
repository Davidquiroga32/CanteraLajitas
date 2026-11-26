<x-app-layout>
    <x-slot name="titulo">Mi perfil</x-slot>

    <x-encabezado ceja="Cuenta" subtitulo="Tus datos de acceso. El rol, la cédula y el cargo los asigna el administrador.">
        Mi <em>perfil</em>
    </x-encabezado>

    <div class="grid max-w-3xl gap-6">
        <section class="card">
            <div class="pd">
                <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <div class="lbl">Rol</div>
                        <div><span @class(['tag', 'o' => $user->isAdmin()])>{{ $user->role->label() }}</span></div>
                    </div>
                    <div>
                        <div class="lbl">Cédula</div>
                        <div class="mono">{{ $user->cedula ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="lbl">Cargo</div>
                        <div>{{ $user->cargo ?? '—' }}</div>
                    </div>
                </div>
                @include('profile.partials.update-profile-information-form')
            </div>
        </section>

        <section class="card">
            <div class="pd">
                @include('profile.partials.update-password-form')
            </div>
        </section>
    </div>
</x-app-layout>
