<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Support;

use App\Modules\Pagos\Contracts\RecargoDeMatricula;
use App\Modules\Pagos\Data\ReporteDeRecaudacion;
use App\Modules\Pagos\Enums\TipoConcepto;
use Carbon\CarbonInterface;

// What a payment should amount to: the fee, plus the late enrollment surcharge when it applies.
final readonly class MontoEsperado
{
    public function __construct(public string $monto, public ?int $recargo = null) {}

    public static function de(string $tarifa, ?TipoConcepto $tipo, CarbonInterface $fechaPago): self
    {
        $recargo = $tipo === TipoConcepto::Matricula ? app(RecargoDeMatricula::class)($fechaPago) : null;

        return $recargo === null ? new self($tarifa) : new self(self::conRecargo($tarifa, $recargo), $recargo);
    }

    // Integer cents, rounded half up: 33.33 plus 15 % is 38.33, never a float.
    public static function conRecargo(string $tarifa, int $porcentaje): string
    {
        $centimos = ReporteDeRecaudacion::centimosDe($tarifa) * (100 + $porcentaje);

        return ReporteDeRecaudacion::decimal(intdiv($centimos + 50, 100));
    }
}
