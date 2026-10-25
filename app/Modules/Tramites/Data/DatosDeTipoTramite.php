<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Data;

use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CondicionDeEtapa;

final readonly class DatosDeTipoTramite
{
    /**
     * @param  list<array{nombre: string, avance: int, condicion: CondicionDeEtapa}>  $etapas
     */
    public function __construct(
        public string $codigo,
        public string $nombre,
        public ?string $requisitos,
        public ?TipoConcepto $tipoConcepto,
        public AccionFinal $accionFinal,
        public bool $activo,
        public array $etapas,
    ) {}
}
