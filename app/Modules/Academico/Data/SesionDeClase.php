<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Models\Horario;

final readonly class SesionDeClase
{
    public const array DIAS_CORTOS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];

    public function __construct(
        public int $diaSemana,
        public string $horaInicio,
        public string $horaFin,
    ) {}

    public static function desde(Horario $horario): self
    {
        return new self($horario->dia_semana, (string) $horario->hora_inicio, (string) $horario->hora_fin);
    }

    public function seCruzaCon(self $otra): bool
    {
        return $this->diaSemana === $otra->diaSemana && $this->horaInicio < $otra->horaFin && $this->horaFin > $otra->horaInicio;
    }

    public function minutos(): int
    {
        return intdiv($this->segundos($this->horaFin) - $this->segundos($this->horaInicio), 60);
    }

    public function dia(): string
    {
        return Horario::DIAS[$this->diaSemana] ?? (string) $this->diaSemana;
    }

    public function descripcion(): string
    {
        return sprintf('%s de %s a %s', $this->dia(), substr($this->horaInicio, 0, 5), substr($this->horaFin, 0, 5));
    }

    /**
     * Days that share the same hours go on one line: "Lun · Mié · Vie, 07:00–08:30".
     *
     * @param  list<self>  $sesiones
     * @return list<string>
     */
    public static function compactar(array $sesiones): array
    {
        return array_map(fn (array $bloque): string => "{$bloque['dias']}, {$bloque['hora']}", self::agrupar($sesiones));
    }

    /**
     * The same grouping split into days and hours: ['dias' => 'Lun · Mié · Vie', 'hora' => '07:00–08:30'].
     *
     * @param  list<self>  $sesiones
     * @return list<array{dias: string, hora: string}>
     */
    public static function agrupar(array $sesiones): array
    {
        usort($sesiones, fn (self $una, self $otra): int => [$una->horaInicio, $una->diaSemana] <=> [$otra->horaInicio, $otra->diaSemana]);
        $porHorario = [];

        foreach ($sesiones as $sesion) {
            $porHorario[$sesion->horas()][] = self::DIAS_CORTOS[$sesion->diaSemana] ?? (string) $sesion->diaSemana;
        }

        return array_map(fn (string $horas, array $dias): array => ['dias' => implode(' · ', $dias), 'hora' => $horas], array_keys($porHorario), $porHorario);
    }

    private function horas(): string
    {
        return substr($this->horaInicio, 0, 5).'–'.substr($this->horaFin, 0, 5);
    }

    private function segundos(string $hora): int
    {
        [$horas, $minutos, $segundos] = array_map(intval(...), explode(':', $hora.':0:0'));

        return $horas * 3600 + $minutos * 60 + $segundos;
    }
}
