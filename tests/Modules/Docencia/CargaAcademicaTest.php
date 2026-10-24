<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Actions\AsignarDocente;
use App\Modules\Docencia\Actions\CambiarEstadoDocente;
use App\Modules\Docencia\Actions\EliminarDisponibilidad;
use App\Modules\Docencia\Actions\GuardarDisponibilidad;
use App\Modules\Docencia\Actions\GuardarDocente;
use App\Modules\Docencia\Actions\QuitarDocenteDelGrupo;
use App\Modules\Docencia\Actions\ResumirCargaDelDocente;
use App\Modules\Docencia\Data\CargaHoraria;
use App\Modules\Docencia\Data\DatosDeDocente;
use App\Modules\Docencia\Data\IdiomaHabilitado;
use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Enums\EstadoDocente;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->usuario = User::factory()->create();
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->frances = Idioma::factory()->create(['nombre' => 'Francés']);
    $this->periodo = Periodo::factory()->create(['nombre' => 'Marzo 2027', 'fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-25']);
    $this->intermedio = Ciclo::factory()->for($this->ingles)->create(['numero' => 5, 'nivel' => NivelCurricular::Intermedio]);
    $this->grupo = grupoConHorario('ING-05-A', $this->intermedio, $this->periodo, [[1, '18:00', '20:00'], [3, '18:00', '20:00']]);
    $this->docente = Docente::factory()->create();
    $this->docente->idiomas()->create(['idioma_id' => $this->ingles->id, 'nivel_maximo' => NivelCurricular::Avanzado]);
    foreach ([1, 3, 5] as $dia) {
        app(GuardarDisponibilidad::class)($this->docente->id, $dia, '16:00', '22:00');
    }
});

function grupoConHorario(string $codigo, Ciclo $ciclo, Periodo $periodo, array $sesiones, EstadoGrupo $estado = EstadoGrupo::Abierto): Grupo
{
    $grupo = Grupo::factory()->create(['codigo' => $codigo, 'ciclo_id' => $ciclo->id, 'periodo_id' => $periodo->id, 'estado' => $estado]);

    foreach ($sesiones as [$dia, $inicio, $fin]) {
        Horario::factory()->create(['grupo_id' => $grupo->id, 'dia_semana' => $dia, 'hora_inicio' => "{$inicio}:00", 'hora_fin' => "{$fin}:00"]);
    }

    return $grupo;
}

function asignar(Grupo $grupo, ?Docente $docente = null): CargaAcademica
{
    return app(AsignarDocente::class)($grupo->id, ($docente ?? test()->docente)->id, test()->usuario);
}

function datosConIdiomas(Docente $docente, array $idiomas): DatosDeDocente
{
    return new DatosDeDocente($docente->persona_id, $docente->correo_institucional, $docente->ruc, $docente->banco, $docente->cuenta_bancaria, CondicionLaboral::Contratado, $idiomas);
}

it('assigns a teacher to a group and records who did it', function (): void {
    $carga = asignar($this->grupo);

    expect(CargaAcademica::query()->sole()->only(['grupo_id', 'docente_id', 'asignado_por']))
        ->toBe(['grupo_id' => $this->grupo->id, 'docente_id' => $this->docente->id, 'asignado_por' => $this->usuario->id])
        ->and($carga->is(CargaAcademica::query()->sole()))->toBeTrue()
        ->and(DB::table('aud_registros')->where('tabla', 'doc_cargas_academicas')->pluck('operacion')->all())->toBe(['INSERT']);
});

it('computes the weeks, dates, days and total hours of each group and the total of the period', function (): void {
    $sabado = grupoConHorario('ING-05-S', $this->intermedio, $this->periodo, [[6, '08:00', '11:30']]);
    app(GuardarDisponibilidad::class)($this->docente->id, 6, '08:00', '12:00');
    asignar($this->grupo);
    asignar($sabado);

    $resumen = app(ResumirCargaDelDocente::class)($this->docente->id);

    expect(array_map(fn (CargaHoraria $carga): array => [$carga->codigo, $carga->idioma, $carga->nivel, $carga->semanas, $carga->inicio->toDateString(), $carga->fin->toDateString(), $carga->dias, $carga->horas()], $resumen->cargas))
        ->toBe([
            ['ING-05-A', 'Inglés', 'Intermedio', 8, '2027-03-01', '2027-04-25', 'Lunes, Miércoles', '32'],
            ['ING-05-S', 'Inglés', 'Intermedio', 8, '2027-03-01', '2027-04-25', 'Sábado', '28'],
        ])
        ->and($resumen->totalesPorPeriodo)->toBe(['Marzo 2027' => '60']);
});

