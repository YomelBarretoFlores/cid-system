<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Policies;

use App\Modules\Pagos\Enums\PermisoPago;
use App\Shared\Auth\Models\User;

final class PagoPolicy
{
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

    public function update(): bool
    {
        return false;
    }

    public function delete(): bool
    {
        return false;
    }

    public function deleteAny(): bool
    {
        return false;
    }

    public function restore(): bool
    {
        return false;
    }

    public function restoreAny(): bool
    {
        return false;
    }

    public function forceDelete(): bool
    {
        return false;
    }

    public function forceDeleteAny(): bool
    {
        return false;
    }

    public function replicate(): bool
    {
        return false;
    }

    public function reorder(): bool
    {
        return false;
    }
}
