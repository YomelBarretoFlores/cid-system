<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

use App\Modules\Docencia\Data\SilaboParaLeer;

interface LecturaDelSilabo
{
    /** The syllabus with section I filled from the group; draft or published, so a student-facing caller checks `estaPublicado()`. */
    public function __invoke(int $grupoId): ?SilaboParaLeer;
}
