<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\CiclosDeGrupos;
use App\Modules\Docencia\Enums\EstadoSilabo;
use App\Modules\Docencia\Models\EvaluacionDeSilabo;
use App\Modules\Docencia\Models\SemanaDeSilabo;
use App\Modules\Docencia\Models\Silabo;
use App\Modules\Docencia\Support\ContenidoDelSilabo;
use App\Modules\Docencia\Support\RedaccionDelSilabo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Replaces the group's syllabus with a published one of the same cycle (any teacher): weeks match by number, evaluation rows by component code.
final readonly class CopiarSilabo
{
    public function __construct(
        private RedaccionDelSilabo $redaccion,
        private CiclosDeGrupos $ciclosDeGrupos,
    ) {}

    public function __invoke(int $grupoId, int $docenteId, int $silaboOrigenId): Silabo
    {
        return $this->redaccion->escribir($grupoId, $docenteId, function (int $semanas, array $codigos) use ($grupoId, $silaboOrigenId): array {
            $origen = $this->origen($grupoId, $silaboOrigenId);

            return [
                ...$origen->only(array_keys(ContenidoDelSilabo::TEXTOS)),
                'semanas' => $origen->semanas->where('semana', '<=', $semanas)
                    ->mapWithKeys(fn (SemanaDeSilabo $semana): array => [$semana->semana => $semana->only(array_keys(ContenidoDelSilabo::CAMPOS_DE_SEMANA))])->all(),
                'evaluaciones' => $origen->evaluaciones->whereIn('componente_codigo', $codigos)
                    ->mapWithKeys(fn (EvaluacionDeSilabo $evaluacion): array => [$evaluacion->componente_codigo => [
                        'indicadores' => $evaluacion->indicadores,
                        'instrumento' => $evaluacion->instrumento,
                        'semana' => $evaluacion->semana !== null && $evaluacion->semana <= $semanas ? $evaluacion->semana : null,
                    ]])->all(),
            ];
        });
    }

    private function origen(int $grupoId, int $silaboOrigenId): Silabo
    {
        $origen = Silabo::query()->with(['semanas', 'evaluaciones'])->whereKey($silaboOrigenId)->where('estado', EstadoSilabo::Publicado)->first()
            ?? throw new ReglaDeNegocioIncumplida('Elige un sílabo publicado para copiarlo.');
        $ciclos = ($this->ciclosDeGrupos)([$grupoId, $origen->grupo_id]);

        if ($origen->grupo_id === $grupoId || ($ciclos[$grupoId]->cicloId ?? 0) !== ($ciclos[$origen->grupo_id]->cicloId ?? -1)) {
            throw new ReglaDeNegocioIncumplida('Solo se copia el sílabo publicado de otro grupo del mismo ciclo.');
        }

        return $origen;
    }
}
