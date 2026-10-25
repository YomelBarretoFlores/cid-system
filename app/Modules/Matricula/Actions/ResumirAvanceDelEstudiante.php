<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Data\AvanceDelEstudiante;
use App\Modules\Matricula\Data\AvanceEnIdioma;
use App\Modules\Matricula\Data\ClaseDeLaSemana;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\PasoDelEstudiante;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Contracts\PagosValidadosDePersona;
use App\Modules\Pagos\Data\PagoValidado;
use App\Modules\Pagos\Enums\TipoConcepto;
use Illuminate\Support\Collection;

final readonly class ResumirAvanceDelEstudiante
{
    public function __construct(
        private DetalleDeGrupos $detalleDeGrupos,
        private PagosValidadosDePersona $pagosValidados,
    ) {}

    public function __invoke(int $personaId): AvanceDelEstudiante
    {
        $matriculas = Matricula::query()->where('persona_id', $personaId)->orderByDesc('matriculado_en')->orderByDesc('id')->get()
            ->sortByDesc(fn (Matricula $matricula): bool => $matricula->estado === EstadoMatricula::Activa)->values();
        $grupos = ($this->detalleDeGrupos)(array_values(array_map(intval(...), $matriculas->pluck('grupo_id')->unique()->all())));
        $activos = $matriculas->filter(fn (Matricula $matricula): bool => $matricula->estado === EstadoMatricula::Activa)
            ->map(fn (Matricula $matricula): ?FichaDeGrupo => $grupos[$matricula->grupo_id] ?? null)->filter()->values();

        return new AvanceDelEstudiante(
            paso: $this->paso($personaId, $activos->isNotEmpty(), $matriculas),
            grupoActual: $activos->first(),
            horario: $this->horario($activos),
            idiomas: $this->idiomas($matriculas, $grupos),
        );
    }

    /**
     * @param  Collection<int, Matricula>  $matriculas
     */
    private function paso(int $personaId, bool $matriculado, Collection $matriculas): PasoDelEstudiante
    {
        if ($matriculado) {
            return PasoDelEstudiante::Matriculado;
        }

        $usados = $matriculas->pluck('pago_id')->filter()->all();
        $disponibles = array_filter(($this->pagosValidados)($personaId, TipoConcepto::Matricula), fn (PagoValidado $pago): bool => ! in_array($pago->id, $usados, true));

        return $disponibles === [] ? PasoDelEstudiante::SinPago : PasoDelEstudiante::PagoValidado;
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
     * @param  Collection<int, Matricula>  $matriculas
     * @param  array<int, FichaDeGrupo>  $grupos
     * @return list<AvanceEnIdioma>
     */
    private function idiomas(Collection $matriculas, array $grupos): array
    {
        return array_values($matriculas
            ->map(fn (Matricula $matricula): ?AvanceEnIdioma => isset($grupos[$matricula->grupo_id]) ? new AvanceEnIdioma(
                $grupos[$matricula->grupo_id]->idioma,
                $grupos[$matricula->grupo_id]->cicloNumero,
                $matricula->estado,
                $grupos[$matricula->grupo_id]->periodo,
            ) : null)
            ->filter()->unique('idioma')->all());
    }
}
