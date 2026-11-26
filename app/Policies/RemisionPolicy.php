<?php

namespace App\Policies;

use App\Models\Remision;
use App\Models\User;

class RemisionPolicy
{
    /**
     * El administrador ve todas las remisiones; el vendedor solo las suyas.
     */
    public function view(User $user, Remision $remision): bool
    {
        return $user->isAdmin() || $remision->vendedor_id === $user->id;
    }
}
