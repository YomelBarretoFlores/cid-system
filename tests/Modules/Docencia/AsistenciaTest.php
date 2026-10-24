<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-10 12:00', 'America/Lima'));
    [$this->usuario, $this->docente] = PortalDocente::docente();
    $periodo = Periodo::factory()->create(['fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-25']);
    $ciclo = Ciclo::factory()->create(['nivel' => NivelCurricular::Basico]);
    $this->grupo = Grupo::factory()->create(['codigo' => 'ING-01-A', 'ciclo_id' => $ciclo->id, 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Abierto]);
    Horario::factory()->for($this->grupo)->create(['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    Horario::factory()->for($this->grupo)->create(['dia_semana' => 3, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    PortalDocente::asignar($this->grupo, $this->docente);
    $this->ana = Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Quispe'])->id, 'matriculado_en' => '2027-02-20 15:00:00+00']);
    $this->luis = Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Álvarez'])->id, 'matriculado_en' => '2027-02-20 15:00:00+00']);
});

function registrarAsistencia(array $estados, string $fecha = '2027-03-08', string $hora = '18:00', ?Docente $docente = null): ClaseDictada
{
    return app(RegistrarAsistencia::class)(test()->grupo->id, ($docente ?? test()->docente)->id, $fecha, $hora, $estados, test()->usuario);
}

function estadosDeLaClase(ClaseDictada $clase): array
{
    return Asistencia::query()->where('clase_id', $clase->id)->orderBy('matricula_id')->pluck('estado', 'matricula_id')->map(fn (EstadoAsistencia $estado): string => $estado->value)->all();
}

it('records the class with its times, teacher and recorder, and the state of each student', function (): void {
    $clase = registrarAsistencia([$this->ana->id => 'presente', $this->luis->id => 'tardanza']);

    expect($clase->only(['grupo_id', 'docente_id', 'registrado_por']))->toBe(['grupo_id' => $this->grupo->id, 'docente_id' => $this->docente->id, 'registrado_por' => $this->usuario->id])
        ->and([$clase->fecha->toDateString(), $clase->hora_inicio, $clase->hora_fin])->toBe(['2027-03-08', '18:00:00', '20:00:00'])
        ->and(estadosDeLaClase($clase))->toBe([$this->ana->id => 'presente', $this->luis->id => 'tardanza'])
        ->and(DB::table('aud_registros')->whereIn('tabla', ['doc_clases_dictadas', 'doc_asistencias'])->count())->toBe(3);
});

it('replaces the states when the same class is saved again', function (): void {
    registrarAsistencia([$this->ana->id => 'presente', $this->luis->id => 'presente']);
    $clase = registrarAsistencia([$this->ana->id => 'falta', $this->luis->id => 'presente']);

    expect(ClaseDictada::query()->count())->toBe(1)
        ->and(estadosDeLaClase($clase))->toBe([$this->ana->id => 'falta', $this->luis->id => 'presente']);
});

it('accepts the class of today and keeps a withdrawn student in an earlier class', function (): void {
    $anterior = registrarAsistencia([$this->ana->id => 'presente', $this->luis->id => 'falta']);
    $this->luis->update(['estado' => 'retirada', 'retirado_en' => now()]);

    $hoy = registrarAsistencia([$this->ana->id => 'presente'], '2027-03-10');

    expect(estadosDeLaClase($hoy))->toBe([$this->ana->id => 'presente'])
        ->and(estadosDeLaClase($anterior))->toBe([$this->ana->id => 'presente', $this->luis->id => 'falta']);
});

