<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Matricula\Data\AvanceDelEstudiante;
use App\Modules\Matricula\Data\AvanceEnIdioma;
use App\Modules\Matricula\Data\ClaseDeLaSemana;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\PasoDelEstudiante;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Support\OpcionesDeMatricula;
use App\Modules\Pagos\Contracts\ResumenDePagosDePersona;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class ResumirAvanceDelEstudiante
{
    public function __construct(
        private DetalleDeGrupos $detalleDeGrupos,
        private OpcionesDeMatricula $opciones,
        private ResumenDePagosDePersona $resumenDePagos,
        private HorariosDeGrupos $horariosDeGrupos,
    ) {}

    public function __invoke(int $personaId): AvanceDelEstudiante
    {
        $matriculas = Matricula::query()->where('persona_id', $personaId)->orderByDesc('matriculado_en')->orderByDesc('id')->get()
            ->sortByDesc(fn (Matricula $matricula): bool => $matricula->estado === EstadoMatricula::Activa)->values();
        $grupos = ($this->detalleDeGrupos)(array_values(array_map(intval(...), $matriculas->pluck('grupo_id')->unique()->all())));
        $terminados = $this->gruposTerminados(array_map(intval(...), array_keys($grupos)));
        $vigentes = $matriculas->filter(fn (Matricula $matricula): bool => $matricula->estado === EstadoMatricula::Activa && isset($grupos[$matricula->grupo_id]) && ! in_array($matricula->grupo_id, $terminados, true));
        $activos = $vigentes->map(fn (Matricula $matricula): FichaDeGrupo => $grupos[$matricula->grupo_id])->values();
        $pagosParaMatricula = count($this->opciones->pagosDisponibles($personaId));

        return new AvanceDelEstudiante(
            paso: match (true) {
                $activos->isNotEmpty() => PasoDelEstudiante::Matriculado,
                $pagosParaMatricula > 0 => PasoDelEstudiante::PagoValidado,
                default => PasoDelEstudiante::SinPago,
            },
            grupoActual: $activos->first(),
            horario: $this->horario($activos),
            idiomas: $this->idiomas($matriculas, $grupos, $terminados),
            pagos: ($this->resumenDePagos)($personaId),
            pagosParaMatricula: $pagosParaMatricula,
            matriculaActual: $vigentes->first()?->ulid,
        );
    }

    /**
     * @param  Collection<int, FichaDeGrupo>  $activos
     * @return array<int, list<ClaseDeLaSemana>>
     */
    private function horario(Collection $activos): array
    {
        $horario = array_fill(1, 7, []);

        foreach ($activos as $grupo) {
            foreach ($grupo->sesiones as $sesion) {
                $horario[$sesion->diaSemana][] = new ClaseDeLaSemana($grupo->idioma, substr($sesion->horaInicio, 0, 5), $grupo->aulaCodigo);
            }
        }

        return array_map(fn (array $clases): array => array_values(collect($clases)->sortBy('hora')->all()), $horario);
    }

    /**
     * @param  list<int>  $grupoIds
     * @return list<int> groups whose period already ended in Lima: an active enrollment there is a finished cycle
     */
    private function gruposTerminados(array $grupoIds): array
    {
        $hoy = CarbonImmutable::today('America/Lima')->toDateString();

        return array_values(array_map(
            fn (HorarioDeGrupo $horario): int => $horario->grupoId,
            array_filter(($this->horariosDeGrupos)($grupoIds), fn (HorarioDeGrupo $horario): bool => $horario->periodoFin->toDateString() < $hoy),
        ));
    }

    /**
     * @param  Collection<int, Matricula>  $matriculas
     * @param  array<int, FichaDeGrupo>  $grupos
     * @param  list<int>  $terminados
     * @return list<AvanceEnIdioma>
     */
    private function idiomas(Collection $matriculas, array $grupos, array $terminados): array
    {
        return array_values($matriculas
            ->map(fn (Matricula $matricula): ?AvanceEnIdioma => isset($grupos[$matricula->grupo_id]) ? new AvanceEnIdioma(
                $grupos[$matricula->grupo_id]->idioma,
                $grupos[$matricula->grupo_id]->cicloNumero,
                $matricula->estado,
                $grupos[$matricula->grupo_id]->periodo,
                $matricula->estado === EstadoMatricula::Activa && in_array($matricula->grupo_id, $terminados, true),
            ) : null)
            ->filter()->unique('idioma')->all());
    }
}
