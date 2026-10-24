<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Policies;

use App\Modules\Docencia\Actions\EliminarCostoPorHora;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

final class CostoPorHoraPolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoDocencia::Costos->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoDocencia::Costos->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermisoDocencia::Costos->value);
    }

    public function delete(User $user, CostoPorHora $costo): bool
    {
        return $user->can(PermisoDocencia::Costos->value) && EliminarCostoPorHora::aunNoVigente($costo);
    }
}
