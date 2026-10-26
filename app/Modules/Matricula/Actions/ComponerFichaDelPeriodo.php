<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Matricula\Data\FichaDelPeriodo;
use App\Modules\Matricula\Data\MatriculaDeLaFicha;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Contracts\ConsultaDePago;
use App\Modules\Personas\Contracts\FichaDePersona;
use Carbon\CarbonImmutable;

// Every enrollment of the person in the period, oldest first, with today's data: groups, teachers and payments as they are now.
final readonly class ComponerFichaDelPeriodo
{
    public function __construct(
        private AsignarCodigoDeFicha $asignarCodigo,
        private FichaDePersona $fichaDePersona,
        private DetalleDeGrupos $detalleDeGrupos,
        private ConsultaDePago $consultaDePago,
        private DocentesDeGrupos $docentesDeGrupos,
    ) {}

    public function __invoke(int $personaId, int $periodoId): FichaDelPeriodo
    {
        $matriculas = Matricula::query()->where('persona_id', $personaId)->orderBy('matriculado_en')->orderBy('id')->get();
        $grupos = ($this->detalleDeGrupos)(array_values(array_unique($matriculas->pluck('grupo_id')->map(intval(...))->all())));
        $delPeriodo = $matriculas->filter(fn (Matricula $matricula): bool => ($grupos[$matricula->grupo_id] ?? null)?->periodoId === $periodoId)->values();
        $docentes = ($this->docentesDeGrupos)(array_values(array_map(intval(...), $delPeriodo->pluck('grupo_id')->all())));

        return new FichaDelPeriodo(
            codigo: ($this->asignarCodigo)($personaId, $periodoId),
            estudiante: ($this->fichaDePersona)($personaId),
            periodo: (string) $delPeriodo->map(fn (Matricula $matricula): string => $grupos[$matricula->grupo_id]->periodo)->first(),
            matriculas: array_values($delPeriodo->map(fn (Matricula $matricula): MatriculaDeLaFicha => new MatriculaDeLaFicha(
                grupo: $grupos[$matricula->grupo_id],
                condicion: $matricula->condicion,
                estado: $matricula->estado,
                matriculadoEn: $matricula->matriculado_en->setTimezone('America/Lima'),
                pago: $matricula->pago_id === null ? null : ($this->consultaDePago)($matricula->pago_id),
                docente: $docentes[$matricula->grupo_id] ?? null,
            ))->all()),
            emitidaEn: CarbonImmutable::now('America/Lima'),
        );
    }
}
