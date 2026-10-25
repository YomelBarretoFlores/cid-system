<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\OfertaDelPeriodo;

interface OfertaPublica
{
    /**
     * The period whose enrollment is open today, or else the next planned one; null when there is neither.
     */
    public function __invoke(): ?OfertaDelPeriodo;
}
