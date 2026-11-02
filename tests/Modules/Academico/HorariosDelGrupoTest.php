<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\GuardarHorario;
use App\Modules\Academico\Actions\QuitarHorario;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Tests\Support\OfertaAcademica;

function horarioDelGrupo(Grupo $grupo): array
{
    return Horario::query()->where('grupo_id', $grupo->id)->orderBy('id')->get()
        ->map(fn (Horario $horario): array => [$horario->dia_semana, $horario->hora_inicio, $horario->hora_fin])->all();
}

beforeEach(function (): void {
    $this->grupo = Grupo::factory()->create(['codigo' => 'ING-01-A', 'estado' => EstadoGrupo::Abierto]);
    $this->sesion = Horario::factory()->for($this->grupo)->create(['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
});

dataset('cambios de horario', [
    'add' => [fn () => app(GuardarHorario::class)(test()->grupo->id, 3, '08:00', '10:00')],
    'edit' => [fn () => app(GuardarHorario::class)(test()->grupo->id, 2, '08:00', '10:00', test()->sesion->id)],
    'remove' => [fn () => app(QuitarHorario::class)(test()->grupo->id, test()->sesion->id)],
]);

it('refuses to change the schedule of a group with enrolled students', function (Closure $cambiar): void {
    OfertaAcademica::matricular($this->grupo, 3);

    expect($cambiar)->toThrow(ReglaDeNegocioIncumplida::class, 'El horario del grupo ING-01-A no puede cambiar porque ya tiene estudiantes matriculados o clases dictadas.')
        ->and(horarioDelGrupo($this->grupo))->toBe([[1, '08:00:00', '10:00:00']]);
})->with('cambios de horario');

it('refuses to change the schedule of a group with a class already held', function (Closure $cambiar): void {
    OfertaAcademica::registrarClase($this->grupo);

    expect($cambiar)->toThrow(ReglaDeNegocioIncumplida::class, 'El horario del grupo ING-01-A no puede cambiar porque ya tiene estudiantes matriculados o clases dictadas.')
        ->and(horarioDelGrupo($this->grupo))->toBe([[1, '08:00:00', '10:00:00']]);
})->with('cambios de horario');

it('changes the schedule of an open group without students or held classes', function (): void {
    app(GuardarHorario::class)($this->grupo->id, 3, '08:00', '10:00');
    app(GuardarHorario::class)($this->grupo->id, 2, '09:00', '11:00', $this->sesion->id);

    expect(horarioDelGrupo($this->grupo))->toBe([[2, '09:00:00', '11:00:00'], [3, '08:00:00', '10:00:00']]);

    app(QuitarHorario::class)($this->grupo->id, $this->sesion->id);

    expect(horarioDelGrupo($this->grupo))->toBe([[3, '08:00:00', '10:00:00']]);
});

it('removes a schedule line of a scheduled group', function (): void {
    $this->grupo->update(['estado' => EstadoGrupo::Programado]);

    app(QuitarHorario::class)($this->grupo->id, $this->sesion->id);

    expect(horarioDelGrupo($this->grupo))->toBe([]);
});

it('refuses to remove a schedule line of a finished group', function (): void {
    $this->grupo->update(['estado' => EstadoGrupo::Finalizado]);

    expect(fn () => app(QuitarHorario::class)($this->grupo->id, $this->sesion->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El grupo ING-01-A está Finalizado y ya no admite cambios.')
        ->and(horarioDelGrupo($this->grupo))->toBe([[1, '08:00:00', '10:00:00']]);
});

it('treats removing a schedule line that is already gone as done', function (): void {
    $this->sesion->delete();

    app(QuitarHorario::class)($this->grupo->id, $this->sesion->id);

    expect(horarioDelGrupo($this->grupo))->toBe([]);
});
