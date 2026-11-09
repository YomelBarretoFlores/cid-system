<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\CiclosDeGrupos;
use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Contracts\VentanasDeMatricula;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Academico\Data\GrupoData;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Academico\Data\VentanaDeMatricula;
use App\Modules\Certificacion\Contracts\MatriculasCalificadas;
use App\Modules\Matricula\Data\RequisitoDeMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\TipoDeRequisito;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Support\OpcionesDeMatricula;
use App\Shared\Ui\Formato;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

// Read-only mirror of the rules MatricularEstudiante enforces, so the student sees what blocks the enrollment before trying it.
final readonly class EvaluarRequisitosDeMatricula
{
    private const string OTROS_IDIOMAS = 'empiezas en el ciclo 1, o puedes rendir el examen de clasificación.';

    public function __construct(
        private OpcionesDeMatricula $opciones,
        private VentanasDeMatricula $ventanas,
        private CatalogoDeIdiomas $catalogo,
        private CiclosDeGrupos $ciclosDeGrupos,
        private DetalleDeGrupos $detalleDeGrupos,
        private MatriculasCalificadas $matriculasCalificadas,
    ) {}

    /**
     * @return list<RequisitoDeMatricula>
     */
    public function __invoke(int $personaId): array
    {
        $oferta = collect($this->opciones->oferta($personaId));
        $activas = collect(Matricula::query()
            ->where(['persona_id' => $personaId, 'estado' => EstadoMatricula::Activa])
            ->pluck('grupo_id', 'id')->all())->map(intval(...));
        $ofertados = array_values(array_map(fn (FichaDeGrupo $grupo): int => $grupo->id, $oferta->all()));
        $ciclos = ($this->ciclosDeGrupos)([...$ofertados, ...$activas->values()->all()]);
        $clavesOfertadas = array_map(fn (int $grupoId): string => $this->clave($ciclos, $grupoId), $ofertados);
        [$enConflicto, $otras] = [
            $activas->filter(fn (int $grupoId): bool => in_array($this->clave($ciclos, $grupoId), $clavesOfertadas, true)),
            $activas->reject(fn (int $grupoId): bool => in_array($this->clave($ciclos, $grupoId), $clavesOfertadas, true)),
        ];
        $enConflicto = array_values($enConflicto->all());

        return [
            $this->periodo(),
            $this->ciclo($personaId, $this->segunCalificacion($otras, $ciclos, true), $this->segunCalificacion($otras, $ciclos, false)),
            $this->pago($personaId),
            $this->grupos($oferta, array_map(fn (int $grupoId): string => $this->clave($ciclos, $grupoId), $enConflicto), $ciclos),
            $this->unicidad($enConflicto),
        ];
    }

    /**
     * @param  array<int, GrupoData>  $ciclos
     */
    private function clave(array $ciclos, int $grupoId): string
    {
        $grupo = $ciclos[$grupoId] ?? null;

        return $grupo === null ? "grupo|{$grupoId}" : "{$grupo->cicloId}|{$grupo->periodoId}";
    }

    private function periodo(): RequisitoDeMatricula
    {
        $ventanas = collect(($this->ventanas)());
        $abiertas = $ventanas->where('abierta', true);

        if ($abiertas->isNotEmpty()) {
            return new RequisitoDeMatricula(TipoDeRequisito::Periodo, 'Periodo de matrícula abierto', true, array_values($abiertas->map(
                fn (VentanaDeMatricula $ventana): string => "{$ventana->periodo}: del ".Formato::fechaCorta($ventana->inicio).' al '.Formato::fechaCorta($ventana->fin).'.',
            )->all()));
        }

        $proxima = $ventanas->first();
        $detalle = match (true) {
            ! $proxima instanceof VentanaDeMatricula => 'Aún no hay un periodo de matrícula programado.',
            $proxima->inicio->isAfter(CarbonImmutable::today('America/Lima')) => "La matrícula del periodo {$proxima->periodo} abre el ".Formato::fechaCorta($proxima->inicio).'.',
            default => "La matrícula del periodo {$proxima->periodo} aún no está abierta.",
        };

        return new RequisitoDeMatricula(TipoDeRequisito::Periodo, 'Periodo de matrícula abierto', false, [$detalle]);
    }

    /**
     * @param  Collection<int, int>  $otras  group per active enrollment of other periods
     * @param  array<int, GrupoData>  $ciclos
     * @return Collection<int, GrupoData>
     */
    private function segunCalificacion(Collection $otras, array $ciclos, bool $calificadas): Collection
    {
        $conActaCerrada = ($this->matriculasCalificadas)(array_values(array_map(intval(...), $otras->keys()->all())));

        return $otras
            ->filter(fn (int $grupoId, int $matriculaId): bool => in_array($matriculaId, $conActaCerrada, true) === $calificadas)
            ->map(fn (int $grupoId): ?GrupoData => $ciclos[$grupoId] ?? null)
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, GrupoData>  $cursadas  graded in a closed acta, which makes the student a repeater
     * @param  Collection<int, GrupoData>  $enCurso  not graded yet
     */
    private function ciclo(int $personaId, Collection $cursadas, Collection $enCurso): RequisitoDeMatricula
    {
        $habilitaciones = Habilitacion::query()->where('persona_id', $personaId)->get()->keyBy('idioma_id');
        $conHistoria = $habilitaciones->keys()->merge($cursadas->pluck('idiomaId'))->merge($enCurso->pluck('idiomaId'))->unique();

        $lineas = collect(($this->catalogo)())
            ->filter(fn (IdiomaDelCatalogo $idioma): bool => $idioma->activo && $conHistoria->contains($idioma->id))
            ->flatMap(fn (IdiomaDelCatalogo $idioma): array => $this->lineaDelIdioma($idioma, $habilitaciones->get($idioma->id), $cursadas, $enCurso));

        $lineas->push(($lineas->isEmpty() ? 'En todos los idiomas ' : 'En los demás idiomas ').self::OTROS_IDIOMAS);

        return new RequisitoDeMatricula(TipoDeRequisito::Ciclo, 'Ciclo que te corresponde', true, array_values($lineas->all()), route('estudiante.clasificacion'), 'Solicitar examen de clasificación');
    }

    /**
     * @param  Collection<int, GrupoData>  $cursadas
     * @param  Collection<int, GrupoData>  $enCurso
     * @return list<string>
     */
    private function lineaDelIdioma(IdiomaDelCatalogo $idioma, ?Habilitacion $habilitacion, Collection $cursadas, Collection $enCurso): array
    {
        $cicloId = $habilitacion->ciclo_id ?? array_key_first($idioma->ciclos);
        $ciclo = $cicloId === null ? null : ($idioma->ciclos[$cicloId] ?? null);

        if ($ciclo === null) {
            return [];
        }

        $origen = $habilitacion instanceof Habilitacion ? 'por '.lcfirst($habilitacion->origen->etiqueta()) : 'desde el inicio del programa';
        $delCiclo = fn (GrupoData $grupo): bool => $grupo->cicloId === $cicloId;
        $situacion = match (true) {
            $enCurso->contains($delCiclo) => '; lo estás cursando: si lo apruebas, al cerrarse el acta se habilitará el ciclo siguiente',
            $cursadas->contains($delCiclo) => '; lo llevarías como repitente',
            default => '',
        };

        return ["{$idioma->nombre}: {$ciclo}, {$origen}{$situacion}."];
    }

    private function pago(int $personaId): RequisitoDeMatricula
    {
        $disponibles = count($this->opciones->pagosDisponibles($personaId));

        return match ($disponibles) {
            0 => new RequisitoDeMatricula(TipoDeRequisito::Pago, 'Pago de matrícula validado y sin usar', false, ['Registra tu pago de matrícula y espera a que el CILN lo valide.'], route('estudiante.pagos'), 'Registrar mi pago'),
            1 => new RequisitoDeMatricula(TipoDeRequisito::Pago, 'Pago de matrícula validado y sin usar', true, ['Tienes 1 pago de matrícula disponible.']),
            default => new RequisitoDeMatricula(TipoDeRequisito::Pago, 'Pago de matrícula validado y sin usar', true, ["Tienes {$disponibles} pagos de matrícula disponibles."]),
        };
    }

    /**
     * @param  Collection<int, FichaDeGrupo>  $oferta
     * @param  list<string>  $conflictos
     * @param  array<int, GrupoData>  $ciclos
     */
    private function grupos(Collection $oferta, array $conflictos, array $ciclos): RequisitoDeMatricula
    {
        $libres = $oferta->reject(fn (FichaDeGrupo $grupo): bool => in_array($this->clave($ciclos, $grupo->id), $conflictos, true));
        $conVacantes = $libres->filter(fn (FichaDeGrupo $grupo): bool => $grupo->vacantesDisponibles > 0);

        if ($conVacantes->isNotEmpty()) {
            return new RequisitoDeMatricula(TipoDeRequisito::Grupos, 'Grupos de tu ciclo con vacantes', true, array_values($conVacantes->groupBy('idioma')->map(
                fn (Collection $grupos, string $idioma): string => $idioma.': '.($grupos->count() === 1 ? '1 grupo con vacantes.' : "{$grupos->count()} grupos con vacantes."),
            )->all()));
        }

        $detalle = match (true) {
            $oferta->isEmpty() => 'Todavía no hay grupos abiertos para tu ciclo.',
            $libres->isEmpty() => 'Ya tienes matrícula en el ciclo que te corresponde de este periodo.',
            default => 'Los grupos abiertos de tu ciclo ya no tienen vacantes.',
        };

        return new RequisitoDeMatricula(TipoDeRequisito::Grupos, 'Grupos de tu ciclo con vacantes', false, [$detalle]);
    }

    /**
     * @param  list<int>  $enConflicto
     */
    private function unicidad(array $enConflicto): RequisitoDeMatricula
    {
        if ($enConflicto === []) {
            return new RequisitoDeMatricula(TipoDeRequisito::Unicidad, 'Sin otra matrícula en el mismo ciclo y periodo', true, ['No tienes otra matrícula activa en el ciclo que te corresponde de este periodo.']);
        }

        return new RequisitoDeMatricula(TipoDeRequisito::Unicidad, 'Sin otra matrícula en el mismo ciclo y periodo', false, array_values(array_map(
            fn (FichaDeGrupo $grupo): string => "Ya estás matriculado en {$grupo->idioma}, ciclo {$grupo->cicloNumero}, de este periodo (grupo {$grupo->codigo}).",
            ($this->detalleDeGrupos)($enConflicto),
        )));
    }
}
