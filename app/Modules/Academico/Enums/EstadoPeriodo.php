<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

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

    public function color(): string
    {
        return match ($this) {
            self::Planificado => 'gray',
            self::MatriculaAbierta => 'success',
            self::EnCurso => 'info',
            self::Cerrado => 'warning',
        };
    }

    public function siguiente(): ?self
    {
        return $this->destinosPermitidos()[0] ?? null;
    }

    public function puedeTransitarA(self $destino): bool
    {
        return in_array($destino, $this->destinosPermitidos(), true);
    }

    public function asegurarTransicionA(self $destino): void
    {
        if (! $this->puedeTransitarA($destino)) {
            throw new ReglaDeNegocioIncumplida("Un periodo {$this->etiqueta()} no puede pasar a {$destino->etiqueta()}.");
        }
    }

    /**
     * @return list<self>
     */
    private function destinosPermitidos(): array
    {
        return match ($this) {
            self::Planificado => [self::MatriculaAbierta],
            self::MatriculaAbierta => [self::EnCurso],
            self::EnCurso => [self::Cerrado],
            self::Cerrado => [],
        };
    }
}