it('refuses a roll call that breaks a rule and saves nothing', function (Closure $preparar, string $mensaje): void {
    [$estados, $fecha, $hora, $docente] = $preparar->call($this) + [[$this->ana->id => 'presente', $this->luis->id => 'presente'], '2027-03-08', '18:00', null];

    expect(fn (): ClaseDictada => registrarAsistencia($estados, $fecha, $hora, $docente))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(ClaseDictada::query()->count())->toBe(0)
        ->and(Asistencia::query()->count())->toBe(0);
})->with([
    'another teacher' => [fn (): array => [3 => Docente::factory()->create()], 'No tienes a cargo el grupo ING-01-A.'],
    'scheduled group' => [function (): array {
        $this->grupo->update(['estado' => EstadoGrupo::Programado]);

        return [];
    }, 'El grupo ING-01-A está Programado; la asistencia solo se registra en grupos abiertos o cerrados.'],
    'cancelled group' => [function (): array {
        $this->grupo->update(['estado' => EstadoGrupo::Cancelado]);

        return [];
    }, 'El grupo ING-01-A está Cancelado; la asistencia solo se registra en grupos abiertos o cerrados.'],
    'day without class' => [fn (): array => [1 => '2027-03-09'], 'El grupo ING-01-A no tiene clase el 09/03/2027 a las 18:00.'],
    'hour without class' => [fn (): array => [2 => '19:00'], 'El grupo ING-01-A no tiene clase el 08/03/2027 a las 19:00.'],
    'after the period' => [fn (): array => [1 => '2027-04-26'], 'El 26/04/2027 está fuera del periodo del grupo ING-01-A.'],
    'before the period' => [fn (): array => [1 => '2027-02-22'], 'El 22/02/2027 está fuera del periodo del grupo ING-01-A.'],
    'future class' => [fn (): array => [1 => '2027-03-15'], 'Todavía no puedes registrar la asistencia de la clase del 15/03/2027.'],
    'malformed date' => [fn (): array => [1 => '2027-3-8'], 'La fecha o la hora de la clase no es válida.'],
    'missing student' => [fn (): array => [0 => [$this->ana->id => 'presente']], 'Marca la asistencia de todos los estudiantes del grupo.'],
    'student of another group' => [fn (): array => [0 => [$this->ana->id => 'presente', $this->luis->id => 'presente', Matricula::factory()->create()->id => 'presente']], 'La lista de estudiantes del grupo cambió; vuelve a cargarla.'],
    'state that is not text' => [fn (): array => [0 => [$this->ana->id => null, $this->luis->id => 'presente']], 'El estado de asistencia no es válido.'],
    'unknown state' => [fn (): array => [0 => [$this->ana->id => 'presente', $this->luis->id => 'ausente']], 'El estado de asistencia no es válido.'],
]);

it('refuses a teacher removed from the group even with an earlier class on record', function (): void {
    registrarAsistencia([$this->ana->id => 'presente', $this->luis->id => 'presente']);
    PortalDocente::asignar($this->grupo, Docente::factory()->create());

    expect(fn (): ClaseDictada => registrarAsistencia([$this->ana->id => 'falta', $this->luis->id => 'falta']))->toThrow(ReglaDeNegocioIncumplida::class, 'No tienes a cargo el grupo ING-01-A.')
        ->and(Asistencia::query()->where('estado', 'falta')->count())->toBe(0);
});

it('accepts the roll call of a closed group', function (): void {
    $this->grupo->update(['estado' => EstadoGrupo::Cerrado]);

    expect(estadosDeLaClase(registrarAsistencia([$this->ana->id => 'presente', $this->luis->id => 'falta'])))->toBe([$this->ana->id => 'presente', $this->luis->id => 'falta']);
});

it('does not ask for a student who enrolled after the class date', function (): void {
    $tarde = Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'matriculado_en' => '2027-03-09 15:00:00+00']);

    $clase = registrarAsistencia([$this->ana->id => 'presente', $this->luis->id => 'presente']);

    expect(estadosDeLaClase($clase))->toBe([$this->ana->id => 'presente', $this->luis->id => 'presente'])
        ->and(fn (): ClaseDictada => registrarAsistencia([$this->ana->id => 'presente', $this->luis->id => 'presente', $tarde->id => 'presente']))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La lista de estudiantes del grupo cambió; vuelve a cargarla.');
});
