<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\Periodos\Pages\CreatePeriodo;
use App\Modules\Academico\Filament\Resources\Periodos\Pages\EditPeriodo;
use App\Modules\Academico\Filament\Resources\Periodos\Pages\ListPeriodos;
use App\Modules\Academico\Filament\Resources\Periodos\PeriodoResource;
use App\Modules\Academico\Filament\Resources\Periodos\Schemas\PeriodoForm;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Shared\Auth\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoAcademico::valores()]);
    $this->lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value]);
    $this->periodo = Periodo::factory()->create(['anio' => 2027, 'numero' => 3, 'nombre' => 'Marzo 2027']);
});

function datosDePeriodo(array $cambios = []): array
{
    return [
        'anio' => 2027,
        'numero' => 4,
        'nombre' => 'Abril 2027',
        'fecha_inicio' => '2027-04-01',
        'fecha_fin' => '2027-04-30',
        'matricula_inicio' => '2027-03-20',
        'matricula_fin' => '2027-04-05',
        ...$cambios,
    ];
}

it('keeps the period screens away from users without the academic read permission', function (): void {
    $ajeno = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($ajeno)->get(PeriodoResource::getUrl('index'))->assertForbidden();
    $this->actingAs($ajeno)->get(PeriodoResource::getUrl('edit', ['record' => $this->periodo]))->assertForbidden();
});

it('lets readers list periods but not create or edit them', function (): void {
    $this->actingAs($this->lector)->get(PeriodoResource::getUrl('index'))->assertOk();
    $this->actingAs($this->lector)->get(PeriodoResource::getUrl('create'))->assertForbidden();
    $this->actingAs($this->lector)->get(PeriodoResource::getUrl('edit', ['record' => $this->periodo]))->assertForbidden();
});

it('lists periods with their dates, enrollment window and state', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ListPeriodos::class)
        ->assertCanSeeTableRecords([$this->periodo])
        ->assertTableColumnFormattedStateSet('fecha_inicio', '01/03/2027', $this->periodo)
        ->assertTableColumnFormattedStateSet('matricula_fin', '05/03/2027', $this->periodo)
        ->assertTableColumnFormattedStateSet('estado', 'Planificado', $this->periodo);
});

it('filters periods by state and by year', function (): void {
    $enCurso = Periodo::factory()->create(['anio' => 2027, 'numero' => 5, 'estado' => EstadoPeriodo::EnCurso]);
    $otroAnio = Periodo::factory()->create(['anio' => 2028, 'numero' => 3, 'fecha_inicio' => '2028-03-01', 'fecha_fin' => '2028-03-31', 'matricula_inicio' => '2028-02-15', 'matricula_fin' => '2028-03-05']);
    $this->actingAs($this->gestor);

    Livewire::test(ListPeriodos::class)
        ->filterTable('estado', EstadoPeriodo::EnCurso->value)
        ->assertCanSeeTableRecords([$enCurso])
        ->assertCanNotSeeTableRecords([$this->periodo, $otroAnio])
        ->resetTableFilters()
        ->filterTable('anio', 2028)
        ->assertCanSeeTableRecords([$otroAnio])
        ->assertCanNotSeeTableRecords([$this->periodo, $enCurso]);
});

it('creates a planned period', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreatePeriodo::class)
        ->fillForm(datosDePeriodo())
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Periodo::query()->where('anio', 2027)->where('numero', 4)->sole())
        ->nombre->toBe('Abril 2027')
        ->estado->toBe(EstadoPeriodo::Planificado)
        ->fecha_fin->toDateString()->toBe('2027-04-30')
        ->matricula_inicio->toDateString()->toBe('2027-03-20');
});

