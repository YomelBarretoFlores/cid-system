<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Enums\RegimenDeEsquema;
use Carbon\CarbonImmutable;

final readonly class DatosDeEsquema
{
    /**
     * @param  list<array{codigo: string, nombre: string, tipo: string, calculo?: ?string, redondeo?: ?string, fuentes?: list<array{codigo: string, peso?: mixed}>, ciclo?: mixed}>  $componentes  in the order they are shown
     */
    public function __construct(
        public string $nombre,
        public ?int $idiomaId,
        public ?NivelCurricular $nivel,
        public string $notaMaxima,
        public string $notaMinimaAprobatoria,
        public CarbonImmutable $vigenteDesde,
        public ?CarbonImmutable $vigenteHasta,
        public array $componentes,
        public ?int $programaId = null,
        public ?RegimenDeEsquema $regimen = null,
    ) {}
}
