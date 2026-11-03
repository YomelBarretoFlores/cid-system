<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Enums;

enum EstadoSolicitudClasificacion: string
{
    case PendienteEvaluacion = 'pendiente_evaluacion';
    case Evaluado = 'evaluado';
    case Expirado = 'expirado';
    case PagoAnulado = 'pago_anulado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::PendienteEvaluacion => 'Pendiente de evaluación',
            self::Evaluado => 'Evaluado',
            self::Expirado => 'Expirado',
            self::PagoAnulado => 'Pago anulado',
        };
    }

    // Shared by the panel badges and the portal's x-portal.insignia.
    public function color(): string
    {
        return match ($this) {
            self::PendienteEvaluacion => 'warning',
            self::Evaluado => 'success',
            self::Expirado => 'gray',
            self::PagoAnulado => 'danger',
        };
    }
}
