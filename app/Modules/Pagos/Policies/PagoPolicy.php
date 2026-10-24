<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Policies;

use App\Modules\Pagos\Enums\PermisoPago;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

final class PagoPolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoPago::Ver->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoPago::Ver->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermisoPago::Registrar->value);
    }

    public function validar(User $user): bool
    {
        return $user->can(PermisoPago::Validar->value);
    }

    public function rechazar(User $user): bool
    {
        return $user->can(PermisoPago::Validar->value);
    }

    public function anular(User $user): bool
    {
        return $user->can(PermisoPago::Anular->value);
    }
}
