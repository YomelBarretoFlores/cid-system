<?php

declare(strict_types=1);

namespace App\Modules\Academico\Policies;

use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

final class GrupoPolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoAcademico::Ver->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoAcademico::Ver->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermisoAcademico::Gestionar->value);
    }

    // A cancelled or finished group no longer saves, so its edit is not offered.
    public function update(User $user, Grupo $grupo): bool
    {
        return $user->can(PermisoAcademico::Gestionar->value) && ! $grupo->estado->esTerminal();
    }

    public function cambiarEstado(User $user): bool
    {
        return $user->can(PermisoAcademico::Gestionar->value);
    }
}
