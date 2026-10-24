<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Enums;

enum TipoConcepto: string
{
    case Matricula = 'matricula';
    case Pension = 'pension';
    case ExamenClasificacion = 'examen_clasificacion';
    case Certificado = 'certificado';
    case Constancia = 'constancia';
    case Tramite = 'tramite';
    case Otro = 'otro';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Matricula => 'Matrícula',
            self::Pension => 'Pensión',
            self::ExamenClasificacion => 'Examen de clasificación',
            self::Certificado => 'Certificado',
            self::Constancia => 'Constancia',
            self::Tramite => 'Trámite',
            self::Otro => 'Otro',
        };
    }
}
