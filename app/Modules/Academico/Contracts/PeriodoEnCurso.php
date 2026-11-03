<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

interface PeriodoEnCurso
{
    /**
     * The period in progress, else the latest one that already started, else the latest one; null without periods.
     */
    public function __invoke(): ?int;
}
