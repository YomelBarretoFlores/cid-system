<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

use App\Modules\Docencia\Enums\EstadoSilabo;
use Carbon\CarbonImmutable;

final readonly class DatosDelSilabo
{
    /**
     * @param  list<SemanaDelSilabo>  $semanas  every week of the period, in order, empty fields as null
     * @param  list<EvaluacionDelSilabo>  $evaluaciones  in the order of the group's evaluation scheme
     */
    public function __construct(
        public int $grupoId,
        public EstadoSilabo $estado,
        public ?string $sumilla,
        public ?string $competencias,
        public ?string $capacidades,
        public ?string $metodologia,
        public ?string $bibliografia,
        public ?CarbonImmutable $publicadoEn,
        public array $semanas,
        public array $evaluaciones,
    ) {}

    public function estaPublicado(): bool
    {
        return $this->estado === EstadoSilabo::Publicado;
    }
}
