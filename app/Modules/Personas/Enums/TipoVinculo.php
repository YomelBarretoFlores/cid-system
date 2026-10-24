<?php

declare(strict_types=1);

namespace App\Modules\Personas\Enums;

enum TipoVinculo: string
{
    case EstudianteUnasam = 'estudiante_unasam';
    case DocenteAdministrativoUnasam = 'docente_administrativo_unasam';
    case PublicoGeneral = 'publico_general';

    public function etiqueta(): string
    {
        return match ($this) {
            self::EstudianteUnasam => 'Estudiante UNASAM',
            self::DocenteAdministrativoUnasam => 'Docente/Administrativo UNASAM',
            self::PublicoGeneral => 'Público general',
        };
    }
}
