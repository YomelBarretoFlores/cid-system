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

    public function pagosVerificados(): int
    {
        return count(array_filter($this->matriculas, fn (MatriculaDeLaFicha $matricula): bool => $matricula->centimosPagados() > 0));
    }

    // The latest validation among the payments that count, for the closing line of the record.
    public function validadaEn(): ?CarbonImmutable
    {
        return collect($this->matriculas)
            ->filter(fn (MatriculaDeLaFicha $matricula): bool => $matricula->centimosPagados() > 0)
            ->map(fn (MatriculaDeLaFicha $matricula): ?CarbonImmutable => $matricula->pago?->validadoEn)
            ->filter()
            ->max();
    }

    public function totalPagado(): string
    {
        $centimos = array_sum(array_map(fn (MatriculaDeLaFicha $matricula): int => $matricula->centimosPagados(), $this->matriculas));

        return number_format(intdiv($centimos, 100)).'.'.str_pad((string) ($centimos % 100), 2, '0', STR_PAD_LEFT);
    }
}
