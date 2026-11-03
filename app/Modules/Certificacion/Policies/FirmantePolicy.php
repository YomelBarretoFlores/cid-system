<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Policies;

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Firmante;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

// Signers are kept by the staff that issues documents; a signer of actas also needs the actas permission. A signer who leaves gets an end date, never a deletion.
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

    public function update(User $user, Firmante $firmante): bool
    {
        return $user->can(PermisoCertificacion::Documentos->value) && (! $firmante->firma_actas || self::firmaActas($user));
    }

    // Who may mark a signer as a signer of actas; the form disables the toggle otherwise, so it is never saved.
    public static function firmaActas(User $user): bool
    {
        return $user->can(PermisoCertificacion::Actas->value);
    }
}
