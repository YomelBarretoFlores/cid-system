<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Enums\NivelCurricular;

interface CiclosDelNivel
{
    /**
     * @param  ?int  $grupoId  a group of that language, used when no language has that name any more (it was renamed)
     * @return list<int> numbers of the active cycles the language configures for the level, in study order; empty for an unknown language
     */
    public function __invoke(string $idioma, NivelCurricular $nivel, ?int $grupoId = null): array;
}
