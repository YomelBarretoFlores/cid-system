<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Policies;

use App\Modules\Tramites\Enums\PermisoTramite;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

// Trámites are started by students and moved forward by staff; nobody creates, edits or deletes them from the panel.
final class TramitePolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoTramite::Gestionar->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoTramite::Gestionar->value);
    }

    public function gestionar(User $user): bool
    {
        return $user->can(PermisoTramite::Gestionar->value);
    }
}
