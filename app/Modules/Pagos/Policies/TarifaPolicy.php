<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Policies;

use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

final class TarifaPolicy
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

    public function cerrar(User $user): bool
    {
        return $user->can(PermisoPago::Configurar->value);
    }

    public function delete(User $user, Tarifa $tarifa): bool
    {
        return $user->can(PermisoPago::Configurar->value) && ! Pago::query()->where('tarifa_id', $tarifa->id)->exists();
    }
}
