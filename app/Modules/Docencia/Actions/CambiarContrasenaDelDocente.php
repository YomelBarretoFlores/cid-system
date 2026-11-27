<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Support\ContrasenaDeOficina;
use App\Modules\Personas\Contracts\CuentasDePersonas;
use App\Shared\Auth\Actions\CambiarContrasenaEnOficina;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class CambiarContrasenaDelDocente
{
    public function __construct(
        private CuentasDePersonas $cuentas,
        private CambiarContrasenaEnOficina $cambiar,
    ) {}

    public function __invoke(int $docenteId, string $contrasena, User $actor): void
    {
        throw_unless($actor->can(PermisoDocencia::Gestionar->value), AuthorizationException::class);
        ContrasenaDeOficina::validar($contrasena);

        $personaId = Docente::query()->findOrFail($docenteId)->persona_id;
        $cuenta = ($this->cuentas)([$personaId])[$personaId] ?? throw new ReglaDeNegocioIncumplida('El docente aún no tiene cuenta.');

        ($this->cambiar)($cuenta->usuarioId, $contrasena, $actor);
    }
}
