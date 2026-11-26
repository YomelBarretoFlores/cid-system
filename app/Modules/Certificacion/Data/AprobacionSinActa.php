<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Certificacion\Enums\FormaDeAprobacion;
use Carbon\CarbonImmutable;

final readonly class AprobacionSinActa
{
    /**
     * @param  string  $origenTipo  solicitud_clasificacion or tramite
     */
    public function __construct(
        public int $personaId,
        public string $idioma,
        public FormaDeAprobacion $forma,
        public ?string $nota,
        public CarbonImmutable $fecha,
        public string $origenTipo,
        public int $origenId,
        public ?int $registradoPor,
    ) {}
}
