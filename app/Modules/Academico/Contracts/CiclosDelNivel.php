<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Enums\NivelCurricular;

interface CiclosDelNivel
{
    /**
     * @return list<int> numbers of the active cycles the language configures for the level, in study order; empty for an unknown language
     */
    public function __invoke(string $idioma, NivelCurricular $nivel): array;
}
