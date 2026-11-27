<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\NotasCalculadas;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Shared\Ui\Formato;

final readonly class FilaDelActa
{
    /**
     * @param  array<int, string>  $notas  grade per entered column id, blanks left out
     */
    public function __construct(
        public int $matriculaId,
        public int $personaId,
        public string $estudiante,
        public string $documento,
        public array $notas,
        public ?string $promedio,
        public EstadoFinal $estadoFinal,
        public NotasCalculadas $calculadas,
    ) {}

    /** What a column shows: the grade the teacher entered, or the calculated value with two decimals. */
    public function celda(ComponenteDeEvaluacion $componente): string
    {
        return $componente->esIngresada() ? Formato::nota($this->notas[$componente->id] ?? null) : ($this->calculadas->valor($componente->codigo) ?? '—');
    }
}
