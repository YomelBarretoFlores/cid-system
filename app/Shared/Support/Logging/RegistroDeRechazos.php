<?php

declare(strict_types=1);

namespace App\Shared\Support\Logging;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Log;

final class RegistroDeRechazos
{
    // Messages may quote a DNI, RUC, account number or email; logs must not keep personal data (Ley 29733).
    public static function registrar(ReglaDeNegocioIncumplida $error): void
    {
        $regla = (string) preg_replace(
            ['/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', '/\d(?:[\s-]?\d){7,}/'],
            ['[correo]', '[dato]'],
            $error->getMessage(),
        );

        Log::info('Regla de negocio incumplida.', ['regla' => $regla]);
    }
}
