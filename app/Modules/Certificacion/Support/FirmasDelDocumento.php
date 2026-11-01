<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Certificacion\Enums\GrupoDeFirma;
use App\Modules\Certificacion\Models\Firmante;
use Carbon\CarbonInterface;

final class FirmasDelDocumento
{
    /**
     * The signers in force on the date, as the signature block prints them.
     *
     * @return list<array{nombre: string, cargo: string}>
     */
    public static function de(GrupoDeFirma $grupo, CarbonInterface $fecha): array
    {
        return array_values(Firmante::query()->firmanEn($grupo, $fecha)->get()
            ->map(fn (Firmante $firmante): array => ['nombre' => $firmante->nombre, 'cargo' => $firmante->cargo])
            ->all());
    }
}
