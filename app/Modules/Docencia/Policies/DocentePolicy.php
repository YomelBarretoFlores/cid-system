<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Policies;

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

final class DocentePolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoDocencia::Ver->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoDocencia::Ver->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermisoDocencia::Gestionar->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermisoDocencia::Gestionar->value);
    }

    public function cambiarEstado(User $user): bool
    {
        return $user->can(PermisoDocencia::Gestionar->value);
    }
}
