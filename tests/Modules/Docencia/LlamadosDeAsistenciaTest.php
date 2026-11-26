<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Modules\Docencia\Actions\RegistrarLlamadoDeCierre;
use App\Modules\Docencia\Enums\ConfirmacionDeClase;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Enums\EstadoDeClase;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Support\EstadoDeLaClase;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;
use App\Shared\Parametros\Actions\GuardarParametrosDelSistema;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    [$this->usuario, $this->docente] = PortalDocente::docente();
    $periodo = Periodo::factory()->create(['fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-25']);
    $ciclo = Ciclo::factory()->create(['nivel' => NivelCurricular::Basico]);
    $this->grupo = Grupo::factory()->create(['codigo' => 'ING-01-A', 'ciclo_id' => $ciclo->id, 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Abierto]);
    Horario::factory()->for($this->grupo)->create(['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    Horario::factory()->for($this->grupo)->create(['dia_semana' => 3, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    PortalDocente::asignar($this->grupo, $this->docente);
    [$this->ana, $this->luis, $this->rosa] = collect(['Quispe', 'Álvarez', 'Zapata'])->map(fn (string $apellido): Matricula => Matricula::factory()->create([
        'grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => $apellido])->id, 'matriculado_en' => '2027-02-20 15:00:00+00',
    ]))->all();
});

function aLas(string $hora, string $fecha = '2027-03-10'): void
{
    test()->travelTo(CarbonImmutable::parse("{$fecha} {$hora}", 'America/Lima'));
}

function llamadoDeInicio(array $estados, string $fecha = '2027-03-10'): ClaseDictada
{
    return app(RegistrarAsistencia::class)(test()->grupo->id, test()->docente->id, $fecha, '18:00', $estados, test()->usuario, 'Saludos y presentaciones');
}

function llamadoDeCierre(array $estados, string $fecha = '2027-03-10', ?Docente $docente = null): ClaseDictada
{
    return app(RegistrarLlamadoDeCierre::class)(test()->grupo->id, ($docente ?? test()->docente)->id, $fecha, '18:00', $estados, test()->usuario, 'Saludos y presentaciones');
}

function marcasDelCierre(ClaseDictada $clase): array
{
    return Asistencia::query()->where('clase_id', $clase->id)->orderBy('matricula_id')->get()
        ->mapWithKeys(fn (Asistencia $asistencia): array => [$asistencia->matricula_id => [$asistencia->estado->value, $asistencia->estado_cierre?->value]])->all();
}

function inicioTomado(): ClaseDictada
{
    aLas('18:05');

    return llamadoDeInicio([test()->ana->id => 'presente', test()->luis->id => 'presente', test()->rosa->id => 'falta']);
}

function enLimaALas(?CarbonImmutable $momento): ?string
{
    return $momento?->setTimezone('America/Lima')->format('Y-m-d H:i');
}

it('takes the first call from ten minutes before the start until the end of the class', function (string $hora): void {
    aLas($hora);

    $clase = llamadoDeInicio([$this->ana->id => 'presente', $this->luis->id => 'presente', $this->rosa->id => 'falta'])->refresh();

    expect(enLimaALas($clase->llamado_inicio_en))->toBe("2027-03-10 {$hora}")
        ->and([$clase->registro_posterior, $clase->llamado_cierre_en, $clase->confirmacion])->toBe([false, null, null])
        ->and(marcasDelCierre($clase))->toBe([$this->ana->id => ['presente', null], $this->luis->id => ['presente', null], $this->rosa->id => ['falta', null]]);
})->with(['when it opens' => '17:50', 'when it closes' => '20:00']);

it('refuses the roll call of today before its first call opens and saves nothing', function (): void {
    aLas('17:49');

    expect(fn (): ClaseDictada => llamadoDeInicio([$this->ana->id => 'presente', $this->luis->id => 'presente', $this->rosa->id => 'presente']))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Todavía no puedes registrar la asistencia de la clase del 10/03/2027. El llamado de inicio se abre a las 17:50.')
        ->and(ClaseDictada::query()->count())->toBe(0)
        ->and(Asistencia::query()->count())->toBe(0);
});

it('corrects the first call within its window and keeps the time it was taken', function (): void {
    inicioTomado();
    aLas('19:30');

    $clase = llamadoDeInicio([$this->ana->id => 'falta', $this->luis->id => 'presente', $this->rosa->id => 'presente'])->refresh();

    expect(ClaseDictada::query()->count())->toBe(1)
        ->and(enLimaALas($clase->llamado_inicio_en))->toBe('2027-03-10 18:05')
        ->and(marcasDelCierre($clase))->toBe([$this->ana->id => ['falta', null], $this->luis->id => ['presente', null], $this->rosa->id => ['presente', null]]);
});

