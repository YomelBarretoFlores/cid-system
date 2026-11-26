<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Enums;

enum OrigenHabilitacion: string
{
    case Clasificacion = 'clasificacion';
    case Aprobacion = 'aprobacion';
    case AjusteAdministrativo = 'ajuste_administrativo';
    case Suficiencia = 'suficiencia';
    case Reclasificacion = 'reclasificacion';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Clasificacion => 'Examen de clasificación',
            self::Aprobacion => 'Aprobación del ciclo anterior',
            self::AjusteAdministrativo => 'Ajuste administrativo',
            self::Suficiencia => 'Examen de suficiencia',
            self::Reclasificacion => 'Reclasificación',
        };
    }
}
