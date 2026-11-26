<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\CicloDelIdioma;

interface CiclosDelIdioma
{
    /**
     * @return list<CicloDelIdioma> every cycle of the language (by name), active or not, in study order; empty for an unknown language
     */
    public function __invoke(string $idioma): array;
}
