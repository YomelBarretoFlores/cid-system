<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Academico\Contracts\CatalogoDeGrupos;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Docencia\Enums\EstadoSilabo;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\Silabo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

// Academico lists groups but not by period, so the coordination page reads them all once per request and keeps the period's.
/**
 * @phpstan-type Fila array{grupo_id: int, codigo: string, idioma_id: int, idioma: string, ciclo: int, nivel: string, docente_id: ?int, docente: ?string, estado: ?EstadoSilabo, publicado_en: ?CarbonImmutable}
 */
final class SilabosDelPeriodo
{
    /** @var array<int, HorarioDeGrupo>|null */
    private ?array $grupos = null;

    public function __construct(
        private readonly CatalogoDeGrupos $catalogoDeGrupos,
        private readonly HorariosDeGrupos $horariosDeGrupos,
        private readonly NombresDelControlDeClases $nombres,
    ) {}

    // The period in progress, else the latest one that already started, else the latest one.
    public function periodoPorDefecto(): ?int
    {
        $hoy = CarbonImmutable::today('America/Lima');
        $grupos = collect($this->grupos())->sortByDesc(fn (HorarioDeGrupo $grupo): string => $grupo->periodoInicio->toDateString());

        $grupo = $grupos->first(fn (HorarioDeGrupo $grupo): bool => $grupo->periodoInicio->lte($hoy) && $grupo->periodoFin->gte($hoy))
            ?? $grupos->first(fn (HorarioDeGrupo $grupo): bool => $grupo->periodoInicio->lte($hoy))
            ?? $grupos->first();

        return $grupo?->periodoId;
    }

    /**
     * Groups of the period that hold classes (cancelled ones need no syllabus), by language, cycle and code.
     *
     * @return Collection<int, Fila>
     */
    public function filas(int $periodoId): Collection
    {
        $grupos = collect($this->grupos())->filter(fn (HorarioDeGrupo $grupo): bool => $grupo->periodoId === $periodoId && $grupo->estado !== EstadoGrupo::Cancelado);
        $ids = array_values($grupos->keys()->all());
        $silabos = Silabo::query()->whereIntegerInRaw('grupo_id', $ids)->get(['grupo_id', 'estado', 'publicado_en'])->keyBy('grupo_id');
        $docentes = CargaAcademica::query()->whereIntegerInRaw('grupo_id', $ids)->pluck('docente_id', 'grupo_id')->map(intval(...));
        $nombres = $this->nombres->docentes(array_values($docentes->unique()->all()));

        return $grupos
            ->sortBy([fn (HorarioDeGrupo $a, HorarioDeGrupo $b): int => [$a->idioma, $a->cicloNumero, $a->codigo] <=> [$b->idioma, $b->cicloNumero, $b->codigo]])
            ->mapWithKeys(function (HorarioDeGrupo $grupo) use ($silabos, $docentes, $nombres): array {
                /** @var Silabo|null $silabo */
                $silabo = $silabos->get($grupo->grupoId);
                $docenteId = $docentes->get($grupo->grupoId);

                return [$grupo->grupoId => [
                    'grupo_id' => $grupo->grupoId,
                    'codigo' => $grupo->codigo,
                    'idioma_id' => $grupo->idiomaId,
                    'idioma' => $grupo->idioma,
                    'ciclo' => $grupo->cicloNumero,
                    'nivel' => $grupo->nivel->etiqueta(),
                    'docente_id' => $docenteId,
                    'docente' => $docenteId === null ? null : $nombres[$docenteId] ?? null,
                    'estado' => $silabo?->estado,
                    'publicado_en' => $silabo?->publicado_en?->toImmutable(),
                ]];
            });
    }

    /**
     * @return array<int, string> teacher id => full name, of every teacher with an assigned group
     */
    public function docentes(): array
    {
        return $this->nombres->docentes(array_values(array_map(intval(...), CargaAcademica::query()->distinct()->pluck('docente_id')->all())));
    }

    /**
     * @return array<int, HorarioDeGrupo>
     */
    private function grupos(): array
    {
        if ($this->grupos !== null) {
            return $this->grupos;
        }

        $ids = array_map(intval(...), array_keys(($this->catalogoDeGrupos)()));

        return $this->grupos = $ids === [] ? [] : ($this->horariosDeGrupos)($ids);
    }
}
