<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

use App\Modules\Certificacion\Data\NivelPorCertificar;

interface NivelesConNotas
{
    /**
     * @return list<NivelPorCertificar> language and level pairs in which the student passed at least one cycle, by language and study order
     */
    public function __invoke(int $personaId): array;
}