it('refuses periods that break the calendar rules', function (array $cambios, string $campo): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreatePeriodo::class)
        ->fillForm(datosDePeriodo($cambios))
        ->call('create')
        ->assertHasFormErrors([$campo]);

    expect(Periodo::query()->count())->toBe(1);
})->with([
    'year before 2000' => [['anio' => 1999], 'anio'],
    'year after 2100' => [['anio' => 2101], 'anio'],
    'number zero' => [['numero' => 0], 'numero'],
    'number above twelve' => [['numero' => 13], 'numero'],
    'end on the start day' => [['fecha_fin' => '2027-04-01'], 'fecha_fin'],
    'end before the start' => [['fecha_fin' => '2027-03-31'], 'fecha_fin'],
    'enrollment ending before it starts' => [['matricula_fin' => '2027-03-19'], 'matricula_fin'],
    'enrollment ending after the period' => [['matricula_fin' => '2027-05-01'], 'matricula_fin'],
    'grade calendar without its end' => [['actas_inicio' => '2027-04-28'], 'actas_fin'],
    'grade calendar without its start' => [['actas_fin' => '2027-05-05'], 'actas_inicio'],
    'grade calendar ending before it starts' => [['actas_inicio' => '2027-04-28', 'actas_fin' => '2027-04-27'], 'actas_fin'],
]);

it('sets the grade calendar of a period, which may run past the end of classes', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreatePeriodo::class)
        ->fillForm(datosDePeriodo(['actas_inicio' => '2027-04-28', 'actas_fin' => '2027-05-05']))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Periodo::query()->where('numero', 4)->sole())
        ->actas_inicio->toDateString()->toBe('2027-04-28')
        ->actas_fin->toDateString()->toBe('2027-05-05');
});

it('accepts an enrollment window of a single day ending with the period', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreatePeriodo::class)
        ->fillForm(datosDePeriodo(['matricula_inicio' => '2027-04-30', 'matricula_fin' => '2027-04-30']))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Periodo::query()->count())->toBe(2);
});

it('refuses a repeated year and number', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreatePeriodo::class)
        ->fillForm(datosDePeriodo(['numero' => 3]))
        ->call('create')
        ->assertHasFormErrors(['numero' => PeriodoForm::PERIODO_REPETIDO]);

    expect(Periodo::query()->count())->toBe(1);
});

it('allows the same number in another year', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreatePeriodo::class)
        ->fillForm(datosDePeriodo(['anio' => 2028, 'numero' => 3, 'fecha_inicio' => '2028-03-01', 'fecha_fin' => '2028-03-31', 'matricula_inicio' => '2028-02-15', 'matricula_fin' => '2028-03-05']))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Periodo::query()->where('numero', 3)->count())->toBe(2);
});

it('reports a period created by someone else at the same time as a form error', function (): void {
    $this->actingAs($this->gestor);
    Periodo::creating(function (Periodo $nuevo): void {
        DB::table('aca_periodos')->insert([
            'anio' => $nuevo->anio, 'numero' => $nuevo->numero, 'nombre' => 'Simultáneo',
            'fecha_inicio' => '2027-04-01', 'fecha_fin' => '2027-04-30', 'matricula_inicio' => '2027-03-20', 'matricula_fin' => '2027-04-05',
        ]);
    });

    Livewire::test(CreatePeriodo::class)
        ->fillForm(datosDePeriodo())
        ->call('create')
        ->assertHasFormErrors(['numero' => PeriodoForm::PERIODO_REPETIDO]);
});

it('edits a period keeping its number and state, without deleting it', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(EditPeriodo::class, ['record' => $this->periodo->getRouteKey()])
        ->assertActionDoesNotExist('delete')
        ->assertSchemaComponentDoesNotExist('estado')
        ->fillForm(['nombre' => 'Marzo regular 2027', 'fecha_fin' => '2027-04-02'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->periodo->refresh())
        ->nombre->toBe('Marzo regular 2027')
        ->numero->toBe(3)
        ->fecha_fin->toDateString()->toBe('2027-04-02')
        ->estado->toBe(EstadoPeriodo::Planificado);
});

