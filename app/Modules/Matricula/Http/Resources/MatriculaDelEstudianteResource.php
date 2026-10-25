<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Http\Resources;

use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Models\Matricula;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{matricula: Matricula, grupo: FichaDeGrupo, docente: ?string} $resource
 */
final class MatriculaDelEstudianteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        ['matricula' => $matricula, 'grupo' => $grupo, 'docente' => $docente] = $this->resource;

        return [
            'id' => $matricula->ulid,
            'estado' => $matricula->estado->value,
            'estado_etiqueta' => $matricula->estado->etiqueta(),
            'condicion' => $matricula->condicion->value,
            'condicion_etiqueta' => $matricula->condicion->etiqueta(),
            'matriculado_en' => $matricula->matriculado_en->setTimezone('America/Lima')->toIso8601String(),
            'retirado_en' => $matricula->retirado_en?->setTimezone('America/Lima')->toIso8601String(),
            'docente' => $docente,
            'grupo' => [
                'codigo' => $grupo->codigo,
                'idioma' => $grupo->idioma,
                'ciclo' => $grupo->cicloNumero,
                'nivel' => $grupo->nivel,
                'modalidad' => $grupo->modalidad,
                'acelerado' => $grupo->esAcelerado,
                'periodo' => $grupo->periodo,
                'horario' => $grupo->horarioCompacto(),
                'aula' => $grupo->aula,
                'enlace_virtual' => $grupo->enlaceVirtual,
            ],
        ];
    }
}
