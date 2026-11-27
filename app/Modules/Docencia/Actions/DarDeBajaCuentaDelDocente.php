<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Support\CuentaDelDocente;
use App\Shared\Auth\Actions\DesactivarCuentaDelPortal;
use App\Shared\Auth\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class DarDeBajaCuentaDelDocente
{
    public function __construct(
        private CuentaDelDocente $cuenta,
        private DesactivarCuentaDelPortal $desactivar,
    ) {}

    public function __invoke(int $docenteId, string $motivo, User $actor): void
    {
        throw_unless($actor->can(PermisoDocencia::Gestionar->value), AuthorizationException::class);
        ($this->desactivar)(($this->cuenta)($docenteId)->usuarioId, $motivo, $actor);
    }
}
