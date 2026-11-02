<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\CambiarEstadoGrupo;
use App\Modules\Academico\Actions\GuardarGrupo;
use App\Modules\Academico\Actions\GuardarHorario;
use App\Modules\Academico\Data\DatosDeGrupo;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;
use Tests\Support\OfertaAcademica;

function datosDeGrupo(array $cambios = []): DatosDeGrupo
{
    return new DatosDeGrupo(...[
        'periodoId' => test()->periodo->id,
        'cicloId' => test()->ciclo->id,
        'codigo' => 'ING-01',
        'modalidad' => Modalidad::Presencial,
        'esAcelerado' => false,
        'cupoMaximo' => 25,
        'cupoMinimo' => 10,
        'aulaId' => test()->aula->id,
        'enlaceVirtual' => null,
        ...$cambios,
    ]);
}

function matricularEnGrupo(Grupo $grupo, int $estudiantes): void
{
    DB::table('aca_grupos')->where('id', $grupo->id)->update(['matriculados' => $estudiantes]);
}

beforeEach(function (): void {
    $this->periodo = Periodo::factory()->create();
    $this->ciclo = Ciclo::factory()->create();
    $this->aula = Aula::factory()->create(['codigo' => 'A-101', 'capacidad' => 30]);
});

it('allows only the planned group state transitions', function (EstadoGrupo $origen, EstadoGrupo $destino, bool $permitida): void {
    $grupo = OfertaAcademica::prepararParaAbrir(Grupo::factory()->create(['estado' => $origen, 'cupo_minimo' => 0]));

    $cambiar = fn (): Grupo => app(CambiarEstadoGrupo::class)($grupo->id, $destino);

    if ($permitida) {
        expect($cambiar()->estado)->toBe($destino)
            ->and($grupo->refresh()->estado)->toBe($destino);

        return;
    }

    expect($cambiar)->toThrow(ReglaDeNegocioIncumplida::class, "Un grupo {$origen->etiqueta()} no puede pasar a {$destino->etiqueta()}.")
        ->and($grupo->refresh()->estado)->toBe($origen);
})->with([
    'programado → abierto' => [EstadoGrupo::Programado, EstadoGrupo::Abierto, true],
    'programado → cancelado' => [EstadoGrupo::Programado, EstadoGrupo::Cancelado, true],
    'programado → cerrado' => [EstadoGrupo::Programado, EstadoGrupo::Cerrado, false],
    'programado → finalizado' => [EstadoGrupo::Programado, EstadoGrupo::Finalizado, false],
    'abierto → cerrado' => [EstadoGrupo::Abierto, EstadoGrupo::Cerrado, true],
    'abierto → cancelado' => [EstadoGrupo::Abierto, EstadoGrupo::Cancelado, true],
    'abierto → programado' => [EstadoGrupo::Abierto, EstadoGrupo::Programado, false],
    'abierto → finalizado' => [EstadoGrupo::Abierto, EstadoGrupo::Finalizado, false],
    'cerrado → abierto' => [EstadoGrupo::Cerrado, EstadoGrupo::Abierto, true],
    'cerrado → finalizado' => [EstadoGrupo::Cerrado, EstadoGrupo::Finalizado, true],
    'cerrado → cancelado' => [EstadoGrupo::Cerrado, EstadoGrupo::Cancelado, true],
    'cerrado → programado' => [EstadoGrupo::Cerrado, EstadoGrupo::Programado, false],
    'finalizado → abierto' => [EstadoGrupo::Finalizado, EstadoGrupo::Abierto, false],
    'finalizado → cancelado' => [EstadoGrupo::Finalizado, EstadoGrupo::Cancelado, false],
    'cancelado → abierto' => [EstadoGrupo::Cancelado, EstadoGrupo::Abierto, false],
    'cancelado → programado' => [EstadoGrupo::Cancelado, EstadoGrupo::Programado, false],
]);

it('treats a change to the current state as already done', function (): void {
    $grupo = Grupo::factory()->create(['estado' => EstadoGrupo::Finalizado]);

    expect(app(CambiarEstadoGrupo::class)($grupo->id, EstadoGrupo::Finalizado)->estado)->toBe(EstadoGrupo::Finalizado);
});