it('counts only the sessions that fall between the period dates', function (): void {
    $corto = Periodo::factory()->create(['nombre' => 'Intensivo', 'fecha_inicio' => '2027-05-06', 'fecha_fin' => '2027-05-18']);
    $grupo = grupoConHorario('ING-05-I', $this->intermedio, $corto, [[1, '18:00', '19:30'], [3, '18:00', '19:30']]);
    asignar($grupo);

    $carga = app(ResumirCargaDelDocente::class)($this->docente->id)->cargas[0];

    expect([$carga->semanas, $carga->dias, $carga->horas()])->toBe([2, 'Lunes, Miércoles', '4.5']);
});

it('refuses an assignment that breaks a rule and leaves the load unchanged', function (Closure $preparar, string $mensaje): void {
    $grupo = $preparar();

    expect(fn (): CargaAcademica => asignar($grupo))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(CargaAcademica::query()->where('grupo_id', $grupo->id)->exists())->toBeFalse();
})->with([
    'teacher on leave' => [function (): Grupo {
        app(CambiarEstadoDocente::class)(test()->docente->id, EstadoDocente::DeLicencia);

        return test()->grupo;
    }, 'está De licencia y no puede recibir carga académica.'],
    'language not enabled' => [fn (): Grupo => grupoConHorario('FRA-01-A', Ciclo::factory()->for(test()->frances)->create(['numero' => 1, 'nivel' => NivelCurricular::Basico]), test()->periodo, [[5, '18:00', '20:00']]),
        'no está habilitado para dictar Francés hasta el nivel Básico que requiere el grupo FRA-01-A.'],
    'level above the authorized one' => [function (): Grupo {
        test()->docente->idiomas()->update(['nivel_maximo' => NivelCurricular::Basico]);

        return test()->grupo;
    }, 'no está habilitado para dictar Inglés hasta el nivel Intermedio que requiere el grupo ING-05-A.'],
    'session outside availability' => [fn (): Grupo => grupoConHorario('ING-05-M', test()->intermedio, test()->periodo, [[2, '18:00', '20:00']]),
        'La sesión del Martes de 18:00 a 20:00 del grupo ING-05-M está fuera de la disponibilidad del docente.'],
    'session longer than the window' => [fn (): Grupo => grupoConHorario('ING-05-L', test()->intermedio, test()->periodo, [[5, '21:00', '22:30']]),
        'La sesión del Viernes de 21:00 a 22:30 del grupo ING-05-L está fuera de la disponibilidad del docente.'],
    'schedule clash in the same period' => [function (): Grupo {
        asignar(test()->grupo);

        return grupoConHorario('ING-05-B', test()->intermedio, test()->periodo, [[3, '19:00', '21:00']]);
    }, 'ya dicta el grupo ING-05-A el Miércoles de 18:00 a 20:00, que se cruza con el grupo ING-05-B.'],
    'clash with an overlapping period' => [function (): Grupo {
        asignar(test()->grupo);
        $traslapado = Periodo::factory()->create(['fecha_inicio' => '2027-04-19', 'fecha_fin' => '2027-06-13']);

        return grupoConHorario('ING-05-T', test()->intermedio, $traslapado, [[1, '19:00', '21:00']]);
    }, 'ya dicta el grupo ING-05-A el Lunes de 18:00 a 20:00, que se cruza con el grupo ING-05-T.'],
    'cancelled group' => [fn (): Grupo => grupoConHorario('ING-05-C', test()->intermedio, test()->periodo, [[5, '18:00', '20:00']], EstadoGrupo::Cancelado),
        'El grupo ING-05-C está Cancelado y ya no admite cambios.'],
]);

it('refuses to assign the teacher a group already in charge', function (): void {
    asignar($this->grupo);

    expect(fn (): CargaAcademica => asignar($this->grupo))->toThrow(ReglaDeNegocioIncumplida::class, "El grupo ING-05-A ya está a cargo del docente {$this->docente->codigo}.")
        ->and(CargaAcademica::query()->count())->toBe(1);
});

