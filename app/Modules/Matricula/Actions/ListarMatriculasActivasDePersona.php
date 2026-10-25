<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Contracts\MatriculasActivasDePersona;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Models\Matricula;

final readonly class ListarMatriculasActivasDePersona implements MatriculasActivasDePersona
{
    public function __construct(private DetalleDeGrupos $detalleDeGrupos) {}

    public function __invoke(int $personaId): array
    {
        $matriculas = Matricula::query()
            ->where(['persona_id' => $personaId, 'estado' => EstadoMatricula::Activa])
            ->orderByDesc('matriculado_en')
            ->pluck('grupo_id', 'id');
        $grupos = ($this->detalleDeGrupos)(array_values(array_unique(array_map(intval(...), $matriculas->all()))));

        return $matriculas
            ->map(fn (int $grupoId): string => $this->etiqueta($grupos[$grupoId] ?? null))
            ->all();
    }

    private function etiqueta(?FichaDeGrupo $grupo): string
    {
        return $grupo instanceof FichaDeGrupo ? "{$grupo->codigo} · {$grupo->idioma} ciclo {$grupo->cicloNumero} · {$grupo->periodo}" : '—';
    }
}
