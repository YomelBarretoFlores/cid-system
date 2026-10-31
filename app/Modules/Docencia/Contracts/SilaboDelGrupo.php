<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

use App\Modules\Docencia\Data\DatosDelSilabo;

interface SilaboDelGrupo
{
    /** Draft or published; whoever shows it to students checks that it is published. Null when the group has none. */
    public function __invoke(int $grupoId): ?DatosDelSilabo;
}
