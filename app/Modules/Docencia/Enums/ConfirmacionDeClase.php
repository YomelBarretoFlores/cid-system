<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Enums;

enum ConfirmacionDeClase: string
{
    case Pendiente = 'pendiente';
    case Confirmada = 'confirmada';
    case Observada = 'observada';
}
