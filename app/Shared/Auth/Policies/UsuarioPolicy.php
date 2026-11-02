<?php

declare(strict_types=1);

namespace App\Shared\Auth\Policies;

use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;

// Staff accounts are deactivated, never deleted.
final class UsuarioPolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoUsuario::Gestionar->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoUsuario::Gestionar->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermisoUsuario::Gestionar->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermisoUsuario::Gestionar->value);
    }
}
