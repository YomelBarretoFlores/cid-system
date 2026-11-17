<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Certificacion\Enums\EstadoFinal;

final readonly class AvanceDeNotasDelEstudiante
{
    /**
     * @param  list<NotaDelComponente>  $componentes
     */
    public function __construct(
        public bool $disponible,
        public bool $actaCerrada,
        public array $componentes,
        public ?string $promedio,
        public ?EstadoFinal $estadoFinal,
        public ?string $notaMinimaAprobatoria,
        public ?string $aviso = null,
        public ?string $promedioHabilidades = null,
        public ?string $promedioExamenes = null,
    ) {}

    public static function noDisponible(): self
    {
        return new self(false, false, [], null, null, null, 'Las notas de este grupo aún no están disponibles.');
    }
}
