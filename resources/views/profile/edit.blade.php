<x-app-layout>
    <x-slot name="titulo">Mi perfil</x-slot>

    <x-encabezado ceja="Cuenta" :subtitulo="$user->isAdmin() ? 'Tus datos de acceso. El rol, la cédula y el cargo se cambian en Equipo.' : 'Tus datos los registra el administrador. Aquí puedes cambiar tu contraseña.'">
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
                @if ($user->isAdmin())
                    @include('profile.partials.update-profile-information-form')
                @else
                    <div class="grid grid-cols-1 gap-4 border-t pt-5 sm:grid-cols-2" style="border-color: var(--line)">
                        <div>
                            <div class="lbl">Nombre completo</div>
                            <div class="text-[16px]">{{ $user->name }}</div>
                        </div>
                        <div>
                            <div class="lbl">Correo para ingresar</div>
                            <div class="break-all text-[16px]">{{ $user->email }}</div>
                        </div>
                    </div>
                    <p class="aviso mt-5 text-[14px]">Si alguno de tus datos está mal, pídele al administrador que lo corrija.</p>
                @endif
            </div>
        </section>

        <section class="card">
            <div class="pd">
                @include('profile.partials.update-password-form')
            </div>
        </section>
    </div>
</x-app-layout>
