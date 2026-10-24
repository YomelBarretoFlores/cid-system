<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Enums;

enum EstadoSolicitudClasificacion: string
{
    case PendienteEvaluacion = 'pendiente_evaluacion';
    case Evaluado = 'evaluado';
    case Expirado = 'expirado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::PendienteEvaluacion => 'Pendiente de evaluación',
            self::Evaluado => 'Evaluado',
            self::Expirado => 'Expirado',
        };
    }
}
