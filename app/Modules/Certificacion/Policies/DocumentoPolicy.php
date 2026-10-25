<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Policies;

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Documento;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

// Documents are created only by the issuing Actions and never edited; they can be voided once.
final class DocumentoPolicy
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

    public function create(): bool
    {
        return false;
    }

    public function emitir(User $user): bool
    {
        return $user->can(PermisoCertificacion::Documentos->value);
    }

    public function anular(User $user, Documento $documento): bool
    {
        return $user->can(PermisoCertificacion::Documentos->value) && $documento->estaVigente();
    }
}
