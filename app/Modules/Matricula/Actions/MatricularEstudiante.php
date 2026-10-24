<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\ConsultaGruposDelCiclo;
use App\Modules\Academico\Contracts\ReservaDeVacante;
use App\Modules\Academico\Data\GrupoData;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Events\EstudianteMatriculado;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Contracts\VerificacionDePago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

final readonly class MatricularEstudiante
{
    private const int CANDADO_MATRICULA = 1;

    public function __construct(
        private VerificacionDePago $verificacionDePago,
        private ReservaDeVacante $reservaDeVacante,
        private ConsultaGruposDelCiclo $gruposDelCiclo,
    ) {}

    public function __invoke(int $personaId, int $grupoId, int $pagoId): Matricula
    {
        // Lock order is always person, payment, then group, so concurrent enrollments cannot deadlock.
        return DB::transaction(function () use ($personaId, $grupoId, $pagoId): Matricula {
            // Serializes one person's enrollments: the one-per-cycle-and-period check reads rows no other lock covers.
            DB::select('select pg_advisory_xact_lock(?, ?)', [self::CANDADO_MATRICULA, $personaId]);

            $pago = ($this->verificacionDePago)($pagoId, $personaId, TipoConcepto::Matricula);

            $previa = Matricula::query()->where('pago_id', $pago->id)->first();

            if ($previa !== null) {
                return $this->reintento($previa, $personaId, $grupoId);
            }

            $grupo = ($this->reservaDeVacante)($grupoId);
            $this->asegurarHabilitacion($personaId, $grupo);

            $matricula = Matricula::query()->create([
                'persona_id' => $personaId,
                'grupo_id' => $grupo->id,
                'pago_id' => $pago->id,
                'condicion' => $this->condicion($personaId, $grupo),
                'matriculado_en' => now(),
            ]);

            EstudianteMatriculado::dispatch($matricula->id, $personaId, $grupo->id);

            return $matricula;
        });
    }

    private function reintento(Matricula $previa, int $personaId, int $grupoId): Matricula
    {
        $mismaSolicitud = $previa->estado === EstadoMatricula::Activa
            && $previa->persona_id === $personaId
            && $previa->grupo_id === $grupoId;

        return $mismaSolicitud ? $previa : throw new ReglaDeNegocioIncumplida('El pago ya se usó en otra matrícula.');
    }

    private function asegurarHabilitacion(int $personaId, GrupoData $grupo): void
    {
        $cicloHabilitado = Habilitacion::query()
            ->where(['persona_id' => $personaId, 'idioma_id' => $grupo->idiomaId])
            ->value('ciclo_id');

        $habilitado = $cicloHabilitado === null ? $grupo->cicloNumero === 1 : $cicloHabilitado === $grupo->cicloId;

        if (! $habilitado) {
            throw new ReglaDeNegocioIncumplida('No está habilitado para este ciclo: debe aprobar el ciclo anterior o rendir el examen de clasificación.');
        }
    }

    private function condicion(int $personaId, GrupoData $grupo): CondicionMatricula
    {
        $grupos = collect(($this->gruposDelCiclo)($grupo->cicloId));

        $gruposCursados = Matricula::query()
            ->where(['persona_id' => $personaId, 'estado' => EstadoMatricula::Activa])
            ->whereIn('grupo_id', $grupos->pluck('id'))
            ->pluck('grupo_id');

        if ($gruposCursados->intersect($grupos->where('periodoId', $grupo->periodoId)->pluck('id'))->isNotEmpty()) {
            throw new ReglaDeNegocioIncumplida('Ya tiene una matrícula activa en este ciclo y periodo.');
        }

        return $gruposCursados->isEmpty() ? CondicionMatricula::Regular : CondicionMatricula::Repitente;
    }
}
