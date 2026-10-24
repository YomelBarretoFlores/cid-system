<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

enum EstadoPeriodo: string
{
    case Planificado = 'planificado';
    case MatriculaAbierta = 'matricula_abierta';
    case EnCurso = 'en_curso';
    case Cerrado = 'cerrado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Planificado => 'Planificado',
            self::MatriculaAbierta => 'Matrícula abierta',
            self::EnCurso => 'En curso',
            self::Cerrado => 'Cerrado',
        };
    }
}
