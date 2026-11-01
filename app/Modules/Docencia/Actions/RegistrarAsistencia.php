<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Academico\Data\SesionDeClase;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Modules\Docencia\Support\GrupoACargo;
use App\Modules\Docencia\Support\MarcasDeAsistencia;
use App\Modules\Docencia\Support\TemaDeLaClase;
use App\Modules\Docencia\Support\VentanasDeLlamado;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

/** First call of a class within its window, or the single call of a registro posterior once its windows passed. */
final readonly class RegistrarAsistencia
{
    public function __construct(
        private BloqueoDeGrupo $bloqueoDeGrupo,
        private EstudiantesDelGrupo $estudiantesDelGrupo,
    ) {}

    /**
     * @param  array<int, mixed>  $estados  state per enrollment id
     */
    public function __invoke(int $grupoId, int $docenteId, string $fecha, string $horaInicio, array $estados, User $usuario, ?string $tema = null): ClaseDictada
    {
        $dia = ClasesDelHorario::fechaDeClase($fecha, $horaInicio);
        $tema = TemaDeLaClase::normalizar($tema);

        return Transaccion::ejecutar(function () use ($grupoId, $docenteId, $dia, $horaInicio, $estados, $usuario, $tema): ClaseDictada {
            // Same lock as assignment and unassignment, so a teacher removed from the group cannot save after that change commits.
            $grupo = ($this->bloqueoDeGrupo)($grupoId);
            GrupoACargo::asegurar($grupo, $docenteId, 'la asistencia solo se registra');
            $sesion = ClasesDelHorario::sesionDelPeriodo($grupo, $dia, $horaInicio);
            $ventanas = VentanasDeLlamado::de($dia, $sesion);
            $ahora = ClasesDelHorario::ahora();
            $this->asegurarAbierta($ventanas, $dia, $ahora);
            $matriculas = array_map(fn (EstudianteDelGrupo $estudiante): int => $estudiante->matriculaId, ClasesDelHorario::estudiantesDe(($this->estudiantesDelGrupo)($grupoId), $dia));
            $marcas = MarcasDeAsistencia::validar($estados, $matriculas, EstadoAsistencia::cases());

            $clase = $this->clase($grupoId, $dia, $sesion);
            // The teacher who took the class keeps it (its hours are theirs); a later correction only changes who recorded it.
            $clase->fill(['hora_fin' => $sesion->horaFin, 'docente_id' => $clase->docente_id ?? $docenteId, 'registrado_por' => $usuario->id, ...$this->llamado($clase, $ventanas, $ahora)]);
            $clase->fill(['tema' => $this->tema($clase, $tema), 'silabo_semana_id' => TemaDeLaClase::semanaDelSilabo($grupo, $dia)?->id])->save();

            foreach ($marcas as $matriculaId => $estado) {
                Asistencia::query()->updateOrCreate(['clase_id' => $clase->id, 'matricula_id' => $matriculaId], ['estado' => $estado]);
            }

            return $clase;
        });
    }

    private function asegurarAbierta(VentanasDeLlamado $ventanas, CarbonImmutable $dia, CarbonImmutable $ahora): void
    {
        if (! $ventanas->yaAbrio($ahora)) {
            throw new ReglaDeNegocioIncumplida("Todavía no puedes registrar la asistencia de la clase del {$dia->format('d/m/Y')}. El llamado de inicio se abre a las {$ventanas->abreInicio()->format('H:i')}.");
        }
    }

    // Only a registro posterior is a single call, so it needs the topic now; the first call may leave it for the closing call.
    private function tema(ClaseDictada $clase, ?string $tema): ?string
    {
        $vigente = $tema ?? $clase->tema;

        return $clase->registro_posterior ? TemaDeLaClase::exigir($vigente) : $vigente;
    }

    private function clase(int $grupoId, CarbonImmutable $dia, SesionDeClase $sesion): ClaseDictada
    {
        $clave = ['grupo_id' => $grupoId, 'fecha' => $dia->toDateString(), 'hora_inicio' => $sesion->horaInicio];

        return ClaseDictada::query()->where($clave)->lockForUpdate()->first() ?? new ClaseDictada($clave);
    }

    /**
     * @return array<string, mixed>
     */
    private function llamado(ClaseDictada $clase, VentanasDeLlamado $ventanas, CarbonImmutable $ahora): array
    {
        if ($ventanas->inicioAbierto($ahora)) {
            if ($clase->llamado_cierre_en !== null) {
                throw new ReglaDeNegocioIncumplida('Ya tomaste el llamado de cierre de esta clase; el llamado de inicio ya no se puede cambiar.');
            }

            return ['llamado_inicio_en' => $clase->llamado_inicio_en ?? now()];
        }

        if ($clase->llamado_inicio_en !== null) {
            throw new ReglaDeNegocioIncumplida("El llamado de inicio de esta clase cerró a las {$ventanas->cierraInicio()->format('H:i')}.");
        }

        // A correction of an earlier registro (posterior or anterior) keeps its kind.
        return $clase->exists ? [] : ['registro_posterior' => true];
    }
}
