<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Enums;

enum PasoDelEstudiante: string
{
    case SinPago = 'sin_pago';
    case PagoValidado = 'pago_validado';
    case Matriculado = 'matriculado';

    public function alcanzo(self $paso): bool
    {
        return $this->orden() >= $paso->orden();
    }

    private function orden(): int
    {
        return match ($this) {
            self::SinPago => 0,
            self::PagoValidado => 1,
            self::Matriculado => 2,
        };
    }
}
