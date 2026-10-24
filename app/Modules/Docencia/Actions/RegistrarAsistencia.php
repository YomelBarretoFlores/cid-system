<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Data\SesionDeClase;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Modules\Docencia\Support\GrupoACargo;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

final readonly class RegistrarAsistencia
{
    public function __construct(
        private BloqueoDeGrupo $bloqueoDeGrupo,
        private EstudiantesDelGrupo $estudiantesDelGrupo,
    ) {}

    /**
     * @param  array<int, mixed>  $estados  state per enrollment id
     */
    public function __invoke(int $grupoId, int $docenteId, string $fecha, string $horaInicio, array $estados, User $usuario): ClaseDictada
    {
        $dia = $this->fecha($fecha, $horaInicio);

        return Transaccion::ejecutar(function () use ($grupoId, $docenteId, $dia, $horaInicio, $estados, $usuario): ClaseDictada {
            // Same lock as assignment and unassignment, so a teacher removed from the group cannot save after that change commits.
            $grupo = ($this->bloqueoDeGrupo)($grupoId);
            GrupoACargo::asegurar($grupo, $docenteId, 'la asistencia solo se registra');
            $sesion = $this->sesion($grupo, $dia, $horaInicio);
            $marcas = $this->marcas($estados, ClasesDelHorario::estudiantesDe(($this->estudiantesDelGrupo)($grupoId), $dia));

            $clase = ClaseDictada::query()->firstOrNew(['grupo_id' => $grupoId, 'fecha' => $dia->toDateString(), 'hora_inicio' => $sesion->horaInicio]);
            // The teacher who took the class keeps it (its hours are theirs); a later correction only changes who recorded it.
            $clase->fill(['hora_fin' => $sesion->horaFin, 'docente_id' => $clase->docente_id ?? $docenteId, 'registrado_por' => $usuario->id])->save();

            foreach ($marcas as $matriculaId => $estado) {
                Asistencia::query()->updateOrCreate(['clase_id' => $clase->id, 'matricula_id' => $matriculaId], ['estado' => $estado]);
            }

            return $clase;
        });
    }

    private function fecha(string $fecha, string $horaInicio): CarbonImmutable
    {
        $dia = preg_match('/^\d{4}-\d{2}-\d{2}\z/', $fecha) === 1 ? CarbonImmutable::createFromFormat('!Y-m-d', $fecha, ClasesDelHorario::ZONA) : null;

        if (! $dia instanceof CarbonImmutable || $dia->toDateString() !== $fecha || preg_match('/^\d{2}:\d{2}\z/', $horaInicio) !== 1) {
            throw new ReglaDeNegocioIncumplida('La fecha o la hora de la clase no es válida.');
        }

        return $dia;
    }

    private function sesion(HorarioDeGrupo $grupo, CarbonImmutable $dia, string $horaInicio): SesionDeClase
    {
        if ($dia->toDateString() < $grupo->periodoInicio->toDateString() || $dia->toDateString() > $grupo->periodoFin->toDateString()) {
            throw new ReglaDeNegocioIncumplida("El {$dia->format('d/m/Y')} está fuera del periodo del grupo {$grupo->codigo}.");
        }

        if ($dia->gt(ClasesDelHorario::hoy())) {
            throw new ReglaDeNegocioIncumplida("Todavía no puedes registrar la asistencia de la clase del {$dia->format('d/m/Y')}.");
        }

        return ClasesDelHorario::sesionDe($grupo, $dia, $horaInicio)
            ?? throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} no tiene clase el {$dia->format('d/m/Y')} a las {$horaInicio}.");
    }

    /**
     * @param  array<int, mixed>  $estados
     * @param  list<EstudianteDelGrupo>  $estudiantes
     * @return array<int, EstadoAsistencia>
     */
    private function marcas(array $estados, array $estudiantes): array
    {
        $matriculas = array_map(fn (EstudianteDelGrupo $estudiante): int => $estudiante->matriculaId, $estudiantes);

        if (array_diff(array_keys($estados), $matriculas) !== []) {
            throw new ReglaDeNegocioIncumplida('La lista de estudiantes del grupo cambió; vuelve a cargarla.');
        }

        if (array_diff($matriculas, array_keys($estados)) !== []) {
            throw new ReglaDeNegocioIncumplida('Marca la asistencia de todos los estudiantes del grupo.');
        }

        return array_map(fn (mixed $estado): EstadoAsistencia => (is_string($estado) ? EstadoAsistencia::tryFrom($estado) : null)
            ?? throw new ReglaDeNegocioIncumplida('El estado de asistencia no es válido.'), $estados);
    }
}
