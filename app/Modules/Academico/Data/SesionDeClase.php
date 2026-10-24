<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Models\Horario;

final readonly class SesionDeClase
{
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

    private function segundos(string $hora): int
    {
        [$horas, $minutos, $segundos] = array_map(intval(...), explode(':', $hora.':0:0'));

        return $horas * 3600 + $minutos * 60 + $segundos;
    }
}
