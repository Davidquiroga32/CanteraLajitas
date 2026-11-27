<?php

namespace App\Http\Controllers;

use App\Http\Requests\AsignarContrasenaRequest;
use App\Http\Requests\GuardarUsuarioRequest;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EquipoController extends Controller
{
    /**
     * Usuarios de la aplicación con su rol, estado y remisiones emitidas.
     */
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('q', ''));
        $digitosBusqueda = preg_replace('/\D/', '', $busqueda);

        $usuarios = User::query()
            ->when($busqueda !== '', function (Builder $query) use ($busqueda, $digitosBusqueda): void {
                $query->where(function (Builder $query) use ($busqueda, $digitosBusqueda): void {
                    $query->where('name', 'like', '%'.$busqueda.'%')
                        ->orWhere('email', 'like', '%'.$busqueda.'%')
                        ->orWhere('cargo', 'like', '%'.$busqueda.'%')
                        ->orWhere('cedula', 'like', '%'.$busqueda.'%')
                        ->when($digitosBusqueda !== '', fn (Builder $q) => $q->orWhereRaw("replace(cedula, '.', '') like ?", ['%'.$digitosBusqueda.'%']));
                });
            })
            ->withCount(['remisiones' => fn (Builder $q) => $q->emitidas()])
            ->withMax(['remisiones as ultima_remision' => fn (Builder $q) => $q->emitidas()], 'emitida_en')
            ->orderByDesc('activo')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('equipo.index', [
            'usuarios' => $usuarios,
            'busqueda' => $busqueda,
        ]);
    }

    public function store(GuardarUsuarioRequest $request): RedirectResponse
    {
        $usuario = User::query()->create([...$request->validated(), 'email_verified_at' => now()]);

        Auditoria::registrar('usuario.creado', $usuario, $usuario->only(['name', 'cedula', 'role']));

        return to_route('equipo.index')->with('ok', 'Usuario "'.$usuario->name.'" creado. Ya puede entrar con su correo y la contraseña que le asignaste.');
    }

    public function update(GuardarUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $usuario->fill($request->validated());
        $cambios = collect($usuario->getDirty())->map(fn ($nuevo, $campo) => ['antes' => $usuario->getOriginal($campo), 'despues' => $nuevo]);
        $usuario->save();

        if ($cambios->isNotEmpty()) {
            Auditoria::registrar('usuario.editado', $usuario, $cambios->map(fn (array $cambio) => array_map(fn ($valor) => $valor instanceof \BackedEnum ? $valor->value : $valor, $cambio))->all());
        }

        $mensaje = $cambios->has('activo') && ! $usuario->activo
            ? '"'.$usuario->name.'" quedó desactivado: ya no puede entrar. Sus remisiones se conservan.'
            : 'Datos de "'.$usuario->name.'" actualizados.';

        return to_route('equipo.index')->with('ok', $mensaje);
    }

    /**
     * Contraseña nueva para un usuario que olvidó la suya. Cierra las sesiones
     * recordadas que tuviera abiertas en otros equipos.
     */
    public function asignarContrasena(AsignarContrasenaRequest $request, User $usuario): RedirectResponse
    {
        $usuario->forceFill([
            'password' => $request->validated('password'),
            'remember_token' => Str::random(60),
        ])->save();

        Auditoria::registrar('usuario.contrasena', $usuario);

        return to_route('equipo.index')->with('ok', 'Contraseña de "'.$usuario->name.'" cambiada. Dísela en persona.');
    }
}
