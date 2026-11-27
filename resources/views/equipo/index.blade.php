@use('App\Support\Formato')
@use('App\Enums\Rol')
@php
    $vacio = ['id' => null, 'name' => '', 'cedula' => '', 'cargo' => '', 'role' => Rol::Vendedor->value, 'email' => '', 'activo' => true];
    $formulario = $errors->usuario->any()
        ? ['id' => old('_editando') ? (int) old('_editando') : null, 'name' => old('name'), 'cedula' => old('cedula'), 'cargo' => old('cargo'), 'role' => old('role', Rol::Vendedor->value), 'email' => old('email'), 'activo' => (bool) old('activo', true)]
        : $vacio;
    $clave = ['id' => old('_usuario') ? (int) old('_usuario') : null, 'name' => old('_nombre', '')];
    $yo = auth()->id();
@endphp

<x-app-layout>
    <x-slot name="titulo">Equipo</x-slot>

    <div x-data="{
            form: @js($formulario),
            clave: @js($clave),
            nuevo() { this.form = @js($vacio); this.$dispatch('open-modal', 'usuario') },
            editar(u) { this.form = { ...u }; this.$dispatch('open-modal', 'usuario') },
            contrasena(u) { this.clave = { id: u.id, name: u.name }; this.$dispatch('open-modal', 'contrasena') },
        }">
        <x-encabezado ceja="Administración" subtitulo="Personas que usan la aplicación. Un vendedor solo ve sus propias remisiones; el administrador ve todo.">
            Equipo <em>y vendedores</em>
            <x-slot name="acciones">
                <button type="button" class="btn pri" @click="nuevo()">+ Nuevo usuario</button>
            </x-slot>
        </x-encabezado>

        <section class="card">
            <form method="GET" action="{{ route('equipo.index') }}" class="flex flex-wrap items-end gap-3 border-b px-[22px] py-[18px]" style="border-color: var(--line)" x-data="filtroEnVivo" role="search">
                <div class="field min-w-[240px] flex-1">
                    <label for="q">Buscar</label>
                    <input id="q" type="search" name="q" class="inp" value="{{ $busqueda }}" placeholder="Escribe un nombre, cédula, cargo o correo…" autocomplete="off">
                </div>
            </form>

            <div data-vivo="lista">
            <div class="tw">
                <table class="t t-movil">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Cédula</th>
                            <th>Correo</th>
                            <th class="num">Remisiones</th>
                            <th>Última remisión</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($usuarios as $usuario)
                            @php($datos = ['id' => $usuario->id, 'name' => $usuario->name, 'cedula' => $usuario->cedula, 'cargo' => $usuario->cargo, 'role' => $usuario->role->value, 'email' => $usuario->email, 'activo' => $usuario->activo])
                            <tr @style(['opacity: .6' => ! $usuario->activo])>
                                <td class="principal">
                                    <span class="font-medium" style="color: var(--navy)">{{ $usuario->name }}</span>
                                    @if ($usuario->id === $yo)
                                        <span class="muted text-[12.5px]">(tú)</span>
                                    @endif
                                    <div class="muted text-[13px]">{{ $usuario->cargo ?: '—' }}</div>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <span @class(['tag', 'v' => $usuario->isAdmin()])>{{ $usuario->role->label() }}</span>
                                        @unless ($usuario->activo)
                                            <span class="tag c">Inactivo</span>
                                        @endunless
                                    </div>
                                </td>
                                <td class="mono whitespace-nowrap text-[13px]" data-label="Cédula">{{ $usuario->cedula ?? '—' }}</td>
                                <td class="break-all text-[13.5px]" data-label="Correo">{{ $usuario->email }}</td>
                                <td class="num" data-label="Remisiones">{{ $usuario->remisiones_count }}</td>
                                <td data-label="Última remisión">{{ $usuario->ultima_remision ? Formato::fecha(\Illuminate\Support\Carbon::parse($usuario->ultima_remision)) : '—' }}</td>
                                <td class="text-right" data-label="Acciones">
                                    <div class="inline-flex flex-wrap justify-end gap-2">
                                        <button type="button" class="btn sm" @click="editar(@js($datos))">Editar</button>
                                        <button type="button" class="btn sm" @click="contrasena(@js($datos))">Cambiar contraseña</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty">
                                    {{ $busqueda !== '' ? 'No se encontraron usuarios con "'.$busqueda.'".' : 'Aún no hay usuarios registrados.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($usuarios->hasPages())
                <div class="px-[22px] py-4">{{ $usuarios->links() }}</div>
            @endif
            </div>
        </section>

        {{-- Crear / editar usuario --}}
        <x-modal name="usuario" :show="$errors->usuario->any()" maxWidth="lg" focusable>
            <form method="POST" :action="form.id ? @js(url('/equipo')) + '/' + form.id : @js(route('equipo.store'))">
                @csrf
                <input type="hidden" name="_method" value="PATCH" :disabled="! form.id">
                <input type="hidden" name="_editando" :value="form.id">
                <div class="md-hd">
                    <div class="ey" x-text="form.id ? 'Editar usuario' : 'Nuevo usuario'"></div>
                    <h2 x-text="form.id ? form.name : 'Dar acceso a una persona'"></h2>
                </div>
                <div class="md-bd">
                    <x-alerta-grande titulo="Revisa estos datos" :errores="$errors->usuario->all()" />
                    <div class="field">
                        <label for="u-nombre">Nombre completo</label>
                        <input id="u-nombre" name="name" class="inp" x-model="form.name" required maxlength="120" autocomplete="off">
                    </div>
                    <div class="f2">
                        <div class="field">
                            <label for="u-cedula">Cédula</label>
                            <input id="u-cedula" name="cedula" class="inp mono" x-model="form.cedula" required maxlength="20" inputmode="numeric" placeholder="1.121.845.330" autocomplete="off">
                        </div>
                        <div class="field">
                            <label for="u-cargo">Cargo (opcional)</label>
                            <input id="u-cargo" name="cargo" class="inp" x-model="form.cargo" maxlength="80" placeholder="Ej. Vendedor de despacho">
                        </div>
                    </div>
                    <div class="field">
                        <label for="u-email">Correo (con este entra a la aplicación)</label>
                        <input id="u-email" name="email" type="email" class="inp" x-model="form.email" required maxlength="255" autocomplete="off" autocapitalize="off">
                    </div>
                    <div class="field">
                        <span class="lbl">Rol</span>
                        <div class="seg">
                            <label><input type="radio" name="role" value="{{ Rol::Vendedor->value }}" x-model="form.role" :disabled="form.id === {{ $yo }}"> Vendedor</label>
                            <label><input type="radio" name="role" value="{{ Rol::Admin->value }}" x-model="form.role"> Administrador</label>
                        </div>
                        <p class="muted text-[13px]" x-show="form.role === 'vendedor'">Hace remisiones y solo ve las suyas.</p>
                        <p class="muted text-[13px]" x-show="form.id === {{ $yo }}">Es tu propio usuario: no puedes quitarte el rol de administrador ni desactivarte.</p>
                        <p class="muted text-[13px]" x-show="form.role === 'admin' && form.id !== {{ $yo }}">Ve todo: cartera, equipo, reportes, y puede anular remisiones y cambiar precios.</p>
                    </div>
                    <template x-if="! form.id">
                        <div class="f2">
                            <div class="field">
                                <label for="u-password">Contraseña inicial</label>
                                <x-campo-contrasena id="u-password" required autocomplete="new-password" minlength="8" />
                            </div>
                            <div class="field">
                                <label for="u-password2">Repite la contraseña</label>
                                <x-campo-contrasena id="u-password2" name="password_confirmation" required autocomplete="new-password" minlength="8" />
                            </div>
                        </div>
                    </template>
                    <p class="muted text-[13px]" x-show="! form.id">Mínimo 8 caracteres. Entrégasela a la persona en privado.</p>
                    <template x-if="form.id && form.id !== {{ $yo }}">
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input type="hidden" name="activo" value="0">
                            <input type="checkbox" name="activo" value="1" class="rounded border-linea-2 text-navy focus:ring-navy" x-model="form.activo">
                            Usuario activo (si se desactiva, ya no puede entrar; sus remisiones se conservan)
                        </label>
                    </template>
                </div>
                <div class="md-ft">
                    <button type="button" class="btn" @click="$dispatch('close-modal', 'usuario')">Cancelar</button>
                    <button type="submit" class="btn pri">Guardar usuario</button>
                </div>
            </form>
        </x-modal>

        {{-- Asignar contraseña nueva --}}
        <x-modal name="contrasena" :show="$errors->contrasena->any()" maxWidth="lg" focusable>
            <form method="POST" :action="@js(url('/equipo')) + '/' + clave.id + '/contrasena'">
                @csrf
                @method('PUT')
                <input type="hidden" name="_usuario" :value="clave.id">
                <input type="hidden" name="_nombre" :value="clave.name">
                <div class="md-hd">
                    <div class="ey" x-text="clave.name"></div>
                    <h2>Asignar contraseña nueva</h2>
                </div>
                <div class="md-bd">
                    <x-alerta-grande titulo="Revisa estos datos" :errores="$errors->contrasena->all()" />
                    <p class="muted text-[14px]">Úsalo cuando alguien olvidó su contraseña. La anterior deja de funcionar.</p>
                    <div class="field">
                        <label for="k-password">Contraseña nueva</label>
                        <x-campo-contrasena id="k-password" required autocomplete="new-password" minlength="8" />
                    </div>
                    <div class="field">
                        <label for="k-password2">Repite la contraseña</label>
                        <x-campo-contrasena id="k-password2" name="password_confirmation" required autocomplete="new-password" minlength="8" />
                    </div>
                </div>
                <div class="md-ft">
                    <button type="button" class="btn" @click="$dispatch('close-modal', 'contrasena')">Cancelar</button>
                    <button type="submit" class="btn pri">Cambiar contraseña</button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
