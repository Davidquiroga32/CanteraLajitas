<x-guest-layout>
    <div>
        <div class="ey">Recuperar acceso</div>
        <h2 style="font-size: 1.9rem">¿Olvidaste tu contraseña?</h2>
    </div>

    <div class="grid gap-4">
        <p class="text-[15px]" style="color: #4f4a3e">
            No te preocupes. Pídele al <b>administrador</b> de la cantera que te asigne una contraseña nueva desde la sección <b>Equipo</b>.
            Con ella podrás entrar de inmediato y, si quieres, cambiarla después en <b>Mi perfil</b>.
        </p>

        <a href="{{ route('login') }}" class="btn pri grande w-full justify-center">Volver a iniciar sesión</a>
    </div>
</x-guest-layout>
