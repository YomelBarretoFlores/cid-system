<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\MatriculaExtemporanea;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Periodo;
use Carbon\CarbonInterface;

final readonly class VerificarMatriculaExtemporanea implements MatriculaExtemporanea
{
    // A period already in progress keeps its late window, so a payment validated after classes start still expects the surcharge.
    public function __invoke(CarbonInterface $fecha): bool
    {
        $dia = $fecha->toDateString();

        return Periodo::query()
            ->whereIn('estado', [EstadoPeriodo::MatriculaAbierta, EstadoPeriodo::EnCurso])
            ->where('matricula_fin', '<', $dia)
            ->where('matricula_extemporanea_fin', '>=', $dia)
            ->exists() && ! $this->enMatriculaRegular($dia);
    }

    // Overlapping periods: the regular window of another one wins, so nobody is charged a surcharge they may not owe.
    private function enMatriculaRegular(string $dia): bool
    {
        return Periodo::query()
            ->where('estado', EstadoPeriodo::MatriculaAbierta)
            ->where('matricula_inicio', '<=', $dia)
            ->where('matricula_fin', '>=', $dia)
            ->exists();
    }
}