it('records a class not registered in its windows as registro posterior, in one call', function (string $hora, string $fecha): void {
    aLas($hora);

    $clase = llamadoDeInicio([$this->ana->id => 'presente', $this->luis->id => 'falta', $this->rosa->id => 'presente'], $fecha)->refresh();

    expect([$clase->registro_posterior, $clase->llamado_inicio_en, $clase->llamado_cierre_en, $clase->confirmacion])->toBe([true, null, null, null])
        ->and(marcasDelCierre($clase))->toBe([$this->ana->id => ['presente', null], $this->luis->id => ['falta', null], $this->rosa->id => ['presente', null]])
        ->and(EstadoDeLaClase::de($clase))->toBe(EstadoDeClase::RegistroPosterior);
})->with([
    'today after the first call closed' => ['20:01', '2027-03-10'],
    'an earlier date' => ['12:00', '2027-03-08'],
]);

it('refuses to change the first call once its window closed', function (): void {
    $clase = inicioTomado();
    aLas('20:01');

    expect(fn (): ClaseDictada => llamadoDeInicio([$this->ana->id => 'falta', $this->luis->id => 'falta', $this->rosa->id => 'falta']))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El llamado de inicio de esta clase cerró a las 20:00.')
        ->and(marcasDelCierre($clase))->toBe([$this->ana->id => ['presente', null], $this->luis->id => ['presente', null], $this->rosa->id => ['falta', null]])
        ->and($clase->refresh()->registro_posterior)->toBeFalse();
});

it('takes the second call for the students present and leaves the class to be confirmed', function (string $hora): void {
    $clase = inicioTomado();
    aLas($hora);

    llamadoDeCierre([$this->ana->id => 'presente', $this->luis->id => 'falta']);
    $clase->refresh();

    expect(enLimaALas($clase->llamado_cierre_en))->toBe("2027-03-10 {$hora}")
        ->and($clase->confirmacion)->toBe(ConfirmacionDeClase::Pendiente)
        ->and(marcasDelCierre($clase))->toBe([$this->ana->id => ['presente', 'presente'], $this->luis->id => ['presente', 'falta'], $this->rosa->id => ['falta', null]])
        ->and(EstadoDeLaClase::de($clase))->toBe(EstadoDeClase::PorConfirmar);
})->with(['when it opens' => '19:45', 'when it closes' => '20:30']);

it('corrects the second call within its window and keeps the time it was taken', function (): void {
    $clase = inicioTomado();
    aLas('19:50');
    llamadoDeCierre([$this->ana->id => 'presente', $this->luis->id => 'falta']);
    aLas('20:10');

    llamadoDeCierre([$this->ana->id => 'falta', $this->luis->id => 'presente']);
    $clase->refresh();

    expect(enLimaALas($clase->llamado_cierre_en))->toBe('2027-03-10 19:50')
        ->and(marcasDelCierre($clase))->toBe([$this->ana->id => ['presente', 'falta'], $this->luis->id => ['presente', 'presente'], $this->rosa->id => ['falta', null]]);
});

it('refuses a second call that breaks a rule and changes nothing', function (Closure $preparar, string $mensaje): void {
    $clase = inicioTomado();
    [$estados, $hora, $docente] = $preparar->call($this) + [[$this->ana->id => 'presente', $this->luis->id => 'presente'], '19:50', null];
    aLas($hora);

    expect(fn (): ClaseDictada => llamadoDeCierre($estados, docente: $docente))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and($clase->refresh()->only(['llamado_cierre_en', 'confirmacion']))->toBe(['llamado_cierre_en' => null, 'confirmacion' => null])
        ->and(Asistencia::query()->whereNotNull('estado_cierre')->count())->toBe(0);
})->with([
    'before it opens' => [fn (): array => [1 => '19:44'], 'El llamado de cierre de la clase del 10/03/2027 se abre a las 19:45.'],
    'after it closes' => [fn (): array => [1 => '20:31'], 'El llamado de cierre de la clase del 10/03/2027 cerró a las 20:30.'],
    'another teacher' => [fn (): array => [2 => Docente::factory()->create()], 'No tienes a cargo el grupo ING-01-A.'],
    'student absent at the start' => [fn (): array => [0 => [$this->ana->id => 'presente', $this->luis->id => 'presente', $this->rosa->id => 'presente']], 'La lista de estudiantes del grupo cambió; vuelve a cargarla.'],
    'missing student' => [fn (): array => [0 => [$this->ana->id => 'presente']], 'Marca la asistencia de todos los estudiantes del grupo.'],
    'a late arrival' => [fn (): array => [0 => [$this->ana->id => 'presente', $this->luis->id => 'tardanza']], 'El estado de asistencia no es válido.'],
]);