it('refuses to cancel a group with enrolled students', function (): void {
    $grupo = Grupo::factory()->create(['estado' => EstadoGrupo::Abierto]);
    matricularEnGrupo($grupo, 3);

    expect(fn (): Grupo => app(CambiarEstadoGrupo::class)($grupo->id, EstadoGrupo::Cancelado))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'No se puede cancelar un grupo con 3 estudiantes matriculados.')
        ->and($grupo->refresh()->estado)->toBe(EstadoGrupo::Abierto);
});

it('creates a scheduled face-to-face group with no enrolled students', function (): void {
    $grupo = app(GuardarGrupo::class)(datosDeGrupo(['esAcelerado' => true]));

    expect($grupo->refresh())
        ->codigo->toBe('ING-01')
        ->aula_id->toBe($this->aula->id)
        ->es_acelerado->toBeTrue()
        ->estado->toBe(EstadoGrupo::Programado)
        ->matriculados->toBe(0);
});

it('updates a group without touching its state or enrolled count', function (): void {
    $grupo = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id, 'estado' => EstadoGrupo::Abierto]);
    matricularEnGrupo($grupo, 5);

    app(GuardarGrupo::class)(datosDeGrupo(['codigo' => 'ING-02', 'cupoMaximo' => 20]), $grupo->id);

    expect($grupo->refresh())
        ->codigo->toBe('ING-02')
        ->cupo_maximo->toBe(20)
        ->estado->toBe(EstadoGrupo::Abierto)
        ->matriculados->toBe(5);
});

it('stores a virtual group with its link and no classroom', function (): void {
    $grupo = app(GuardarGrupo::class)(datosDeGrupo(['modalidad' => Modalidad::Virtual, 'aulaId' => null, 'enlaceVirtual' => 'https://meet.example.test/ing-01', 'cupoMaximo' => 40]));

    expect($grupo->refresh())
        ->aula_id->toBeNull()
        ->enlace_virtual->toBe('https://meet.example.test/ing-01')
        ->cupo_maximo->toBe(40);
});

it('enforces the group rules with business errors', function (array $cambios, string $mensaje): void {
    expect(fn (): Grupo => app(GuardarGrupo::class)(datosDeGrupo($cambios)))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(Grupo::query()->count())->toBe(0);
})->with([
    'virtual without link' => [['modalidad' => Modalidad::Virtual, 'aulaId' => null, 'enlaceVirtual' => null], 'Un grupo virtual necesita el enlace de la clase.'],
    'virtual with classroom' => [['modalidad' => Modalidad::Virtual, 'enlaceVirtual' => 'https://meet.example.test/x'], 'Un grupo virtual no usa aula.'],
    'face-to-face without classroom' => [['aulaId' => null], 'Un grupo presencial o semipresencial necesita un aula.'],
    'blended without classroom' => [['modalidad' => Modalidad::Semipresencial, 'aulaId' => null], 'Un grupo presencial o semipresencial necesita un aula.'],
    'quota above classroom capacity' => [['cupoMaximo' => 31], 'El aula A-101 tiene capacidad para 30 estudiantes; el cupo máximo no puede superarla.'],
    'minimum above maximum' => [['cupoMinimo' => 26], 'El cupo mínimo no puede superar el cupo máximo.'],
]);

it('refuses a code already used by another group of the period', function (): void {
    $existente = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'codigo' => 'ING-01']);

    expect(fn (): Grupo => app(GuardarGrupo::class)(datosDeGrupo()))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Ya existe un grupo con el código ING-01 en este periodo.')
        ->and(Grupo::query()->pluck('id')->all())->toBe([$existente->id]);
});

it('refuses a maximum quota below the enrolled students', function (): void {
    $grupo = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id]);
    matricularEnGrupo($grupo, 12);

    expect(fn (): Grupo => app(GuardarGrupo::class)(datosDeGrupo(['cupoMaximo' => 11]), $grupo->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El cupo máximo no puede ser menor que los 12 estudiantes matriculados.')
        ->and($grupo->refresh()->cupo_maximo)->toBe(25);
});

