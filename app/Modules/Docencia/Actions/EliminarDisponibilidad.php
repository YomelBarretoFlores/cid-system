<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Support\ReglasDeCargaDocente;
use App\Shared\Support\Database\Transaccion;

final readonly class EliminarDisponibilidad
{
    public function __construct(private ReglasDeCargaDocente $reglas) {}

    public function __invoke(int $docenteId, int $disponibilidadId): void
    {
        Transaccion::ejecutar(function () use ($docenteId, $disponibilidadId): void {
            $docente = Docente::query()->whereKey($docenteId)->lockForUpdate()->firstOrFail();

            $docente->disponibilidades()->findOrFail($disponibilidadId)->delete();
            $this->reglas->asegurarCargaVigente($docente);
        });
    }
}
