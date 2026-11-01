<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Policies;

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

// Employment conditions and academic degrees: teaching managers keep them; nobody deletes an item.
final class CatalogoDeDocenciaPolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoDocencia::Gestionar->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoDocencia::Gestionar->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermisoDocencia::Gestionar->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermisoDocencia::Gestionar->value);
    }
}
