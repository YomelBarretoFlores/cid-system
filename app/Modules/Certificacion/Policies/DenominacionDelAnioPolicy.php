<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Policies;

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

// The yearly denomination is read by the staff that issues documents and changed only with the actas permission too, since actas print it; issued documents keep their text.
final class DenominacionDelAnioPolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoCertificacion::Documentos->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoCertificacion::Documentos->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermisoCertificacion::Documentos->value) && $user->can(PermisoCertificacion::Actas->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermisoCertificacion::Documentos->value) && $user->can(PermisoCertificacion::Actas->value);
    }
}
