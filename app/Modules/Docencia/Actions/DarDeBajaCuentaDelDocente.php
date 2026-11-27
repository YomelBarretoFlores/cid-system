<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Contracts\CuentasDePersonas;
use App\Shared\Auth\Actions\DesactivarCuentaDelPortal;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class DarDeBajaCuentaDelDocente
{
    public function __construct(
        private CuentasDePersonas $cuentas,
        private DesactivarCuentaDelPortal $desactivar,
    ) {}

    public function __invoke(int $docenteId, string $motivo, User $actor): void
    {
        throw_unless($actor->can(PermisoDocencia::Gestionar->value), AuthorizationException::class);
        $personaId = Docente::query()->findOrFail($docenteId)->persona_id;
        $cuenta = ($this->cuentas)([$personaId])[$personaId] ?? throw new ReglaDeNegocioIncumplida('El docente aún no tiene cuenta.');

        ($this->desactivar)($cuenta->usuarioId, $motivo, $actor);
    }
}
