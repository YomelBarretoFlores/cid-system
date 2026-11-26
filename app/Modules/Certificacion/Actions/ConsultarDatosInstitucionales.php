<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Contracts\DatosInstitucionales;
use App\Modules\Certificacion\Data\DatosDeLaInstitucion;
use App\Modules\Certificacion\Models\Institucion;

final readonly class ConsultarDatosInstitucionales implements DatosInstitucionales
{
    // A missing row (a test truncation) falls back to the defaults the migration inserts.
    public function __invoke(): DatosDeLaInstitucion
    {
        $institucion = Institucion::query()->find(Institucion::FILA);

        return $institucion === null ? new DatosDeLaInstitucion : DatosDeLaInstitucion::desde($institucion);
    }
}
