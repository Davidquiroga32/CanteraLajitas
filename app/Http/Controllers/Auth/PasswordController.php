<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $recordado = $request->cookies->has(Auth::guard('web')->getRecallerName());
        $request->user()->cerrarSesiones($request->session()->getId());

        if ($recordado) {
            Auth::guard('web')->login($request->user(), remember: true);
        }

        Auditoria::registrar('usuario.contrasena_propia', $request->user());

        return back()->with('status', 'password-updated');
    }
}
