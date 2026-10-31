<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Enums;

enum Llamado: string
{
    case Inicio = 'inicio';
    case Cierre = 'cierre';
    case Posterior = 'posterior';
    case Cerrado = 'cerrado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Inicio => 'Llamado de inicio',
            self::Cierre => 'Llamado de cierre',
            self::Posterior => 'Registro posterior',
            self::Cerrado => 'Llamados cerrados',
        };
    }

    public function boton(): ?string
    {
        return $this === self::Cerrado ? null : 'Guardar '.mb_lcfirst($this->etiqueta());
    }

    /**
     * @return list<EstadoAsistencia>
     */
    public function estados(): array
    {
        return $this === self::Cierre ? [EstadoAsistencia::Presente, EstadoAsistencia::Falta] : EstadoAsistencia::cases();
    }
}
