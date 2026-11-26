<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Pagos\Contracts\ConsultaDePago;
use App\Modules\Personas\Models\Persona;

final readonly class HabilitarCuentaPorPago
{
    public function __construct(private ConsultaDePago $consultaDePago) {}

    public function __invoke(int $pagoId): void
    {
        Persona::query()
            ->whereKey(($this->consultaDePago)($pagoId)->personaId)
            ->whereNull('habilitada_en')
            ->update(['habilitada_en' => now()]);
    }
}
