<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Docencia\Data\ClaseParaConfirmar;
use App\Modules\Docencia\Data\ClasesDelDelegado;
use App\Modules\Docencia\Enums\ConfirmacionDeClase;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Enums\EstadoDeClase;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\Delegado;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Matricula\Contracts\MatriculasActivasDePersona;
use App\Modules\Personas\Contracts\FichasDePersonas;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

// What the delegate's portal reads: the classes of the groups where the person is the current delegate.
final readonly class BandejaDelDelegado
{
    private const int REVISADAS = 10;

    public function __construct(
        private MatriculasActivasDePersona $matriculasActivasDePersona,
        private DelegadoDelGrupo $delegadoDelGrupo,
        private DetalleDeGrupos $detalleDeGrupos,
        private FichasDePersonas $fichasDePersonas,
    ) {}

    public function __invoke(int $personaId): ClasesDelDelegado
    {
        $delegaciones = $this->delegaciones($personaId);

        if ($delegaciones === []) {
            return new ClasesDelDelegado(false, [], []);
        }

        $porConfirmar = $this->porConfirmar(array_keys($delegaciones));
        $revisadas = $this->revisadas(array_keys($delegaciones));
        $fichas = $this->fichas($porConfirmar->concat($revisadas), $delegaciones);

        return new ClasesDelDelegado(true, array_values($porConfirmar->map($fichas)->all()), array_values($revisadas->map($fichas)->all()));
    }

    /**
     * A person enrolled in a group is its delegate only while that enrollment is the group's current delegate.
     *
     * @return array<int, int> group id => enrollment id of the delegate
     */
    private function delegaciones(int $personaId): array
    {
        $matriculas = array_keys(($this->matriculasActivasDePersona)($personaId));

        if ($matriculas === []) {
            return [];
        }

        $delegaciones = [];

        foreach (Delegado::query()->whereIn('matricula_id', $matriculas)->pluck('grupo_id') as $grupoId) {
            $delegado = ($this->delegadoDelGrupo)((int) $grupoId);

            if ($delegado?->personaId === $personaId) {
                $delegaciones[(int) $grupoId] = $delegado->matriculaId;
            }
        }

        return $delegaciones;
    }

    /**
     * @param  list<int>  $grupoIds
     * @return Collection<int, ClaseDictada>
     */
    private function porConfirmar(array $grupoIds): Collection
    {
        $ahora = ClasesDelHorario::ahora();
        $desde = $ahora->subMinutes(Config::integer('ciln.asistencia.confirmacion_minutos_despues_del_fin'))->subDay();

        return ClaseDictada::query()
            ->whereIn('grupo_id', $grupoIds)
            ->where('confirmacion', ConfirmacionDeClase::Pendiente)
            ->where('fecha', '>=', $desde->toDateString())
            ->orderByDesc('fecha')->orderByDesc('hora_inicio')
            ->get()
            ->filter(fn (ClaseDictada $clase): bool => EstadoDeLaClase::de($clase, $ahora) === EstadoDeClase::PorConfirmar)
            ->values();
    }

    /**
     * @param  list<int>  $grupoIds
     * @return Collection<int, ClaseDictada>
     */
    private function revisadas(array $grupoIds): Collection
    {
        return ClaseDictada::query()
            ->whereIn('grupo_id', $grupoIds)
            ->whereIn('confirmacion', [ConfirmacionDeClase::Confirmada, ConfirmacionDeClase::Observada])
            ->orderByDesc('confirmada_en')
            ->limit(self::REVISADAS)
            ->get();
    }

    /**
     * @param  Collection<int, ClaseDictada>  $clases
     * @param  array<int, int>  $delegaciones
     * @return \Closure(ClaseDictada): ClaseParaConfirmar
     */
    private function fichas(Collection $clases, array $delegaciones): \Closure
    {
        $asistencias = Asistencia::query()->whereIn('clase_id', $clases->modelKeys())->get()->groupBy('clase_id');
        $grupos = ($this->detalleDeGrupos)(array_keys($delegaciones));
        $docentes = $this->nombresDeDocentes($clases);

        return function (ClaseDictada $clase) use ($asistencias, $grupos, $docentes, $delegaciones): ClaseParaConfirmar {
            $presentes = ($asistencias[$clase->id] ?? new Collection)->filter(fn (Asistencia $asistencia): bool => EstadoAsistencia::efectivo($asistencia->estado, $asistencia->estado_cierre) !== EstadoAsistencia::Falta);
            $grupo = $grupos[$clase->grupo_id] ?? null;

            return new ClaseParaConfirmar(
                claseId: $clase->id,
                fecha: $clase->fecha,
                horario: substr($clase->hora_inicio, 0, 5).' – '.substr($clase->hora_fin, 0, 5),
                grupo: $grupo === null ? '—' : "{$grupo->codigo} · {$grupo->idioma} ciclo {$grupo->cicloNumero}",
                docente: $docentes[$clase->docente_id] ?? '—',
                presentes: $presentes->count(),
                estudiantes: ($asistencias[$clase->id] ?? new Collection)->count(),
                vence: VentanasDeLlamado::deLaClase($clase)->venceConfirmacion(),
                puedeConfirmar: $presentes->contains('matricula_id', $delegaciones[$clase->grupo_id] ?? null),
                confirmacion: $clase->confirmacion ?? ConfirmacionDeClase::Pendiente,
                observacion: $clase->observacion,
            );
        };
    }

    /**
     * @param  Collection<int, ClaseDictada>  $clases
     * @return array<int, string> teacher id => full name
     */
    private function nombresDeDocentes(Collection $clases): array
    {
        $personas = Docente::query()->whereKey($clases->pluck('docente_id')->unique()->all())->pluck('persona_id', 'id');
        $fichas = ($this->fichasDePersonas)(array_values(array_map(intval(...), $personas->unique()->all())));

        return $personas->map(fn (int $personaId): string => $fichas[$personaId]->nombreCompleto ?? '—')->all();
    }
}
