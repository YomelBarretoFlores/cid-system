<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Policies;

use App\Modules\Tramites\Enums\PermisoTramite;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

final class TipoTramitePolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoTramite::Configurar->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoTramite::Configurar->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermisoTramite::Configurar->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermisoTramite::Configurar->value);
    }
}
