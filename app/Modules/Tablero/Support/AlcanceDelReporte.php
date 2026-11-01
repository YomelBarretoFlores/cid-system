<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// The groups a report covers (not cancelled, ordered by language, cycle and code) and the filters printed on it.
final readonly class AlcanceDelReporte
{
    public function __construct(
        private CatalogoDePeriodos $catalogoDePeriodos,
        private CatalogoDeIdiomas $catalogoDeIdiomas,
        private HorariosDeGrupos $horariosDeGrupos,
    ) {}

    public function periodo(FiltrosDeReporte $filtros): string
    {
        return ($this->catalogoDePeriodos)()[$filtros->periodoId] ?? throw new ReglaDeNegocioIncumplida('Elige un periodo válido.');
    }

    /**
     * @return list<HorarioDeGrupo>
     */
    public function grupos(FiltrosDeReporte $filtros): array
    {
        $ids = GruposDelPeriodo::consulta($filtros->delTablero())
            ->when($filtros->grupoId, fn ($consulta, int $grupoId) => $consulta->where('g.id', $grupoId))
            ->pluck('g.id')->map(fn (mixed $id): int => (int) $id)->all();
        $grupos = array_values(($this->horariosDeGrupos)(array_values($ids)));
        usort($grupos, fn (HorarioDeGrupo $a, HorarioDeGrupo $b): int => [$a->idioma, $a->cicloNumero, $a->codigo] <=> [$b->idioma, $b->cicloNumero, $b->codigo]);

        return $grupos;
    }

    /**
     * @param  list<HorarioDeGrupo>  $grupos
     * @return array<string, string>
     */
    public function descripcion(FiltrosDeReporte $filtros, array $grupos): array
    {
        $idioma = collect(($this->catalogoDeIdiomas)())->first(fn (IdiomaDelCatalogo $candidato): bool => $candidato->id === $filtros->idiomaId);

        return [
            'Idioma' => $idioma instanceof IdiomaDelCatalogo ? $idioma->nombre : 'Todos',
            'Nivel' => $filtros->nivel?->etiqueta() ?? 'Todos',
            'Grupo' => $filtros->grupoId === null ? 'Todos' : ($grupos[0]->codigo ?? 'Sin grupos'),
        ];
    }
}
