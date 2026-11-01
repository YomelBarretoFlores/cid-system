<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Policies;

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

// Signers are kept by the staff that issues documents; a signer who leaves gets an end date, never a deletion.
final class FirmantePolicy
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
        return $user->can(PermisoCertificacion::Documentos->value);
    }

    public function update(User $user): bool
    {
        return $user->can(PermisoCertificacion::Documentos->value);
    }
}
