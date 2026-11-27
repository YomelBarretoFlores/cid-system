<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Support\ContrasenaDeOficina;
use App\Modules\Docencia\Support\CuentaDelDocente;
use App\Shared\Auth\Actions\CambiarContrasenaEnOficina;
use App\Shared\Auth\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class CambiarContrasenaDelDocente
{
    public function __construct(
        private CuentaDelDocente $cuenta,
        private CambiarContrasenaEnOficina $cambiar,
    ) {}

    public function __invoke(int $docenteId, string $contrasena, User $actor): void
    {
        throw_unless($actor->can(PermisoDocencia::Gestionar->value), AuthorizationException::class);
        ContrasenaDeOficina::validar($contrasena);

        ($this->cambiar)(($this->cuenta)($docenteId)->usuarioId, $contrasena, $actor);
    }
}
