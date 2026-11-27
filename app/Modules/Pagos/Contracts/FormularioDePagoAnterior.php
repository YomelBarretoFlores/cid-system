<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Contracts;

use App\Modules\Pagos\Data\PagoAnteriorAlSistema;
use Filament\Schemas\Components\Component;

// Lets another module's panel form collect a payment made before the system with the payment module's own fields.
interface FormularioDePagoAnterior
{
    /**
     * @return list<Component> fields that read the payer from the form's `persona_id`
     */
    public function campos(): array;

    /**
     * @param  array<string, mixed>  $datos  the form state, including `persona_id`
     */
    public function pago(array $datos): PagoAnteriorAlSistema;
}