it('refuses moving a group to a classroom another group of the period uses at the same time', function (): void {
    $otraAula = Aula::factory()->create(['codigo' => 'A-202']);
    $ocupante = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $otraAula->id, 'codigo' => 'FRA-01']);
    Horario::factory()->for($ocupante)->create(['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    $grupo = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id]);
    Horario::factory()->for($grupo)->create(['dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '11:00']);

    expect(fn (): Grupo => app(GuardarGrupo::class)(datosDeGrupo(['aulaId' => $otraAula->id]), $grupo->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El aula A-202 ya está ocupada por el grupo FRA-01 el Lunes de 08:00 a 10:00.')
        ->and($grupo->refresh()->aula_id)->toBe($this->aula->id);
});

it('adds a schedule line to a group', function (): void {
    $grupo = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id]);

    $horario = app(GuardarHorario::class)($grupo->id, 3, '18:00', '20:00');

    expect($horario->refresh())
        ->grupo_id->toBe($grupo->id)
        ->dia_semana->toBe(3)
        ->hora_inicio->toBe('18:00:00')
        ->hora_fin->toBe('20:00:00');
});

it('updates a schedule line without clashing with itself', function (): void {
    $grupo = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id]);
    $horario = Horario::factory()->for($grupo)->create(['dia_semana' => 2, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);

    app(GuardarHorario::class)($grupo->id, 2, '09:00', '11:00', $horario->id);

    expect($horario->refresh())->hora_inicio->toBe('09:00:00')->hora_fin->toBe('11:00:00')
        ->and(Horario::query()->count())->toBe(1);
});

it('enforces the schedule rules with business errors', function (int $dia, string $inicio, string $fin, string $mensaje): void {
    $grupo = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id]);
    Horario::factory()->for($grupo)->create(['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);

    expect(fn (): Horario => app(GuardarHorario::class)($grupo->id, $dia, $inicio, $fin))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(Horario::query()->count())->toBe(1);
})->with([
    'weekday zero' => [0, '08:00', '10:00', 'El día debe estar entre 1 (lunes) y 7 (domingo).'],
    'weekday eight' => [8, '08:00', '10:00', 'El día debe estar entre 1 (lunes) y 7 (domingo).'],
    'end before start' => [2, '10:00', '08:00', 'La hora de fin debe ser posterior a la de inicio.'],
    'end equal to start' => [2, '10:00', '10:00', 'La hora de fin debe ser posterior a la de inicio.'],
    'overlaps own line' => [1, '09:30', '11:00', 'El horario se cruza con otra sesión del grupo el Lunes de 08:00 a 10:00.'],
]);

it('refuses a schedule line when another group of the period uses the classroom at that time', function (): void {
    $ocupante = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id, 'codigo' => 'FRA-01']);
    Horario::factory()->for($ocupante)->create(['dia_semana' => 5, 'hora_inicio' => '16:00', 'hora_fin' => '18:00']);
    $grupo = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id]);

    expect(fn (): Horario => app(GuardarHorario::class)($grupo->id, 5, '17:00', '19:00'))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El aula A-101 ya está ocupada por el grupo FRA-01 el Viernes de 16:00 a 18:00.')
        ->and($grupo->horarios()->count())->toBe(0);
});

it('allows the same classroom and time in another period that does not overlap, back to back, or for a virtual group', function (): void {
    $abril = Periodo::factory()->create(['fecha_inicio' => '2027-04-01', 'fecha_fin' => '2027-04-30']);
    $otroPeriodo = Grupo::factory()->create(['periodo_id' => $abril->id, 'aula_id' => $this->aula->id]);
    Horario::factory()->for($otroPeriodo)->create(['dia_semana' => 5, 'hora_inicio' => '16:00', 'hora_fin' => '18:00']);
    $contiguo = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id]);
    Horario::factory()->for($contiguo)->create(['dia_semana' => 5, 'hora_inicio' => '14:00', 'hora_fin' => '16:00']);
    $virtual = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'modalidad' => Modalidad::Virtual, 'enlace_virtual' => 'https://meet.example.test/v']);
    Horario::factory()->for($virtual)->create(['dia_semana' => 5, 'hora_inicio' => '16:00', 'hora_fin' => '18:00']);
    $grupo = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id]);

    app(GuardarHorario::class)($grupo->id, 5, '16:00', '18:00');
    $otroVirtual = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'modalidad' => Modalidad::Virtual, 'enlace_virtual' => 'https://meet.example.test/w']);
    app(GuardarHorario::class)($otroVirtual->id, 5, '16:00', '18:00');

    expect($grupo->horarios()->count())->toBe(1)
        ->and($otroVirtual->horarios()->count())->toBe(1);
});

