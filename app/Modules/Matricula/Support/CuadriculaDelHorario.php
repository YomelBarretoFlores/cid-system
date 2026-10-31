<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Academico\Data\SesionDeClase;
use App\Modules\Matricula\Data\CeldaDelHorario;
use App\Modules\Matricula\Data\FranjaDelHorario;

// The grid of the SGA "Horario personalizado": 30-minute rows, each class repeated on every row it covers, and a long empty stretch shown as one grey row.
final class CuadriculaDelHorario
{
    private const int FRANJA = 30;

    private const int HUECO_QUE_SE_RESUME = 120;

    private const array DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    /**
     * @param  list<array{CeldaDelHorario, SesionDeClase}>  $clases
     * @return array<int, string>
     */
    public function dias(array $clases): array
    {
        $usaDomingo = collect($clases)->contains(fn (array $clase): bool => $clase[1]->diaSemana === 7);

        return array_slice(self::DIAS, 0, $usaDomingo ? 7 : 6, true);
    }

    /**
     * @param  list<array{CeldaDelHorario, SesionDeClase}>  $clases
     * @return list<FranjaDelHorario>
     */
    public function franjas(array $clases): array
    {
        if ($clases === []) {
            return [];
        }

        $desde = intdiv(min(array_map(fn (array $clase): int => $this->minutos($clase[1]->horaInicio), $clases)), self::FRANJA) * self::FRANJA;
        $hasta = max(array_map(fn (array $clase): int => $this->minutos($clase[1]->horaFin), $clases));
        $franjas = [];
        $vacias = [];

        for ($inicio = $desde; $inicio < $hasta; $inicio += self::FRANJA) {
            $celdas = $this->celdasEntre($clases, $inicio, $inicio + self::FRANJA);

            if ($celdas === []) {
                $vacias[] = new FranjaDelHorario($this->rotulo($inicio));

                continue;
            }

            array_push($franjas, ...$this->hueco($vacias), ...[new FranjaDelHorario($this->rotulo($inicio), $celdas)]);
            $vacias = [];
        }

        return $franjas;
    }

    /**
     * @param  list<FranjaDelHorario>  $vacias
     * @return list<FranjaDelHorario>
     */
    private function hueco(array $vacias): array
    {
        return count($vacias) * self::FRANJA >= self::HUECO_QUE_SE_RESUME ? [new FranjaDelHorario('', sinClases: true)] : $vacias;
    }

    /**
     * @param  list<array{CeldaDelHorario, SesionDeClase}>  $clases
     * @return array<int, list<CeldaDelHorario>>
     */
    private function celdasEntre(array $clases, int $inicio, int $fin): array
    {
        $celdas = [];

        foreach ($clases as [$celda, $sesion]) {
            if ($this->minutos($sesion->horaInicio) < $fin && $this->minutos($sesion->horaFin) > $inicio) {
                $celdas[$sesion->diaSemana][] = $celda;
            }
        }

        ksort($celdas);

        return $celdas;
    }

    private function rotulo(int $inicio): string
    {
        $fin = $inicio + self::FRANJA;

        return sprintf('%02d:%02d - %02d:%02d', intdiv($inicio, 60), $inicio % 60, intdiv($fin, 60), $fin % 60);
    }

    private function minutos(string $hora): int
    {
        return (int) substr($hora, 0, 2) * 60 + (int) substr($hora, 3, 2);
    }
}
