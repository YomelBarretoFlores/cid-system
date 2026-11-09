<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Enums;

enum TipoDeRequisito: string
{
    case Periodo = 'periodo';
    case Ciclo = 'ciclo';
    case Pago = 'pago';
    case Grupos = 'grupos';
    case Unicidad = 'unicidad';
}
