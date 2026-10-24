<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Data\SesionDeClase;
use Carbon\CarbonImmutable;

final readonly class CargaHoraria
{
    public function __construct(
        public int $grupoId,
        public string $codigo,
        public string $idioma,
        public string $nivel,
        public string $periodo,
        public CarbonImmutable $inicio,
        public CarbonImmutable $fin,
        public int $semanas,
        public string $dias,
        public int $minutos,
    ) {}

    public static function de(HorarioDeGrupo $grupo): self
    {
        $sesiones = $grupo->sesiones;
        usort($sesiones, fn (SesionDeClase $a, SesionDeClase $b): int => $a->diaSemana <=> $b->diaSemana);

        return new self(
            grupoId: $grupo->grupoId,
            codigo: $grupo->codigo,
            idioma: $grupo->idioma,
            nivel: $grupo->nivel->etiqueta(),
            periodo: $grupo->periodo,
            inicio: $grupo->periodoInicio,
            fin: $grupo->periodoFin,
            semanas: intdiv(self::diasEntre($grupo->periodoInicio, $grupo->periodoFin) + 7, 7),
            dias: implode(', ', array_values(array_unique(array_map(fn (SesionDeClase $sesion): string => $sesion->dia(), $sesiones)))),
            minutos: array_sum(array_map(fn (SesionDeClase $sesion): int => self::ocurrencias($grupo, $sesion->diaSemana) * $sesion->minutos(), $sesiones)),
        );
    }

    public function horas(): string
    {
        return self::formatearHoras($this->minutos);
    }

    // Hours are shown with up to two decimals using integer arithmetic only.
    public static function formatearHoras(int $minutos): string
    {
        $centesimas = intdiv($minutos * 100 + 30, 60);

        return rtrim(rtrim(sprintf('%d.%02d', intdiv($centesimas, 100), $centesimas % 100), '0'), '.');
    }

    private static function ocurrencias(HorarioDeGrupo $grupo, int $diaSemana): int
    {
        $primera = $grupo->periodoInicio->addDays(($diaSemana - $grupo->periodoInicio->dayOfWeekIso + 7) % 7);

        return $primera->gt($grupo->periodoFin) ? 0 : intdiv(self::diasEntre($primera, $grupo->periodoFin), 7) + 1;
    }

    private static function diasEntre(CarbonImmutable $desde, CarbonImmutable $hasta): int
    {
        return (int) $desde->diffInDays($hasta);
    }
}
