<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

use App\Modules\Certificacion\Data\RecordDelIdioma;

interface RecordDeNotas
{
    /**
     * @return list<RecordDelIdioma> one per language with closed actas, by language name
     */
    public function __invoke(int $personaId): array;
}
