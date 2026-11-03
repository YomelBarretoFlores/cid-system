<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\CiclosDeGrupos;
use App\Modules\Academico\Data\GrupoData;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Support\MovimientoDeMatricula;
use App\Shared\Support\Database\Transaccion;

// Passing a cycle enables the next one, and never lowers a cycle the student already reached by another way.
final readonly class HabilitarCiclosSiguientes
{
    public function __construct(
        private CiclosDeGrupos $ciclosDeGrupos,
        private CatalogoDeIdiomas $catalogoDeIdiomas,
    ) {}

    /**
     * @param  list<int>  $matriculasAprobadas
     * @return int habilitations moved forward
     */
    public function __invoke(array $matriculasAprobadas): int
    {
        $matriculas = Matricula::query()->whereKey($matriculasAprobadas)->get(['id', 'persona_id', 'grupo_id']);
        $grupos = ($this->ciclosDeGrupos)(array_values(array_map(intval(...), $matriculas->pluck('grupo_id')->unique()->all())));
        $ordenPorIdioma = collect(($this->catalogoDeIdiomas)())
            ->mapWithKeys(fn (IdiomaDelCatalogo $idioma): array => [$idioma->id => array_keys($idioma->ciclos)]);

        return $matriculas->sum(function (Matricula $matricula) use ($grupos, $ordenPorIdioma): int {
            $grupo = $grupos[$matricula->grupo_id];

            return (int) $this->habilitar($matricula->persona_id, $grupo, $ordenPorIdioma->get($grupo->idiomaId, []));
        });
    }

    /**
     * @param  list<int>  $orden  cycle ids of the language in study order
     */
    private function habilitar(int $personaId, GrupoData $grupo, array $orden): bool
    {
        $siguiente = (int) array_search($grupo->cicloId, $orden, true) + 1;

        if (! isset($orden[$siguiente])) {
            return false;
        }

        return Transaccion::ejecutar(function () use ($personaId, $grupo, $orden, $siguiente): bool {
            MovimientoDeMatricula::bloquearPersona($personaId);
            $actual = Habilitacion::query()->where(['persona_id' => $personaId, 'idioma_id' => $grupo->idiomaId])->lockForUpdate()->first();

            // No row means the first cycle, the lowest position.
            if ($actual !== null && (int) array_search($actual->ciclo_id, $orden, true) >= $siguiente) {
                return false;
            }

            Habilitacion::query()->updateOrCreate(
                ['persona_id' => $personaId, 'idioma_id' => $grupo->idiomaId],
                ['ciclo_id' => $orden[$siguiente], 'origen' => OrigenHabilitacion::Aprobacion, 'motivo' => null, 'actualizado_por' => null],
            );

            return true;
        });
    }
}
