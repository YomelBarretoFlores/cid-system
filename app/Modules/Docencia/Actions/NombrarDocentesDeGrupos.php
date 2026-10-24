<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Personas\Contracts\FichasDePersonas;

final readonly class NombrarDocentesDeGrupos implements DocentesDeGrupos
{
    public function __construct(private FichasDePersonas $fichasDePersonas) {}

    public function __invoke(array $grupoIds): array
    {
        $cargas = CargaAcademica::query()
            ->join('doc_docentes', 'doc_docentes.id', '=', 'doc_cargas_academicas.docente_id')
            ->whereIn('doc_cargas_academicas.grupo_id', $grupoIds)
            ->pluck('doc_docentes.persona_id', 'doc_cargas_academicas.grupo_id');
        $fichas = ($this->fichasDePersonas)(array_values(array_map(intval(...), $cargas->unique()->all())));

        return array_filter(
            $cargas->mapWithKeys(fn (int $personaId, int $grupoId): array => [$grupoId => $fichas[$personaId]->nombreCompleto ?? ''])->all(),
            fn (string $nombre): bool => $nombre !== '',
        );
    }
}
