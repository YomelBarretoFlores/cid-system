<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Policies;

use App\Modules\Pagos\Enums\PermisoPago;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

final class ConceptoPolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoPago::Configurar->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoPago::Configurar->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermisoPago::Configurar->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermisoPago::Configurar->value);
    }
}