it('allows sessions that only touch, groups of periods that do not overlap and cancelled groups', function (): void {
    asignar($this->grupo);
    $contiguo = grupoConHorario('ING-05-D', $this->intermedio, $this->periodo, [[1, '20:00', '22:00']]);
    $siguiente = Periodo::factory()->create(['fecha_inicio' => '2027-04-26', 'fecha_fin' => '2027-06-20']);
    $despues = grupoConHorario('ING-05-E', $this->intermedio, $siguiente, [[1, '18:00', '20:00']]);
    $otro = grupoConHorario('ING-05-F', $this->intermedio, $this->periodo, [[3, '19:00', '21:00']]);
    asignar($contiguo);
    asignar($despues);
    Grupo::query()->whereKey($this->grupo->id)->update(['estado' => EstadoGrupo::Cancelado]);
    asignar($otro);

    expect(CargaAcademica::query()->where('docente_id', $this->docente->id)->count())->toBe(4);
});

it('replaces the teacher of a group and removes the assignment', function (): void {
    $otro = Docente::factory()->create();
    $otro->idiomas()->create(['idioma_id' => $this->ingles->id, 'nivel_maximo' => NivelCurricular::Intermedio]);
    app(GuardarDisponibilidad::class)($otro->id, 1, '18:00', '20:00');
    app(GuardarDisponibilidad::class)($otro->id, 3, '18:00', '20:00');
    asignar($this->grupo);

    asignar($this->grupo, $otro);
    expect(CargaAcademica::query()->sole()->docente_id)->toBe($otro->id);

    app(QuitarDocenteDelGrupo::class)($this->grupo->id);
    expect(CargaAcademica::query()->exists())->toBeFalse()
        ->and(fn () => app(QuitarDocenteDelGrupo::class)($this->grupo->id))->toThrow(ReglaDeNegocioIncumplida::class, 'El grupo ING-05-A no tiene docente asignado.');
});

it('keeps the assignment of a finished group', function (): void {
    asignar($this->grupo);
    Grupo::query()->whereKey($this->grupo->id)->update(['estado' => EstadoGrupo::Finalizado]);

    expect(fn () => app(QuitarDocenteDelGrupo::class)($this->grupo->id))->toThrow(ReglaDeNegocioIncumplida::class, 'El grupo ING-05-A está Finalizado y ya no admite cambios.')
        ->and(CargaAcademica::query()->count())->toBe(1);
});

it('refuses profile changes that would leave an assigned group without a qualified teacher', function (Closure $cambio, string $mensaje): void {
    asignar($this->grupo);
    $antes = [DB::table('doc_docente_idiomas')->get()->toArray(), DB::table('doc_disponibilidades')->get()->toArray(), $this->docente->refresh()->estado];

    expect($cambio)->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and([DB::table('doc_docente_idiomas')->get()->toArray(), DB::table('doc_disponibilidades')->get()->toArray(), $this->docente->refresh()->estado])->toEqual($antes);
})->with([
    'remove the language' => [fn () => app(GuardarDocente::class)(datosConIdiomas(test()->docente, [new IdiomaHabilitado(test()->frances->id, NivelCurricular::Avanzado)]), test()->docente->id),
        'no está habilitado para dictar Inglés hasta el nivel Intermedio que requiere el grupo ING-05-A.'],
    'lower the level' => [fn () => app(GuardarDocente::class)(datosConIdiomas(test()->docente, [new IdiomaHabilitado(test()->ingles->id, NivelCurricular::Basico)]), test()->docente->id),
        'no está habilitado para dictar Inglés hasta el nivel Intermedio que requiere el grupo ING-05-A.'],
    'remove a needed window' => [fn () => app(EliminarDisponibilidad::class)(test()->docente->id, test()->docente->disponibilidades()->where('dia_semana', 3)->sole()->id),
        'La sesión del Miércoles de 18:00 a 20:00 del grupo ING-05-A está fuera de la disponibilidad del docente.'],
    'narrow a needed window' => [fn () => app(GuardarDisponibilidad::class)(test()->docente->id, 1, '19:00', '22:00', test()->docente->disponibilidades()->where('dia_semana', 1)->sole()->id),
        'La sesión del Lunes de 18:00 a 20:00 del grupo ING-05-A está fuera de la disponibilidad del docente.'],
    'leave the active status' => [fn () => app(CambiarEstadoDocente::class)(test()->docente->id, EstadoDocente::Inactivo),
        'tiene grupos a cargo (ING-05-A); reasígnelos antes de cambiar su estado.'],
]);

