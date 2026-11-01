<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Contracts\VerificacionDeCargaDocente;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Runs inside the transaction that changed a period's dates: its groups may now overlap groups of other periods
 * in a classroom or through their teacher. Only periods the new dates start to overlap count, so a classroom share
 * saved before this rule does not block the edit. Same lock order as a schedule change: group, then classroom, then teacher.
 */
final readonly class VerificarCrucesDelPeriodo
{
    public function __construct(private VerificacionDeCargaDocente $verificacionDeCargaDocente) {}

    public function __invoke(int $periodoId, CarbonInterface $desdeAnterior, CarbonInterface $hastaAnterior): void
    {
        $periodo = Periodo::query()->findOrFail($periodoId);
        $grupos = Grupo::query()
            ->where('periodo_id', $periodo->id)
            ->where('estado', '!=', EstadoGrupo::Cancelado->value)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($grupos as $grupo) {
            $this->asegurarAulaLibre($grupo, $periodo, $desdeAnterior, $hastaAnterior);
            ($this->verificacionDeCargaDocente)($grupo->id);
        }
    }

    private function asegurarAulaLibre(Grupo $grupo, Periodo $periodo, CarbonInterface $desdeAnterior, CarbonInterface $hastaAnterior): void
    {
        if ($grupo->aula_id === null) {
            return;
        }

        $aula = Aula::query()->whereKey($grupo->aula_id)->lockForUpdate()->firstOrFail();

        foreach ($grupo->horarios as $horario) {
            $choque = Horario::query()
                ->with('grupo')
                ->ocupanAula($aula->id, $periodo, $grupo->id)
                ->whereDoesntHave('grupo.periodo', fn (Builder $antes): Builder => $antes
                    ->where('fecha_inicio', '<=', $hastaAnterior)
                    ->where('fecha_fin', '>=', $desdeAnterior))
                ->seCruzaCon($horario->dia_semana, $horario->hora_inicio, $horario->hora_fin)
                ->first();

            if ($choque !== null) {
                throw new ReglaDeNegocioIncumplida("El aula {$aula->codigo} ya está ocupada por el grupo {$choque->grupo->codigo} el {$choque->descripcion}, en un periodo que se cruza con las nuevas fechas del grupo {$grupo->codigo}.");
            }
        }
    }
}
