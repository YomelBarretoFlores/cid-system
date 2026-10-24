<?php

declare(strict_types=1);

namespace App\Modules\Academico\Policies;

use App\Modules\Academico\Enums\PermisoAcademico;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

final class HorarioPolicy
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

    public function update(User $user): bool
    {
        return $user->can(PermisoAcademico::Gestionar->value);
    }

    public function delete(User $user): bool
    {
        return $user->can(PermisoAcademico::Gestionar->value);
    }
}
