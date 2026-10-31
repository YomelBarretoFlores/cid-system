<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\VentanasDeMatricula;
use App\Modules\Academico\Data\VentanaDeMatricula;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Periodo;
use Carbon\CarbonImmutable;

final readonly class ListarVentanasDeMatricula implements VentanasDeMatricula
{
    public function __invoke(): array
    {
        $hoy = CarbonImmutable::today('America/Lima')->toDateString();

        return array_values(Periodo::query()
            ->whereIn('estado', [EstadoPeriodo::Planificado, EstadoPeriodo::MatriculaAbierta])
            ->where('matricula_fin', '>=', $hoy)
            ->orderBy('matricula_inicio')
            ->get()
            ->map(fn (Periodo $periodo): VentanaDeMatricula => new VentanaDeMatricula(
                periodoId: $periodo->id,
                periodo: $periodo->nombre,
                inicio: $periodo->matricula_inicio,
                fin: $periodo->matricula_fin,
                abierta: $periodo->estado === EstadoPeriodo::MatriculaAbierta && $periodo->matricula_inicio->toDateString() <= $hoy,
            ))
            ->all());
    }
}
