<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Academico\Enums\GrupoDeComponente;
use App\Modules\Certificacion\Enums\EstadoFinal;

final readonly class FilaDelActa
{
    /**
     * @param  array<int, string>  $notas  grade per component id, blanks left out
     * @param  ?string  $promedioHabilidades  shown with two decimals; the final grade never uses it rounded
     */
    public function __construct(
        public int $matriculaId,
        public int $personaId,
        public string $estudiante,
        public string $documento,
        public array $notas,
        public ?string $promedio,
        public EstadoFinal $estadoFinal,
        public ?string $promedioHabilidades,
        public ?string $promedioExamenes,
    ) {}

    public function promedioDe(GrupoDeComponente $grupo): ?string
    {
        return $grupo === GrupoDeComponente::Habilidades ? $this->promedioHabilidades : $this->promedioExamenes;
    }
}
