<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Contracts\CuentasDePersonas;
use App\Modules\Personas\Data\CuentaDelPortal;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class CuentaDelDocente
{
    public function __construct(private CuentasDePersonas $cuentas) {}

    public function __invoke(int $docenteId): CuentaDelPortal
    {
        $personaId = Docente::query()->findOrFail($docenteId)->persona_id;

        return ($this->cuentas)([$personaId])[$personaId] ?? throw new ReglaDeNegocioIncumplida('El docente aún no tiene cuenta.');
    }
}
