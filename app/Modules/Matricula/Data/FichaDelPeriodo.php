<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

use App\Modules\Personas\Data\FichaDeEstudiante;
use Carbon\CarbonImmutable;

final readonly class FichaDelPeriodo
{
    /**
     * @param  list<MatriculaDeLaFicha>  $matriculas
     */
    public function __construct(
        public string $codigo,
        public FichaDeEstudiante $estudiante,
        public string $periodo,
        public array $matriculas,
        public CarbonImmutable $emitidaEn,
    ) {}

    public function primeraMatricula(): ?CarbonImmutable
    {
        return collect($this->matriculas)->min(fn (MatriculaDeLaFicha $matricula): CarbonImmutable => $matricula->matriculadoEn);
    }

    public function totalPagado(): string
    {
        $centimos = array_sum(array_map(fn (MatriculaDeLaFicha $matricula): int => $matricula->centimosPagados(), $this->matriculas));

        return number_format(intdiv($centimos, 100)).'.'.str_pad((string) ($centimos % 100), 2, '0', STR_PAD_LEFT);
    }
}