it('refuses a second call for a class without a first call', function (Closure $preparar, string $mensaje): void {
    $preparar->call($this);
    aLas('20:10');

    expect(fn (): ClaseDictada => llamadoDeCierre([$this->ana->id => 'presente', $this->luis->id => 'presente', $this->rosa->id => 'presente']))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(ClaseDictada::query()->whereNotNull('llamado_cierre_en')->count())->toBe(0)
        ->and(Asistencia::query()->whereNotNull('estado_cierre')->count())->toBe(0);
})->with([
    'not recorded yet' => [fn (): null => null, 'Primero toma el llamado de inicio de esta clase.'],
    'registro posterior' => [function (): void {
        aLas('20:05');
        llamadoDeInicio([$this->ana->id => 'presente', $this->luis->id => 'presente', $this->rosa->id => 'presente']);
    }, 'Esta clase se registró como registro posterior; no tiene llamado de cierre.'],
]);

it('refuses to change the first call once the second call was taken', function (): void {
    $clase = inicioTomado();
    aLas('19:50');
    llamadoDeCierre([$this->ana->id => 'presente', $this->luis->id => 'presente']);
    aLas('19:55');

    expect(fn (): ClaseDictada => llamadoDeInicio([$this->ana->id => 'falta', $this->luis->id => 'falta', $this->rosa->id => 'falta']))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Ya tomaste el llamado de cierre de esta clase; el llamado de inicio ya no se puede cambiar.')
        ->and(marcasDelCierre($clase))->toBe([$this->ana->id => ['presente', 'presente'], $this->luis->id => ['presente', 'presente'], $this->rosa->id => ['falta', null]]);
});

it('refuses to change the second call once the delegate reviewed the class', function (): void {
    $clase = inicioTomado();
    aLas('19:50');
    llamadoDeCierre([$this->ana->id => 'presente', $this->luis->id => 'presente']);
    DB::table('doc_clases_dictadas')->where('id', $clase->id)->update(['confirmacion' => 'confirmada', 'confirmada_por_matricula_id' => $this->ana->id, 'confirmada_en' => now()]);

    expect(fn (): ClaseDictada => llamadoDeCierre([$this->ana->id => 'falta', $this->luis->id => 'falta']))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El delegado ya revisó esta clase; el llamado de cierre ya no se puede cambiar.')
        ->and(marcasDelCierre($clase))->toBe([$this->ana->id => ['presente', 'presente'], $this->luis->id => ['presente', 'presente'], $this->rosa->id => ['falta', null]]);
});

it('reads the state of a class from its calls, its confirmation and the time', function (Closure $preparar, string $ahora, EstadoDeClase $estado, string $etiqueta): void {
    $clase = $preparar->call($this);

    expect(EstadoDeLaClase::de($clase->refresh(), CarbonImmutable::parse("2027-03-10 {$ahora}", 'America/Lima')))->toBe($estado)
        ->and($estado->etiqueta())->toBe($etiqueta);
})->with([
    'saved before the two calls existed' => [fn (): ClaseDictada => ClaseDictada::query()->create(['grupo_id' => $this->grupo->id, 'fecha' => '2027-03-10', 'hora_inicio' => '18:00', 'hora_fin' => '20:00', 'docente_id' => $this->docente->id, 'registrado_por' => $this->usuario->id]), '23:00', EstadoDeClase::RegistroAnterior, 'registrada'],
    'first call while the second can still be taken' => [fn (): ClaseDictada => inicioTomado()->refresh(), '20:30', EstadoDeClase::LlamadoDeInicio, 'con llamado de inicio'],
    'first call and the second window passed' => [fn (): ClaseDictada => inicioTomado()->refresh(), '20:31', EstadoDeClase::SinLlamadoDeCierre, 'sin llamado de cierre'],
    'second call within the confirmation window' => [function (): ClaseDictada {
        $clase = inicioTomado();
        aLas('19:50');
        llamadoDeCierre([$this->ana->id => 'presente', $this->luis->id => 'presente']);

        return $clase;
    }, '22:00', EstadoDeClase::PorConfirmar, 'por confirmar'],
    'second call and the confirmation window passed' => [function (): ClaseDictada {
        $clase = inicioTomado();
        aLas('19:50');
        llamadoDeCierre([$this->ana->id => 'presente', $this->luis->id => 'presente']);

        return $clase;
    }, '22:01', EstadoDeClase::SinConfirmar, 'sin confirmar'],
    'confirmed by the delegate' => [function (): ClaseDictada {
        $clase = inicioTomado();
        aLas('19:50');
        llamadoDeCierre([$this->ana->id => 'presente', $this->luis->id => 'presente']);
        DB::table('doc_clases_dictadas')->where('id', $clase->id)->update(['confirmacion' => 'confirmada', 'confirmada_por_matricula_id' => $this->ana->id, 'confirmada_en' => now()]);

        return $clase;
    }, '23:00', EstadoDeClase::Confirmada, 'confirmada'],
    'observed by the delegate' => [function (): ClaseDictada {
        $clase = inicioTomado();
        aLas('19:50');
        llamadoDeCierre([$this->ana->id => 'presente', $this->luis->id => 'presente']);
        DB::table('doc_clases_dictadas')->where('id', $clase->id)->update(['confirmacion' => 'observada', 'confirmada_por_matricula_id' => $this->ana->id, 'confirmada_en' => now(), 'observacion' => 'El docente no llegó.']);

        return $clase;
    }, '23:00', EstadoDeClase::Observada, 'observada'],
    'registro posterior' => [function (): ClaseDictada {
        aLas('12:00');

        return llamadoDeInicio([$this->ana->id => 'presente', $this->luis->id => 'presente', $this->rosa->id => 'presente'], '2027-03-08');
    }, '23:00', EstadoDeClase::RegistroPosterior, 'registro posterior'],
]);

