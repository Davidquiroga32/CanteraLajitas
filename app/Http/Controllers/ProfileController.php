<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $cambios = collect($request->user()->getDirty())
            ->only(['name', 'email'])
            ->map(fn ($nuevo, $campo) => ['antes' => $request->user()->getOriginal($campo), 'despues' => $nuevo]);

        $request->user()->save();

        if ($cambios->isNotEmpty()) {
            Auditoria::registrar('usuario.editado', $request->user(), $cambios->all());
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
