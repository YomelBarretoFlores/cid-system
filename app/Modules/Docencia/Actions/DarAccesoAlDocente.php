<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Support\ContrasenaDeOficina;
use App\Modules\Personas\Contracts\AccesoConDocumento;
use App\Modules\Personas\Data\CuentaDelPortal;
use App\Shared\Auth\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

// Most teachers sign in with the DNI and a password CILN gives them, as in the previous system.
final readonly class DarAccesoAlDocente
{
    public function __construct(private AccesoConDocumento $acceso) {}

    public function __invoke(int $docenteId, string $contrasena, User $actor): CuentaDelPortal
    {
        throw_unless($actor->can(PermisoDocencia::Gestionar->value), AuthorizationException::class);
        ContrasenaDeOficina::validar($contrasena);

        return ($this->acceso)(Docente::query()->findOrFail($docenteId)->persona_id, $contrasena);
    }
}
