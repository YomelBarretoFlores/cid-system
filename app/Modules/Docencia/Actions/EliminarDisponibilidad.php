<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Models\Docente;
use Illuminate\Support\Facades\DB;

final readonly class EliminarDisponibilidad
{
    public function __invoke(int $docenteId, int $disponibilidadId): void
    {
        DB::transaction(function () use ($docenteId, $disponibilidadId): void {
            $docente = Docente::query()->whereKey($docenteId)->lockForUpdate()->firstOrFail();

            $docente->disponibilidades()->findOrFail($disponibilidadId)->delete();
        });
    }
}
