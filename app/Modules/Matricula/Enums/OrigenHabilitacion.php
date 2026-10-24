<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Enums;

enum OrigenHabilitacion: string
{
    case Clasificacion = 'clasificacion';
    case Aprobacion = 'aprobacion';
    case AjusteAdministrativo = 'ajuste_administrativo';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Clasificacion => 'Examen de clasificación',
            self::Aprobacion => 'Aprobación del ciclo anterior',
            self::AjusteAdministrativo => 'Ajuste administrativo',
        };
    }
}
