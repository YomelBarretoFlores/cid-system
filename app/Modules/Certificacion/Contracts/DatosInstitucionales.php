<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

use App\Modules\Certificacion\Data\DatosDeLaInstitucion;

// The texts official documents print in their header and footer, the city before the date and the number patterns.
interface DatosInstitucionales
{
    public function __invoke(): DatosDeLaInstitucion;
}
