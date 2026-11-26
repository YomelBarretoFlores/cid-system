<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use Carbon\CarbonInterface;

// Whether an enrollment paid or made on that date is late, so Pagos can expect the surcharge.
interface MatriculaExtemporanea
{
    public function __invoke(CarbonInterface $fecha): bool;
}