it('lets the profile change once the assigned group is finished', function (): void {
    asignar($this->grupo);
    Grupo::query()->whereKey($this->grupo->id)->update(['estado' => EstadoGrupo::Finalizado]);

    app(EliminarDisponibilidad::class)($this->docente->id, $this->docente->disponibilidades()->where('dia_semana', 3)->sole()->id);
    app(CambiarEstadoDocente::class)($this->docente->id, EstadoDocente::Inactivo);

    expect($this->docente->refresh()->estado)->toBe(EstadoDocente::Inactivo);
});

it('keeps a cancelled group in the list but out of the period total', function (): void {
    $sabado = grupoConHorario('ING-05-S', $this->intermedio, $this->periodo, [[5, '18:00', '20:00']]);
    asignar($this->grupo);
    asignar($sabado);
    Grupo::query()->whereKey($sabado->id)->update(['estado' => EstadoGrupo::Cancelado]);

    $resumen = app(ResumirCargaDelDocente::class)($this->docente->id);

    expect(array_map(fn (CargaHoraria $carga): array => [$carga->codigo, $carga->cancelado], $resumen->cargas))->toBe([['ING-05-A', false], ['ING-05-S', true]])
        ->and($resumen->totalesPorPeriodo)->toBe(['Marzo 2027' => '32']);
});

it('refuses to remove a group that already moved to another teacher', function (): void {
    $otro = Docente::factory()->create();
    $otro->idiomas()->create(['idioma_id' => $this->ingles->id, 'nivel_maximo' => NivelCurricular::Avanzado]);
    app(GuardarDisponibilidad::class)($otro->id, 1, '16:00', '22:00');
    app(GuardarDisponibilidad::class)($otro->id, 3, '16:00', '22:00');
    asignar($this->grupo);
    asignar($this->grupo, $otro);

    expect(fn () => app(QuitarDocenteDelGrupo::class)($this->grupo->id, $this->docente->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El grupo ING-05-A ya no está a cargo de este docente.')
        ->and(CargaAcademica::query()->sole()->docente_id)->toBe($otro->id);
});

it('removes the group when the teacher of the page still has it', function (): void {
    asignar($this->grupo);

    app(QuitarDocenteDelGrupo::class)($this->grupo->id, $this->docente->id);

    expect(CargaAcademica::query()->exists())->toBeFalse();
});

it('accepts a session covered by two adjacent availability windows', function (): void {
    $otro = Docente::factory()->create();
    $otro->idiomas()->create(['idioma_id' => $this->ingles->id, 'nivel_maximo' => NivelCurricular::Avanzado]);
    app(GuardarDisponibilidad::class)($otro->id, 2, '16:00', '19:00');
    app(GuardarDisponibilidad::class)($otro->id, 2, '19:00', '22:00');

    asignar(grupoConHorario('ING-05-ADJ', $this->intermedio, $this->periodo, [[2, '18:00', '20:00']]), $otro);

    expect(CargaAcademica::query()->where('docente_id', $otro->id)->count())->toBe(1);
});

it('still refuses a session that falls in the gap between two windows', function (): void {
    $otro = Docente::factory()->create();
    $otro->idiomas()->create(['idioma_id' => $this->ingles->id, 'nivel_maximo' => NivelCurricular::Avanzado]);
    app(GuardarDisponibilidad::class)($otro->id, 2, '16:00', '19:00');
    app(GuardarDisponibilidad::class)($otro->id, 2, '19:30', '22:00');

    expect(fn (): CargaAcademica => asignar(grupoConHorario('ING-05-GAP', $this->intermedio, $this->periodo, [[2, '18:00', '20:00']]), $otro))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La sesión del Martes de 18:00 a 20:00 del grupo ING-05-GAP está fuera de la disponibilidad del docente.');
});
