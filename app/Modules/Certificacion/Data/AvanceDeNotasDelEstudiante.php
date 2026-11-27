<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Certificacion\Enums\EstadoFinal;

final readonly class AvanceDeNotasDelEstudiante
{
    /**
     * @param  list<NotaDelComponente>  $componentes  the columns the teacher enters
     * @param  list<NotaDelComponente>  $calculadas  the calculated columns before the cycle grade
     */
    public function __construct(
        public bool $disponible,
        public bool $actaCerrada,
        public array $componentes,
        public ?string $promedio,
        public ?EstadoFinal $estadoFinal,
        public ?string $notaMinimaAprobatoria,
        public ?string $aviso = null,
        public array $calculadas = [],
    ) {}

    public static function noDisponible(): self
    {
        return new self(false, false, [], null, null, null, 'Las notas de este grupo aún no están disponibles.');
    }
}
