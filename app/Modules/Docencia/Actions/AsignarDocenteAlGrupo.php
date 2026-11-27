<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Contracts\AsignacionDeDocente;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Shared\Auth\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class AsignarDocenteAlGrupo implements AsignacionDeDocente
{
    public function __construct(private AsignarDocente $asignarDocente) {}

    public function __invoke(int $grupoId, int $docenteId, User $actor): void
    {
        throw_unless($actor->can(PermisoDocencia::Gestionar->value), AuthorizationException::class);

        ($this->asignarDocente)($grupoId, $docenteId, $actor);
    }
}
