<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Data\DatosDelCurso;
use App\Modules\Certificacion\Contracts\BoletaDeNotas;
use App\Modules\Certificacion\Data\FilaDeLaBoleta;
use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Support\HistorialDeNotas;

// A closed acta gives the grade with the course as it was closed; otherwise the current course and no grade yet.
final readonly class ArmarBoletaDeNotas implements BoletaDeNotas
{
    public function __construct(private CursoDelGrupo $cursoDelGrupo) {}

    public function __invoke(array $grupoPorMatricula): array
    {
        $cerradas = HistorialDeNotas::deMatriculas(array_keys($grupoPorMatricula));
        $filas = [];

        foreach ($grupoPorMatricula as $matriculaId => $grupoId) {
            $fila = isset($cerradas[$matriculaId]) ? $this->cerrada($cerradas[$matriculaId]) : $this->abierta($matriculaId, $grupoId);

            if ($fila instanceof FilaDeLaBoleta) {
                $filas[] = $fila;
            }
        }

        return $filas;
    }

    private function cerrada(NotaDelHistorial $nota): FilaDeLaBoleta
    {
        return new FilaDeLaBoleta($nota->matriculaId, $nota->grupoCodigo, $nota->idioma, $nota->cicloNumero, $nota->cicloNombre, $nota->horasLectivas, $nota->promedio, $nota->estadoFinal);
    }

    private function abierta(int $matriculaId, int $grupoId): ?FilaDeLaBoleta
    {
        $curso = ($this->cursoDelGrupo)($grupoId);

        return $curso instanceof DatosDelCurso ? new FilaDeLaBoleta($matriculaId, $curso->codigo, $curso->idioma, $curso->cicloNumero, $curso->cicloNombre, $curso->horasLectivas, null, null) : null;
    }
}
