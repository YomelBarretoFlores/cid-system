<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Enums\NivelCurricular;
use Carbon\CarbonImmutable;

final readonly class DatosDeEsquema
{
    /**
     * @param  list<array{codigo: string, nombre: string, peso: string}>  $componentes  in the order they are shown
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
    ) {}
}
