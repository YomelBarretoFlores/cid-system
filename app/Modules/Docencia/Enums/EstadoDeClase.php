<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Enums;

enum EstadoDeClase: string
{
    case RegistroAnterior = 'registro_anterior';
    case LlamadoDeInicio = 'llamado_de_inicio';
    case SinLlamadoDeCierre = 'sin_llamado_de_cierre';
    case PorConfirmar = 'por_confirmar';
    case SinConfirmar = 'sin_confirmar';
    case Confirmada = 'confirmada';
    case Observada = 'observada';
    case RegistroPosterior = 'registro_posterior';

    public function etiqueta(): string
    {
        return match ($this) {
            self::RegistroAnterior => 'registrada',
            self::LlamadoDeInicio => 'con llamado de inicio',
            self::SinLlamadoDeCierre => 'sin llamado de cierre',
            self::PorConfirmar => 'por confirmar',
            self::SinConfirmar => 'sin confirmar',
            self::Confirmada => 'confirmada',
            self::Observada => 'observada',
            self::RegistroPosterior => 'registro posterior',
        };
    }
}
