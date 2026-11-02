<?php

declare(strict_types=1);

namespace App\Shared\Audit\Policies;

use App\Shared\Audit\Enums\PermisoAuditoria;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

final class RegistroDeAuditoriaPolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoAuditoria::Ver->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoAuditoria::Ver->value);
    }

    public function create(): bool
    {
        return false;
    }
}
