<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\OfertaDeMatricula;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use Illuminate\Database\Eloquent\Builder;

final readonly class ListarOfertaDeMatricula implements OfertaDeMatricula
{
    public function __invoke(array $ciclosHabilitados): array
    {
        $grupos = Grupo::query()
            ->abiertosParaMatricula()
            ->whereHas('ciclo', fn (Builder $ciclo) => $this->delCicloHabilitado($ciclo, $ciclosHabilitados))
            ->with(FichaDeGrupo::RELACIONES)
            ->get()
            ->sortBy([
                fn (Grupo $a, Grupo $b): int => $a->ciclo->idioma->nombre <=> $b->ciclo->idioma->nombre,
                fn (Grupo $a, Grupo $b): int => $a->codigo <=> $b->codigo,
            ]);

        return array_values($grupos->map(FichaDeGrupo::desde(...))->all());
    }

    /**
     * @param  Builder<Ciclo>  $ciclo
     * @param  array<int, int>  $ciclosHabilitados
     */
    private function delCicloHabilitado(Builder $ciclo, array $ciclosHabilitados): void
    {
        $ciclo->whereHas('idioma', fn (Builder $idioma) => $idioma->where('activo', true))
            ->where(fn (Builder $habilitado) => $habilitado
                ->whereIn('id', array_values($ciclosHabilitados))
                ->orWhere(fn (Builder $primero) => $primero
                    ->where('numero', 1)
                    ->whereNotIn('idioma_id', array_keys($ciclosHabilitados))));
    }
}
