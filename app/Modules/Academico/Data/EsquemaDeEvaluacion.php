<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

final readonly class EsquemaDeEvaluacion
{
    /**
     * @param  list<ComponenteDeEvaluacion>  $componentes
     */
    public function __construct(
        public int $esquemaId,
        public string $nombre,
        public string $notaMaxima,
        public string $notaMinimaAprobatoria,
        public array $componentes,
    ) {}
}
