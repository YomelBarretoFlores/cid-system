<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Policies;

use App\Modules\Pagos\Enums\PermisoPago;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

// Payment channels and fee sources: the payment configurators keep them; nobody deletes an item.
final class CatalogoDePagosPolicy
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
