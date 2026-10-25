<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

use App\Modules\Certificacion\Data\NivelPorCertificar;

interface NivelesPorCertificar
{
    /**
     * @return list<NivelPorCertificar> levels the student completed in closed actas and has no valid certificate for
     */
    public function __invoke(int $personaId): array;
}
