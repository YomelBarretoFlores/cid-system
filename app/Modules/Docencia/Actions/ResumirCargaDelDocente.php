<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Docencia\Data\CargaHoraria;
use App\Modules\Docencia\Data\ResumenDeCarga;
use App\Modules\Docencia\Models\CargaAcademica;

final readonly class ResumirCargaDelDocente
{
    public function __construct(private HorariosDeGrupos $horariosDeGrupos) {}

    public function __invoke(int $docenteId): ResumenDeCarga
    {
        $ids = array_values(array_map(intval(...), CargaAcademica::query()->where('docente_id', $docenteId)->pluck('grupo_id')->all()));
        $cargas = array_values(array_map(CargaHoraria::de(...), ($this->horariosDeGrupos)($ids)));
        usort($cargas, fn (CargaHoraria $a, CargaHoraria $b): int => [$b->inicio->toDateString(), $a->codigo] <=> [$a->inicio->toDateString(), $b->codigo]);

        $minutosPorPeriodo = [];

        foreach ($cargas as $carga) {
            $minutosPorPeriodo[$carga->periodo] = ($minutosPorPeriodo[$carga->periodo] ?? 0) + $carga->minutos;
        }

        return new ResumenDeCarga($cargas, array_map(CargaHoraria::formatearHoras(...), $minutosPorPeriodo));
    }
}
