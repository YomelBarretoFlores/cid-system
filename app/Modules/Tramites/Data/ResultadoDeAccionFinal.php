<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Data;

final readonly class ResultadoDeAccionFinal
{
    /**
     * @param  ?array<string, bool|int|string|null>  $resultado  stored in tra_tramites.resultado
     * @param  ?string  $comentario  the summary written on the last movement, which the student reads
     */
    public function __construct(
        public ?int $documentoId = null,
        public ?array $resultado = null,
        public ?string $comentario = null,
    ) {}
}
