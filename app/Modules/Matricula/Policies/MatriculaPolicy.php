<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Policies;

use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Policies\DeniegaLoNoConcedido;

// Enrollments are created only by MatricularEstudiante; staff consult them and move or withdraw them.
final class MatriculaPolicy
{
    use DeniegaLoNoConcedido;

    public function viewAny(User $user): bool
    {
        return $user->can(PermisoMatricula::Ver->value);
    }

    public function view(User $user): bool
    {
        return $user->can(PermisoMatricula::Ver->value);
    }

    public function create(): bool
    {
        return false;
    }

    public function trasladar(User $user): bool
    {
        return $user->can(PermisoMatricula::Gestionar->value);
    }

    public function retirar(User $user): bool
    {
        return $user->can(PermisoMatricula::Gestionar->value);
    }
}