it('offers only the action that moves a period to its next state', function (EstadoPeriodo $estado, ?string $siguiente): void {
    $this->periodo->update(['estado' => $estado]);
    $this->actingAs($this->gestor);
    $pagina = Livewire::test(EditPeriodo::class, ['record' => $this->periodo->getRouteKey()]);

    foreach (['abrirMatricula', 'iniciarClases', 'cerrar'] as $accion) {
        $accion === $siguiente ? $pagina->assertActionVisible($accion) : $pagina->assertActionHidden($accion);
    }
})->with([
    'planned' => [EstadoPeriodo::Planificado, 'abrirMatricula'],
    'enrollment open' => [EstadoPeriodo::MatriculaAbierta, 'iniciarClases'],
    'in progress' => [EstadoPeriodo::EnCurso, 'cerrar'],
    'closed' => [EstadoPeriodo::Cerrado, null],
]);

it('moves a period forward from the list and from its page', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ListPeriodos::class)
        ->callAction(TestAction::make('abrirMatricula')->table($this->periodo))
        ->assertNotified('Matrícula abierta');
    expect($this->periodo->refresh()->estado)->toBe(EstadoPeriodo::MatriculaAbierta);

    Livewire::test(EditPeriodo::class, ['record' => $this->periodo->getRouteKey()])
        ->callAction('iniciarClases')
        ->assertNotified('Periodo en curso')
        ->assertActionHidden('iniciarClases')
        ->assertActionVisible('cerrar');
    expect($this->periodo->refresh()->estado)->toBe(EstadoPeriodo::EnCurso);
});

it('does not run a state change that another user already made obsolete', function (): void {
    $this->actingAs($this->gestor);
    $pagina = Livewire::test(EditPeriodo::class, ['record' => $this->periodo->getRouteKey()]);
    Periodo::query()->whereKey($this->periodo->id)->update(['estado' => EstadoPeriodo::EnCurso->value]);

    $pagina->callAction('abrirMatricula')
        ->assertNotNotified('Matrícula abierta');

    expect($this->periodo->refresh()->estado)->toBe(EstadoPeriodo::EnCurso);
});

it('hides the state changes from readers', function (): void {
    $this->actingAs($this->lector);

    Livewire::test(ListPeriodos::class)
        ->assertActionHidden(TestAction::make('abrirMatricula')->table($this->periodo));
});

it('reports a year and number taken by someone else while editing as a form error', function (): void {
    $this->actingAs($this->gestor);
    Periodo::updating(function (): void {
        DB::table('aca_periodos')->insert([
            'anio' => 2027, 'numero' => 9, 'nombre' => 'Simultáneo',
            'fecha_inicio' => '2027-09-01', 'fecha_fin' => '2027-09-30', 'matricula_inicio' => '2027-08-20', 'matricula_fin' => '2027-09-05',
        ]);
    });

    Livewire::test(EditPeriodo::class, ['record' => $this->periodo->getRouteKey()])
        ->fillForm(['numero' => 9])
        ->call('save')
        ->assertHasFormErrors(['numero' => PeriodoForm::PERIODO_REPETIDO]);
});

it('warns before starting classes ahead of the period start date', function (): void {
    $this->periodo->update(['estado' => EstadoPeriodo::MatriculaAbierta]);
    $this->actingAs($this->gestor);

    Livewire::test(EditPeriodo::class, ['record' => $this->periodo->getRouteKey()])
        ->mountAction('iniciarClases')
        ->assertMountedActionModalSee('Las clases de este periodo empiezan el 01/03/2027: se iniciarán antes de esa fecha.');
});

it('explains why a period with open groups cannot be closed', function (): void {
    $this->periodo->update(['estado' => EstadoPeriodo::EnCurso]);
    Grupo::factory()->for($this->periodo)->create(['codigo' => 'ING-01-A', 'estado' => EstadoGrupo::Abierto]);
    $this->actingAs($this->gestor);

    Livewire::test(EditPeriodo::class, ['record' => $this->periodo->getRouteKey()])
        ->callAction('cerrar')
        ->assertNotified('No se puede cerrar el periodo Marzo 2027 mientras tenga grupos abiertos: ING-01-A. Ciérralos o cancélalos antes.');

    expect($this->periodo->refresh()->estado)->toBe(EstadoPeriodo::EnCurso);
});
