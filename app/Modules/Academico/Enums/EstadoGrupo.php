<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

enum EstadoGrupo: string
{
    case Programado = 'programado';
    case Abierto = 'abierto';
    case Cerrado = 'cerrado';
    case Cancelado = 'cancelado';
    case Finalizado = 'finalizado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Programado => 'Programado',
            self::Abierto => 'Abierto',
            self::Cerrado => 'Cerrado',
            self::Cancelado => 'Cancelado',
            self::Finalizado => 'Finalizado',
        };
    }
}