it('frees the classroom of a cancelled group', function (): void {
    $cancelado = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id, 'estado' => EstadoGrupo::Cancelado]);
    Horario::factory()->for($cancelado)->create(['dia_semana' => 4, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    $grupo = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id]);

    app(GuardarHorario::class)($grupo->id, 4, '08:00', '10:00');

    expect($grupo->horarios()->count())->toBe(1);
});

it('refuses new groups on an inactive language, cycle or classroom', function (string $caso, string $mensaje): void {
    match ($caso) {
        'idioma' => $this->ciclo->idioma->update(['activo' => false]),
        'ciclo' => $this->ciclo->update(['activo' => false]),
        'aula' => $this->aula->update(['activo' => false]),
    };

    expect(fn (): Grupo => app(GuardarGrupo::class)(datosDeGrupo()))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
    expect(Grupo::query()->count())->toBe(0);
})->with([
    'language' => ['idioma', 'no está disponible para nuevos grupos'],
    'cycle' => ['ciclo', 'El ciclo seleccionado no está disponible para nuevos grupos.'],
    'classroom' => ['aula', 'El aula A-101 no está disponible para nuevos grupos.'],
]);

it('keeps saving an existing group whose classroom was deactivated later', function (): void {
    $grupo = app(GuardarGrupo::class)(datosDeGrupo());
    $this->aula->update(['activo' => false]);

    app(GuardarGrupo::class)(datosDeGrupo(['cupoMaximo' => 20]), $grupo->id);

    expect($grupo->refresh()->cupo_maximo)->toBe(20)
        ->and($grupo->aula_id)->toBe($this->aula->id);
});

it('freezes cancelled and finished groups and their schedules', function (EstadoGrupo $estado): void {
    $grupo = app(GuardarGrupo::class)(datosDeGrupo());
    DB::table('aca_grupos')->where('id', $grupo->id)->update(['estado' => $estado->value]);

    expect(fn (): Grupo => app(GuardarGrupo::class)(datosDeGrupo(['cupoMaximo' => 20]), $grupo->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'ya no admite cambios')
        ->and(fn (): Horario => app(GuardarHorario::class)($grupo->id, 1, '08:00', '10:00'))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'ya no admite cambios');
    expect($grupo->refresh()->cupo_maximo)->toBe(25)
        ->and(Horario::query()->where('grupo_id', $grupo->id)->exists())->toBeFalse();
})->with([EstadoGrupo::Cancelado, EstadoGrupo::Finalizado]);

it('refuses renaming a group to a code already used in its period', function (): void {
    app(GuardarGrupo::class)(datosDeGrupo(['codigo' => 'ING-02']));
    $grupo = app(GuardarGrupo::class)(datosDeGrupo());

    expect(fn (): Grupo => app(GuardarGrupo::class)(datosDeGrupo(['codigo' => 'ING-02']), $grupo->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Ya existe un grupo con el código ING-02 en este periodo.');
});

it('refuses turning a virtual group face-to-face in a classroom another group uses at that time', function (): void {
    $ocupante = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id, 'codigo' => 'FRA-01']);
    Horario::factory()->for($ocupante)->create(['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    $virtual = app(GuardarGrupo::class)(datosDeGrupo(['modalidad' => Modalidad::Virtual, 'aulaId' => null, 'enlaceVirtual' => 'https://meet.example.test/ing']));
    Horario::factory()->for($virtual)->create(['dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '11:00']);

    expect(fn (): Grupo => app(GuardarGrupo::class)(datosDeGrupo(['modalidad' => Modalidad::Presencial]), $virtual->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El aula A-101 ya está ocupada por el grupo FRA-01');
});
