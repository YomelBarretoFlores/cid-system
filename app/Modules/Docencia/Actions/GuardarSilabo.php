<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Models\Silabo;
use App\Modules\Docencia\Support\RedaccionDelSilabo;

// Saves the teacher's syllabus as typed: a draft may be incomplete, a published one is corrected and stays published.
final readonly class GuardarSilabo
{
    public function __construct(private RedaccionDelSilabo $redaccion) {}

    /**
     * @param  array<mixed>  $datos  texts, `semanas` by week number and `evaluaciones` by component code
     */
    public function __invoke(int $grupoId, int $docenteId, array $datos): Silabo
    {
        return $this->redaccion->escribir($grupoId, $docenteId, $datos);
    }
}
