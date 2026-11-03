<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Contracts;

interface TramiteDelPago
{
    /** "T-2027-0001 (Constancia de estudios)" for the procedure the payment paid, or null. */
    public function __invoke(int $pagoId): ?string;
}
