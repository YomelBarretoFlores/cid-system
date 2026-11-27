<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Contracts;

use App\Modules\Pagos\Data\PagoAnteriorAlSistema;
use App\Shared\Auth\Models\User;

interface RegistroDePagoAnterior
{
    /**
     * @return int the id of the payment, stored as validated
     */
    public function __invoke(PagoAnteriorAlSistema $pago, User $actor): int;
}
