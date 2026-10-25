<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Database\Seeders;

use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Docencia\Actions\AsignarDocente;
use App\Modules\Docencia\Actions\GuardarDisponibilidad;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Modules\Docencia\Actions\RegistrarCostoPorHora;
use App\Modules\Docencia\Actions\RegistrarNotasParciales;
use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

// Synthetic teachers who teach the demo groups through the real Actions, with the attendance of the classes already given, some partial grades and hourly costs.
final class DocenciaDemoSeeder extends Seeder
{
    /**
     * Teacher, document, category, enabled languages, availability (days, from, to) and group codes of the current period.
     *
     * @var list<array{0: array{0: string, 1: string, 2: string}, 1: string, 2: CondicionLaboral, 3: list<string>, 4: array{0: list<int>, 1: string, 2: string}, 5: list<string>}>
     */
    private const array DOCENTES = [
        [['Docente', 'Demo', 'Ana'], '70000002', CondicionLaboral::Contratado, ['ING', 'ITA'], [[1, 2, 3, 4, 5], '07:00', '13:00'], ['ING-1-M', 'ITA-1-M']],
        [['Vega', 'Ríos', 'Carlos'], '70000003', CondicionLaboral::Nombrado, ['ING', 'POR', 'FRA'], [[1, 2, 3, 4, 5], '15:00', '21:00'], ['ING-5-N', 'POR-1-T', 'FRA-1-N']],
        [['Shuan', 'Ramírez', 'Julián'], '70000004', CondicionLaboral::Tercero, ['QUE'], [[6], '08:00', '19:00'], ['QUE-1-S']],
    ];

    public function run(): void
    {
        $administrador = User::query()->where('email', 'admin@ciln.test')->firstOrFail();

        // Fictitious hourly costs per category so the hours report shows amounts.
        foreach ([[CondicionLaboral::Nombrado, '50.00'], [CondicionLaboral::Contratado, '45.00'], [CondicionLaboral::Tercero, '40.00']] as [$categoria, $costo]) {
            app(RegistrarCostoPorHora::class)($categoria, $costo, CarbonImmutable::today('America/Lima')->startOfYear(), null, $administrador);
        }

        foreach (self::DOCENTES as $posicion => [$nombre, $documento, $categoria, $idiomas, $disponibilidad, $grupos]) {
            $docente = $this->docente($nombre, $documento, $categoria, $idiomas, $disponibilidad, $posicion === 0);
            $usuario = User::query()->where('email', $posicion === 0 ? 'docente@ciln.test' : 'admin@ciln.test')->firstOrFail();

            foreach ($this->gruposDelPeriodoEnCurso($grupos) as $grupo) {
                app(AsignarDocente::class)($grupo->id, $docente->id, $administrador);
                $this->tomarAsistencia($grupo, $docente, $usuario);
            }
        }

        $this->registrarNotasParciales();
    }

    /**
     * @param  array{0: string, 1: string, 2: string}  $nombre
     * @param  list<string>  $idiomas
     * @param  array{0: list<int>, 1: string, 2: string}  $disponibilidad
     */
    private function docente(array $nombre, string $documento, CondicionLaboral $categoria, array $idiomas, array $disponibilidad, bool $conCuenta): Docente
    {
        $usuario = $conCuenta ? User::factory()->create(['name' => 'Docente Demo', 'email' => 'docente@ciln.test']) : null;
        $correo = $conCuenta ? 'docente@ciln.test' : "docente.{$documento}@ciln.test";
        $persona = Persona::factory()->create([
            'user_id' => $usuario?->id, 'numero_documento' => $documento,
            'apellido_paterno' => $nombre[0], 'apellido_materno' => $nombre[1], 'nombres' => $nombre[2], 'correo' => $correo,
        ]);
        $docente = Docente::factory()->create([
            'persona_id' => $persona->id, 'correo_institucional' => "docente.{$documento}@ciln.test", 'ruc' => "10{$documento}0",
            'condicion_laboral' => $categoria,
        ]);

        foreach (Idioma::query()->whereIn('codigo', $idiomas)->pluck('id') as $idiomaId) {
            $docente->idiomas()->create(['idioma_id' => $idiomaId, 'nivel_maximo' => NivelCurricular::Avanzado]);
        }

        foreach ($disponibilidad[0] as $dia) {
            app(GuardarDisponibilidad::class)($docente->id, $dia, $disponibilidad[1], $disponibilidad[2]);
        }

        return $docente;
    }

    /**
     * @param  list<string>  $codigos
     * @return iterable<Grupo>
     */
    private function gruposDelPeriodoEnCurso(array $codigos): iterable
    {
        return Grupo::query()->whereIn('codigo', $codigos)->whereRelation('periodo', 'estado', 'en_curso')->orderBy('id')->get();
    }

    // Mostly present; a few late arrivals and absences in a fixed pattern so every run looks the same.
    private function tomarAsistencia(Grupo $grupo, Docente $docente, User $usuario): void
    {
        $horario = app(HorariosDeGrupos::class)([$grupo->id])[$grupo->id];
        $estudiantes = app(EstudiantesDelGrupo::class)($grupo->id);

        foreach (ClasesDelHorario::hastaHoy($horario) as $numero => $clase) {
            $presentes = ClasesDelHorario::estudiantesDe($estudiantes, $clase->fecha);
            $estados = collect($presentes)->mapWithKeys(fn (EstudianteDelGrupo $estudiante, int $indice): array => [
                $estudiante->matriculaId => match (($indice + $numero) % 9) {
                    4 => EstadoAsistencia::Tardanza->value,
                    7 => EstadoAsistencia::Falta->value,
                    default => EstadoAsistencia::Presente->value,
                },
            ])->all();

            app(RegistrarAsistencia::class)($grupo->id, $docente->id, $clase->fecha->toDateString(), substr($clase->sesion->horaInicio, 0, 5), $estados, $usuario);
        }
    }

    private function registrarNotasParciales(): void
    {
        $grupo = Grupo::query()->where('codigo', 'ING-1-M')->whereRelation('periodo', 'estado', 'en_curso')->firstOrFail();
        $parcial = collect(app(EsquemaDelGrupo::class)($grupo->id)->componentes ?? [])->firstWhere('codigo', 'parcial');
        $docente = Docente::query()->whereRelation('cargas', 'grupo_id', $grupo->id)->first();

        if ($parcial === null || $docente === null) {
            return;
        }

        $notas = collect(app(EstudiantesDelGrupo::class)($grupo->id))->mapWithKeys(fn (EstudianteDelGrupo $estudiante, int $indice): array => [
            $estudiante->matriculaId => [$parcial->id => number_format(10 + ($indice * 7 % 9) + ($indice % 2) * 0.5, 2, '.', '')],
        ])->all();

        app(RegistrarNotasParciales::class)($grupo->id, $docente->id, $notas, User::query()->where('email', 'docente@ciln.test')->firstOrFail());
    }
}
