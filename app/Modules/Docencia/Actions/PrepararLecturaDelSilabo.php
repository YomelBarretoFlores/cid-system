<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Data\DatosDelCurso;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Modules\Docencia\Contracts\LecturaDelSilabo;
use App\Modules\Docencia\Contracts\SilaboDelGrupo;
use App\Modules\Docencia\Data\DatosDelSilabo;
use App\Modules\Docencia\Data\SilaboParaLeer;
use App\Shared\Ui\Formato;

final readonly class PrepararLecturaDelSilabo implements LecturaDelSilabo
{
    public function __construct(
        private SilaboDelGrupo $silaboDelGrupo,
        private CursoDelGrupo $cursoDelGrupo,
        private DetalleDeGrupos $detalleDeGrupos,
        private DocenteDelGrupo $docenteDelGrupo,
    ) {}

    public function __invoke(int $grupoId): ?SilaboParaLeer
    {
        $contenido = ($this->silaboDelGrupo)($grupoId);
        $curso = ($this->cursoDelGrupo)($grupoId);

        if (! $contenido instanceof DatosDelSilabo || ! $curso instanceof DatosDelCurso) {
            return null;
        }

        $ficha = ($this->detalleDeGrupos)([$grupoId])[$grupoId] ?? null;

        return new SilaboParaLeer($grupoId, $curso->codigo, "{$curso->idioma} · Ciclo {$curso->cicloNumero}", [
            'Grupo' => $curso->codigo,
            'Idioma' => $curso->idioma,
            'Ciclo' => "{$curso->cicloNumero} · {$curso->cicloNombre}",
            'Nivel' => $curso->nivel->etiqueta(),
            'Periodo' => $curso->periodo.$this->fechas($contenido),
            'Horas lectivas' => (string) $curso->horasLectivas,
            'Horario' => $ficha instanceof FichaDeGrupo && $ficha->sesiones !== [] ? implode(' / ', $ficha->horarioCompacto()) : 'Por confirmar',
            'Aula' => $this->aula($ficha),
            'Docente' => ($this->docenteDelGrupo)($grupoId)->nombreCompleto ?? 'Por asignar',
        ], $contenido);
    }

    // The weeks span the whole period, so their ends are the period dates.
    private function fechas(DatosDelSilabo $contenido): string
    {
        $primera = $contenido->semanas[0] ?? null;
        $ultima = $contenido->semanas[array_key_last($contenido->semanas) ?? 0] ?? null;

        return $primera === null || $ultima === null ? '' : ' ('.Formato::fechaCorta($primera->desde).' al '.Formato::fechaCorta($ultima->hasta).')';
    }

    private function aula(?FichaDeGrupo $ficha): string
    {
        if ($ficha?->aula !== null) {
            return $ficha->aula;
        }

        return $ficha?->enlaceVirtual === null ? 'Por asignar' : 'Clase virtual';
    }
}