it('counts a student absent in the second call as absent, otherwise the first call', function (EstadoAsistencia $inicio, ?EstadoAsistencia $cierre, EstadoAsistencia $efectivo): void {
    expect(EstadoAsistencia::efectivo($inicio, $cierre))->toBe($efectivo);
})->with([
    'left before the end' => [EstadoAsistencia::Presente, EstadoAsistencia::Falta, EstadoAsistencia::Falta],
    'present and stayed' => [EstadoAsistencia::Presente, EstadoAsistencia::Presente, EstadoAsistencia::Presente],
    'absent at the start' => [EstadoAsistencia::Falta, null, EstadoAsistencia::Falta],
    'one call only' => [EstadoAsistencia::Presente, null, EstadoAsistencia::Presente],
]);

it('keeps the calls and the confirmation consistent in the database', function (Closure $romper): void {
    $clase = inicioTomado();

    expect(fn (): mixed => $romper->call($this, $clase))->toThrow(QueryException::class);
})->with([
    'to be confirmed without a second call' => [fn (ClaseDictada $clase): int => DB::table('doc_clases_dictadas')->where('id', $clase->id)->update(['confirmacion' => 'pendiente'])],
    'second call without a first call' => [fn (ClaseDictada $clase): int => DB::table('doc_clases_dictadas')->where('id', $clase->id)->update(['llamado_inicio_en' => null, 'llamado_cierre_en' => now(), 'confirmacion' => 'pendiente'])],
    'registro posterior with a first call' => [fn (ClaseDictada $clase): int => DB::table('doc_clases_dictadas')->where('id', $clase->id)->update(['registro_posterior' => true])],
    'confirmed without the delegate' => [fn (ClaseDictada $clase): int => DB::table('doc_clases_dictadas')->where('id', $clase->id)->update(['llamado_cierre_en' => now(), 'confirmacion' => 'confirmada'])],
    'observed without a comment' => [fn (ClaseDictada $clase): int => DB::table('doc_clases_dictadas')->where('id', $clase->id)->update(['llamado_cierre_en' => now(), 'confirmacion' => 'observada', 'confirmada_por_matricula_id' => $this->ana->id, 'confirmada_en' => now()])],
    'second call mark for a student absent at the start' => [fn (ClaseDictada $clase): int => DB::table('doc_asistencias')->where('clase_id', $clase->id)->where('matricula_id', $this->rosa->id)->update(['estado_cierre' => 'presente'])],
]);

it('opens the first call as many minutes before the class as the system parameters say', function (): void {
    app(GuardarParametrosDelSistema::class)(['asistencia_inicio_abre_minutos_antes' => 30], User::factory()->create()->givePermissionTo(PermisoUsuario::Gestionar->value));

    aLas('17:29');
    expect(fn (): ClaseDictada => llamadoDeInicio([$this->ana->id => 'presente', $this->luis->id => 'presente', $this->rosa->id => 'presente']))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Todavía no puedes registrar la asistencia de la clase del 10/03/2027. El llamado de inicio se abre a las 17:30.');

    aLas('17:30');
    $clase = llamadoDeInicio([$this->ana->id => 'presente', $this->luis->id => 'presente', $this->rosa->id => 'presente'])->refresh();

    expect(enLimaALas($clase->llamado_inicio_en))->toBe('2027-03-10 17:30');
});
