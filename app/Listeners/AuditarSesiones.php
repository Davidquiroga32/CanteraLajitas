<?php

namespace App\Listeners;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;

/**
 * Deja en la auditoría los ingresos, salidas, intentos fallidos y bloqueos del login.
 */
class AuditarSesiones
{
    public function handleLogin(Login $evento): void
    {
        if (! $evento->user instanceof User) {
            return;
        }

        Auditoria::registrar('sesion.ingreso', $evento->user, [
            'automatico' => Auth::guard($evento->guard)->viaRemember(),
        ], $evento->user);
    }

    public function handleFailed(Failed $evento): void
    {
        $usuario = $evento->user instanceof User ? $evento->user : null;

        Auditoria::registrar('sesion.fallido', $usuario, [
            'correo' => mb_substr((string) ($evento->credentials['email'] ?? ''), 0, 120),
            'motivo' => $usuario && ! $usuario->activo ? 'inactivo' : 'datos',
        ], $usuario);
    }

    public function handleLockout(Lockout $evento): void
    {
        Auditoria::registrar('sesion.bloqueo', null, [
            'correo' => mb_substr((string) $evento->request->input('email'), 0, 120),
        ]);
    }

    public function handleLogout(Logout $evento): void
    {
        if (! $evento->user instanceof User) {
            return;
        }

        Auditoria::registrar('sesion.salida', $evento->user, null, $evento->user);
    }
}
