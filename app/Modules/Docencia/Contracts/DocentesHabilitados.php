<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

use App\Modules\Academico\Enums\NivelCurricular;

// Active teachers enabled to teach a language up to a level, for a group form to offer.
interface DocentesHabilitados
{
    /**
     * @return array<int, string> teacher id => code and full name
     */
    public function __invoke(int $idiomaId, NivelCurricular $nivel): array;
}
