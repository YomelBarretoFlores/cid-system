<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Database\Seeders;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Actions\ConfirmarClaseDictada;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Modules\Docencia\Actions\RegistrarLlamadoDeCierre;
use App\Modules\Docencia\Data\ClaseProgramada;
use App\Modules\Docencia\Enums\ConfirmacionDeClase;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Enums\EstadoDeClase;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Modules\Docencia\Support\DelegadoDelGrupo;
use App\Modules\Docencia\Support\VentanasDeLlamado;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use Closure;
use Illuminate\Database\Seeder;

// The last finished classes of the demo student's English group, taken with both calls at their real times and answered by the delegate:
// two confirmed, one observed and one never answered. Only classes without calls (missing or saved before the calls existed) are used.
final class ControlDeClasesDemoSeeder extends Seeder
{
    public const string GRUPO = 'ING-1-M';

    private const array RESPUESTAS = [EstadoDeClase::Confirmada, EstadoDeClase::Confirmada, EstadoDeClase::Observada, EstadoDeClase::SinConfirmar];

    /**
     * The newest classes whose confirmation deadline already passed, oldest first.
     *
     * @param  (Closure(ClaseProgramada): bool)|null  $libre
     * @return list<ClaseProgramada>
     */
    public static function reservadas(HorarioDeGrupo $horario, ?Closure $libre = null): array
    {
        $ahora = ClasesDelHorario::ahora();

        return array_values(collect(ClasesDelHorario::hastaHoy($horario))
            ->filter(fn (ClaseProgramada $clase): bool => VentanasDeLlamado::de($clase->fecha, $clase->sesion)->venceConfirmacion()->lt($ahora) && (! $libre instanceof Closure || $libre($clase)))
            ->take(count(self::RESPUESTAS))->reverse()->all());
    }

    public function run(): void
    {
        $grupo = Grupo::query()->where('codigo', self::GRUPO)->whereRelation('periodo', 'estado', 'en_curso')->first();
        $docenteId = CargaAcademica::query()->where('grupo_id', $grupo->id ?? 0)->value('docente_id');
        $delegado = $grupo instanceof Grupo ? app(DelegadoDelGrupo::class)($grupo->id) : null;

        if ($docenteId === null || ! $delegado instanceof EstudianteDelGrupo || $this->yaRespondidas($grupo->id)) {
            $this->command?->info('Control de clases de demo: 0 clases tomadas');

            return;
        }

        $horario = app(HorariosDeGrupos::class)([$grupo->id])[$grupo->id];
        $clases = self::reservadas($horario, fn (ClaseProgramada $clase): bool => $this->sinLlamados($grupo->id, $clase));

        foreach ($clases as $posicion => $clase) {
            $this->tomar($grupo->id, (int) $docenteId, $clase, $delegado, self::RESPUESTAS[$posicion]);
        }

        $this->command?->info('Control de clases de demo: '.count($clases).' clases tomadas');
    }

    private function yaRespondidas(int $grupoId): bool
    {
        return ClaseDictada::query()->where('grupo_id', $grupoId)->whereIn('confirmacion', [ConfirmacionDeClase::Confirmada, ConfirmacionDeClase::Observada])->exists();
    }

    private function sinLlamados(int $grupoId, ClaseProgramada $clase): bool
    {
        $existente = ClaseDictada::query()->where(['grupo_id' => $grupoId, 'fecha' => $clase->fecha->toDateString(), 'hora_inicio' => $clase->sesion->horaInicio])->first();

        return $existente === null || (! $existente->registro_posterior && $existente->llamado_inicio_en === null);
    }

    private function tomar(int $grupoId, int $docenteId, ClaseProgramada $clase, EstudianteDelGrupo $delegado, EstadoDeClase $respuesta): void
    {
        $ventanas = VentanasDeLlamado::de($clase->fecha, $clase->sesion);
        $usuario = CuentasDeDemo::usuarioDe($docenteId);
        $fecha = $clase->fecha->toDateString();
        $hora = substr($clase->sesion->horaInicio, 0, 5);
        $inicio = $this->marcasDeInicio($grupoId, $clase, $delegado);

        $tomada = RelojDeDemo::en($ventanas->abreInicio()->addMinutes(12), fn (): ClaseDictada => app(RegistrarAsistencia::class)($grupoId, $docenteId, $fecha, $hora, $inicio, $usuario));
        $cierre = $this->marcasDeCierre($inicio, $delegado);
        RelojDeDemo::en($ventanas->abreCierre()->addMinutes(5), fn (): ClaseDictada => app(RegistrarLlamadoDeCierre::class)($grupoId, $docenteId, $fecha, $hora, $cierre, $usuario));

        if ($respuesta === EstadoDeClase::SinConfirmar) {
            return;
        }

        $observacion = $respuesta === EstadoDeClase::Observada ? 'La clase terminó veinte minutos antes de la hora programada.' : null;
        RelojDeDemo::en($ventanas->abreCierre()->addMinutes(35), fn (): ClaseDictada => app(ConfirmarClaseDictada::class)($tomada->id, $delegado->personaId, $observacion === null, $observacion));
    }

    /**
     * Mostly present with one late arrival and one absence; the delegate is always present.
     *
     * @return array<int, string>
     */
    private function marcasDeInicio(int $grupoId, ClaseProgramada $clase, EstudianteDelGrupo $delegado): array
    {
        $estudiantes = ClasesDelHorario::estudiantesDe(app(EstudiantesDelGrupo::class)($grupoId), $clase->fecha);

        return collect($estudiantes)->mapWithKeys(fn (EstudianteDelGrupo $estudiante, int $indice): array => [
            $estudiante->matriculaId => match (true) {
                $estudiante->matriculaId === $delegado->matriculaId => EstadoAsistencia::Presente->value,
                $indice % 7 === 3 => EstadoAsistencia::Tardanza->value,
                $indice % 7 === 5 => EstadoAsistencia::Falta->value,
                default => EstadoAsistencia::Presente->value,
            },
        ])->all();
    }

    /**
     * Everyone present or late at the first call stays, except one student who left early.
     *
     * @param  array<int, string>  $inicio
     * @return array<int, string>
     */
    private function marcasDeCierre(array $inicio, EstudianteDelGrupo $delegado): array
    {
        $presentes = collect($inicio)->reject(fn (string $estado): bool => $estado === EstadoAsistencia::Falta->value)->keys();
        $seFue = $presentes->reject(fn (int $matriculaId): bool => $matriculaId === $delegado->matriculaId)->last();

        return $presentes->mapWithKeys(fn (int $matriculaId): array => [$matriculaId => $matriculaId === $seFue ? EstadoAsistencia::Falta->value : EstadoAsistencia::Presente->value])->all();
    }
}
