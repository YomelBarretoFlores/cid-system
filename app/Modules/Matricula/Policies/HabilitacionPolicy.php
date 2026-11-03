<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Policies;

use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

// Enabled cycles move by placement and by passing a cycle; staff only adjust them with a reason, never delete them.
final class HabilitacionPolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoMatricula::Ver->value);
    }

    public function create(): bool
    {
        return false;
    }

    public function ajustar(User $user): bool
    {
        return $user->can(PermisoMatricula::Gestionar->value);
    }
}
