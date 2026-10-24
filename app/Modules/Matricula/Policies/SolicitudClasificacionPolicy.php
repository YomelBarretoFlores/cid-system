<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Policies;

use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

// Requests are made by applicants in the portal; staff consult them and record results.
final class SolicitudClasificacionPolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoClasificacion::Ver->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoClasificacion::Ver->value);
    }

    public function create(): bool
    {
        return false;
    }

    public function evaluar(User $user): bool
    {
        return $user->can(PermisoClasificacion::Evaluar->value);
    }
}
