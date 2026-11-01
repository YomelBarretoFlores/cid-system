<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\GuardarGrupo;
use App\Modules\Academico\Actions\GuardarHorario;
use App\Modules\Academico\Data\DatosDeGrupo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\Periodos\Pages\EditPeriodo;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Actions\GuardarDisponibilidad;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Livewire\Livewire;
use Tests\Support\PortalDocente;

// March runs 1–31, the bimester 15 March–15 May overlaps it, and April 1–30 overlaps only the bimester.
beforeEach(function (): void {
    $this->marzo = Periodo::factory()->create(['numero' => 3, 'fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-03-31']);
    $this->bimestre = Periodo::factory()->create(['numero' => 5, 'fecha_inicio' => '2027-03-15', 'fecha_fin' => '2027-05-15', 'matricula_fin' => '2027-03-15']);
    $this->abril = Periodo::factory()->create(['numero' => 4, 'fecha_inicio' => '2027-04-01', 'fecha_fin' => '2027-04-30', 'matricula_inicio' => '2027-03-20', 'matricula_fin' => '2027-04-05']);
    $this->aula = Aula::factory()->create(['codigo' => 'A-101', 'capacidad' => 30]);
    $this->ocupante = Grupo::factory()->create(['periodo_id' => $this->marzo->id, 'aula_id' => $this->aula->id, 'codigo' => 'FRA-01']);
    Horario::factory()->for($this->ocupante)->create(['dia_semana' => 5, 'hora_inicio' => '16:00', 'hora_fin' => '18:00']);
});

function grupoEnAula(Periodo $periodo, string $codigo = 'ING-01'): Grupo
{
    return Grupo::factory()->create(['periodo_id' => $periodo->id, 'aula_id' => test()->aula->id, 'codigo' => $codigo]);
}

it('refuses a schedule line when a group of another period with overlapping dates uses the classroom at that time', function (): void {
    $grupo = grupoEnAula($this->bimestre);

    expect(fn (): Horario => app(GuardarHorario::class)($grupo->id, 5, '17:00', '19:00'))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El aula A-101 ya está ocupada por el grupo FRA-01 el Viernes de 16:00 a 18:00.')
        ->and($grupo->horarios()->count())->toBe(0);
});

it('keeps the classroom free for a period whose dates do not overlap', function (): void {
    $grupo = grupoEnAula($this->abril);

    app(GuardarHorario::class)($grupo->id, 5, '16:00', '18:00');

    expect($grupo->horarios()->count())->toBe(1);
});

it('refuses moving a group to a classroom a group of an overlapping period uses at the same time', function (): void {
    $grupo = Grupo::factory()->create(['periodo_id' => $this->bimestre->id, 'aula_id' => Aula::factory()->create(['capacidad' => 30])->id, 'codigo' => 'ING-02']);
    Horario::factory()->for($grupo)->create(['dia_semana' => 5, 'hora_inicio' => '17:00', 'hora_fin' => '19:00']);
    $datos = new DatosDeGrupo($this->bimestre->id, $grupo->ciclo_id, 'ING-02', Modalidad::Presencial, false, 25, 10, $this->aula->id);

    expect(fn (): Grupo => app(GuardarGrupo::class)($datos, $grupo->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El aula A-101 ya está ocupada por el grupo FRA-01 el Viernes de 16:00 a 18:00.')
        ->and($grupo->refresh()->aula_id)->not->toBe($this->aula->id);
});

it('refuses new dates for a period that make one of its groups share a classroom with another period, and keeps the dates', function (): void {
    $grupo = grupoEnAula($this->abril);
    Horario::factory()->for($grupo)->create(['dia_semana' => 5, 'hora_inicio' => '16:00', 'hora_fin' => '18:00']);
    $gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoAcademico::valores()]);

    Livewire::actingAs($gestor)
        ->test(EditPeriodo::class, ['record' => $this->abril->getRouteKey()])
        ->fillForm(['fecha_inicio' => '2027-03-25', 'matricula_inicio' => '2027-03-10', 'matricula_fin' => '2027-03-20'])
        ->call('save')
        ->assertNotified('El aula A-101 ya está ocupada por el grupo FRA-01 el Viernes de 16:00 a 18:00, en un periodo que se cruza con las nuevas fechas del grupo ING-01.');

    expect($this->abril->refresh()->fecha_inicio->toDateString())->toBe('2027-04-01');
});

it('refuses new dates for a period that make a teacher teach two groups at the same time', function (): void {
    [, $docente] = PortalDocente::docente();
    $marzo = Grupo::factory()->create(['periodo_id' => $this->marzo->id, 'codigo' => 'ING-M']);
    Horario::factory()->for($marzo)->create(['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    $abril = Grupo::factory()->create(['periodo_id' => $this->abril->id, 'codigo' => 'ING-A']);
    Horario::factory()->for($abril)->create(['dia_semana' => 1, 'hora_inicio' => '19:00', 'hora_fin' => '21:00']);
    app(GuardarDisponibilidad::class)($docente->id, 1, '17:00', '22:00');
    PortalDocente::asignar($marzo, $docente);
    PortalDocente::asignar($abril, $docente);
    $gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoAcademico::valores()]);

    Livewire::actingAs($gestor)
        ->test(EditPeriodo::class, ['record' => $this->abril->getRouteKey()])
        ->fillForm(['fecha_inicio' => '2027-03-25', 'matricula_inicio' => '2027-03-10', 'matricula_fin' => '2027-03-20'])
        ->call('save')
        ->assertNotified("El docente {$docente->codigo} ya dicta el grupo ING-M el Lunes de 18:00 a 20:00, que se cruza con el grupo ING-A.");

    expect($this->abril->refresh()->fecha_inicio->toDateString())->toBe('2027-04-01');
});

it('accepts new dates that cause no clash', function (): void {
    $grupo = grupoEnAula($this->abril);
    Horario::factory()->for($grupo)->create(['dia_semana' => 5, 'hora_inicio' => '16:00', 'hora_fin' => '18:00']);
    $gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoAcademico::valores()]);

    Livewire::actingAs($gestor)
        ->test(EditPeriodo::class, ['record' => $this->abril->getRouteKey()])
        ->fillForm(['fecha_fin' => '2027-05-05'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->abril->refresh()->fecha_fin->toDateString())->toBe('2027-05-05');
});

it('accepts new dates for a period that only keep a classroom share saved before the rule', function (): void {
    $legado = grupoEnAula($this->bimestre, 'ING-L');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE aca_horarios DISABLE TRIGGER aca_horarios_aula_libre');
    Horario::factory()->for($legado)->create(['dia_semana' => 5, 'hora_inicio' => '16:00', 'hora_fin' => '18:00']);
    DB::statement('ALTER TABLE aca_horarios ENABLE TRIGGER aca_horarios_aula_libre');
    $gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoAcademico::valores()]);

    Livewire::actingAs($gestor)
        ->test(EditPeriodo::class, ['record' => $this->bimestre->getRouteKey()])
        ->fillForm(['fecha_fin' => '2027-05-10'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotNotified('El aula A-101 ya está ocupada por el grupo FRA-01 el Viernes de 16:00 a 18:00, en un periodo que se cruza con las nuevas fechas del grupo ING-L.');

    expect($this->bimestre->refresh()->fecha_fin->toDateString())->toBe('2027-05-10');
});
