<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Docencia\Contracts\VerificacionDeCargaDocente;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

final readonly class GuardarHorario
{
    public function __construct(private VerificacionDeCargaDocente $verificacionDeCargaDocente) {}

    public function __invoke(int $grupoId, int $diaSemana, string $horaInicio, string $horaFin, ?int $horarioId = null): Horario
    {
        $inicio = self::normalizarHora($horaInicio);
        $fin = self::normalizarHora($horaFin);
        $this->asegurarFranjaValida($diaSemana, $inicio, $fin);

        return DB::transaction(function () use ($grupoId, $diaSemana, $inicio, $fin, $horarioId): Horario {
            $grupo = Grupo::query()->whereKey($grupoId)->lockForUpdate()->firstOrFail();

            if ($grupo->estado->esTerminal()) {
                throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} está {$grupo->estado->etiqueta()} y ya no admite cambios.");
            }

            $horario = $horarioId === null
                ? new Horario(['grupo_id' => $grupo->id])
                : Horario::query()->where('grupo_id', $grupo->id)->findOrFail($horarioId);

            $this->asegurarSinCruceEnElGrupo($grupo, $horario, $diaSemana, $inicio, $fin);
            $this->asegurarAulaLibre($grupo, $diaSemana, $inicio, $fin);

            $horario->fill(['dia_semana' => $diaSemana, 'hora_inicio' => $inicio, 'hora_fin' => $fin])->save();
            ($this->verificacionDeCargaDocente)($grupo->id);

            return $horario;
        });
    }

    public static function normalizarHora(string $hora): string
    {
        return strlen($hora) === 5 ? "{$hora}:00" : $hora;
    }

    private function asegurarFranjaValida(int $diaSemana, string $inicio, string $fin): void
    {
        if (! array_key_exists($diaSemana, Horario::DIAS)) {
            throw new ReglaDeNegocioIncumplida('El día debe estar entre 1 (lunes) y 7 (domingo).');
        }

        if ($fin <= $inicio) {
            throw new ReglaDeNegocioIncumplida('La hora de fin debe ser posterior a la de inicio.');
        }
    }

    private function asegurarSinCruceEnElGrupo(Grupo $grupo, Horario $horario, int $diaSemana, string $inicio, string $fin): void
    {
        $cruce = Horario::query()
            ->where('grupo_id', $grupo->id)
            ->when($horario->exists, fn ($query) => $query->whereKeyNot($horario->getKey()))
            ->seCruzaCon($diaSemana, $inicio, $fin)
            ->first();

        if ($cruce !== null) {
            throw new ReglaDeNegocioIncumplida("El horario se cruza con otra sesión del grupo el {$cruce->descripcion}.");
        }
    }

    private function asegurarAulaLibre(Grupo $grupo, int $diaSemana, string $inicio, string $fin): void
    {
        if ($grupo->aula_id === null) {
            return;
        }

        // Locking the classroom serializes schedule changes of every group that uses it.
        $aula = Aula::query()->whereKey($grupo->aula_id)->lockForUpdate()->firstOrFail();
        $choque = Horario::query()
            ->with('grupo')
            ->ocupanAula($aula->id, $grupo->periodo_id, $grupo->id)
            ->seCruzaCon($diaSemana, $inicio, $fin)
            ->first();

        if ($choque !== null) {
            throw new ReglaDeNegocioIncumplida("El aula {$aula->codigo} ya está ocupada por el grupo {$choque->grupo->codigo} el {$choque->descripcion}.");
        }
    }
}
