<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Support\CuentaDelDocente;
use App\Shared\Auth\Actions\ReactivarCuentaDelPortal;
use App\Shared\Auth\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class ReactivarCuentaDelDocente
{
    public function __construct(
        private CuentaDelDocente $cuenta,
        private ReactivarCuentaDelPortal $reactivar,
    ) {}

    public function __invoke(int $docenteId, User $actor): void
    {
        throw_unless($actor->can(PermisoDocencia::Gestionar->value), AuthorizationException::class);
        ($this->reactivar)(($this->cuenta)($docenteId)->usuarioId, $actor);
    }
}
