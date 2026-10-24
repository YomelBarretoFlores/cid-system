<?php

declare(strict_types=1);

namespace App\Modules\Personas\Policies;

use App\Modules\Personas\Enums\PermisoPersona;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

final class PersonaPolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoPersona::Ver->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoPersona::Ver->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermisoPersona::Gestionar->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermisoPersona::Gestionar->value);
    }
}
