<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Contracts;

use App\Modules\Pagos\Data\TarifaVigente;
use Carbon\CarbonInterface;

interface TarifasVigentes
{
    /**
     * Fees of the active concepts in force on the date, by concept name and link.
     *
     * @return list<TarifaVigente>
     */
    public function __invoke(CarbonInterface $fecha): array;
}
