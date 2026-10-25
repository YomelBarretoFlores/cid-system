<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Policies;

use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Acta;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

// Actas are created only by the teacher's validation; the panel reads them and authorizes rectifications.
final class ActaPolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoCertificacion::Actas->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoCertificacion::Actas->value);
    }

    public function create(): bool
    {
        return false;
    }

    public function rectificar(User $user, Acta $acta): bool
    {
        return $user->can(PermisoCertificacion::Actas->value) && $acta->estado === EstadoActa::Cerrada;
    }
}
