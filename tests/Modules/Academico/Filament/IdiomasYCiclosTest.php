<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\Idiomas\IdiomaResource;
use App\Modules\Academico\Filament\Resources\Idiomas\Pages\CreateIdioma;
use App\Modules\Academico\Filament\Resources\Idiomas\Pages\EditIdioma;
use App\Modules\Academico\Filament\Resources\Idiomas\Pages\ListIdiomas;
use App\Modules\Academico\Filament\Resources\Idiomas\RelationManagers\CiclosRelationManager;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Shared\Auth\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->coordinador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value, PermisoAcademico::Gestionar->value]);
    $this->ingles = Idioma::factory()->create(['codigo' => 'ING', 'nombre' => 'Inglés']);
    $this->basicoUno = Ciclo::factory()->for($this->ingles)->create([
        'numero' => 1, 'nivel' => NivelCurricular::Basico, 'nombre' => 'Básico I', 'horas_lectivas' => 60,
    ]);
});

function ciclosDe(Idioma $idioma): Testable
{
    return Livewire::test(CiclosRelationManager::class, ['ownerRecord' => $idioma, 'pageClass' => EditIdioma::class]);
}

it('keeps the language screens away from users without academic permissions', function (): void {
    $sinPermiso = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($sinPermiso)->get(IdiomaResource::getUrl('index'))->assertForbidden();
    $this->actingAs($sinPermiso)->get(IdiomaResource::getUrl('edit', ['record' => $this->ingles]))->assertForbidden();
});

it('lets a reader list languages but not create or edit them', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value]);

    $this->actingAs($lector)->get(IdiomaResource::getUrl('index'))->assertOk();
    $this->actingAs($lector)->get(IdiomaResource::getUrl('create'))->assertForbidden();
    $this->actingAs($lector)->get(IdiomaResource::getUrl('edit', ['record' => $this->ingles]))->assertForbidden();

    Livewire::actingAs($lector)->test(ListIdiomas::class)
        ->assertCanSeeTableRecords([$this->ingles])
        ->assertActionHidden('create');
});

it('creates a language with a normalized code', function (): void {
    $this->actingAs($this->coordinador);

    Livewire::test(CreateIdioma::class)
        ->fillForm(['codigo' => ' que ', 'nombre' => 'Quechua', 'es_lengua_nativa' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Idioma::query()->where('codigo', 'QUE')->sole())
        ->nombre->toBe('Quechua')
        ->es_lengua_nativa->toBeTrue()
        ->activo->toBeTrue();
});

it('refuses a repeated language code or name', function (): void {
    $this->actingAs($this->coordinador);

    Livewire::test(CreateIdioma::class)
        ->fillForm(['codigo' => ' ing ', 'nombre' => 'Inglés'])
        ->call('create')
        ->assertHasFormErrors(['codigo', 'nombre']);

    expect(Idioma::query()->count())->toBe(1);
});

it('reports a language created by someone else at the same time as a form error', function (string $campo): void {
    $this->actingAs($this->coordinador);
    Idioma::creating(function (Idioma $nuevo): void {
        DB::table('aca_idiomas')->insert([
            'codigo' => $nuevo->codigo === 'FRA' ? 'FRA' : 'OTR',
            'nombre' => $nuevo->nombre === 'Francés' ? 'Francés' : 'Otro',
        ]);
    });

    Livewire::test(CreateIdioma::class)
        ->fillForm($campo === 'codigo' ? ['codigo' => 'fra', 'nombre' => 'Portugués'] : ['codigo' => 'POR', 'nombre' => 'Francés'])
        ->call('create')
        ->assertHasFormErrors([$campo]);

    expect(Idioma::query()->count())->toBe(1);
})->with(['codigo', 'nombre']);

it('edits a language keeping its code and deactivates it instead of deleting it', function (): void {
    $this->actingAs($this->coordinador);

    Livewire::test(EditIdioma::class, ['record' => $this->ingles->getRouteKey()])
        ->assertActionDoesNotExist('delete')
        ->assertFormFieldDisabled('codigo')
        ->fillForm(['nombre' => 'Inglés británico', 'activo' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->ingles->refresh())
        ->codigo->toBe('ING')
        ->nombre->toBe('Inglés británico')
        ->activo->toBeFalse();
});

it('gives a new language the curriculum code 01 and lets the coordinator change it', function (): void {
    $this->actingAs($this->coordinador);

    Livewire::test(CreateIdioma::class)
        ->fillForm(['codigo' => 'ALE', 'nombre' => 'Alemán'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Idioma::query()->where('codigo', 'ALE')->sole()->curriculo)->toBe('01');

    Livewire::test(EditIdioma::class, ['record' => $this->ingles->getRouteKey()])
        ->assertSchemaStateSet(['curriculo' => '01'])
        ->fillForm(['curriculo' => ' 02 '])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->ingles->refresh()->curriculo)->toBe('02');

    Livewire::test(ListIdiomas::class)->assertTableColumnStateSet('curriculo', '02', $this->ingles);
});

it('requires a curriculum code of at most ten characters', function (?string $curriculo): void {
    $this->actingAs($this->coordinador);

    Livewire::test(EditIdioma::class, ['record' => $this->ingles->getRouteKey()])
        ->fillForm(['curriculo' => $curriculo])
        ->call('save')
        ->assertHasFormErrors(['curriculo']);

    expect($this->ingles->refresh()->curriculo)->toBe('01');
})->with(['empty' => null, 'too long' => '12345678901']);

it('lists the curriculum code in the language catalog other modules read', function (): void {
    $this->ingles->update(['curriculo' => '03']);

    $catalogo = collect(app(CatalogoDeIdiomas::class)())->keyBy('nombre');

    expect($catalogo['Inglés']->curriculo)->toBe('03');
});

it('refuses to rename a language to another one on edit, also when it happens at the same time', function (): void {
    $frances = Idioma::factory()->create(['codigo' => 'FRA', 'nombre' => 'Francés']);
    $this->actingAs($this->coordinador);

    Livewire::test(EditIdioma::class, ['record' => $this->ingles->getRouteKey()])
        ->fillForm(['nombre' => 'Francés'])
        ->call('save')
        ->assertHasFormErrors(['nombre']);

    Idioma::updating(function (): void {
        DB::table('aca_idiomas')->insert(['codigo' => 'ITA', 'nombre' => 'Italiano']);
    });

    Livewire::test(EditIdioma::class, ['record' => $this->ingles->getRouteKey()])
        ->fillForm(['nombre' => 'Italiano'])
        ->call('save')
        ->assertHasFormErrors(['nombre']);

    expect($this->ingles->refresh()->nombre)->toBe('Inglés')
        ->and($frances->refresh()->codigo)->toBe('FRA');
});

it('lists the study plan of a language with its levels', function (): void {
    $this->actingAs($this->coordinador);

    ciclosDe($this->ingles)
        ->assertCanSeeTableRecords([$this->basicoUno])
        ->assertTableColumnFormattedStateSet('nivel', 'Básico', $this->basicoUno)
        ->assertActionDoesNotExist(TestAction::make('delete')->table($this->basicoUno));
});

it('adds a cycle to the study plan', function (): void {
    $this->actingAs($this->coordinador);

    ciclosDe($this->ingles)
        ->callAction(TestAction::make('create')->table(), data: [
            'numero' => 12, 'nivel' => NivelCurricular::Avanzado->value, 'nombre' => 'Avanzado XII', 'horas_lectivas' => 64,
        ])
        ->assertHasNoFormErrors();

    expect(Ciclo::query()->where('idioma_id', $this->ingles->id)->where('numero', 12)->sole())
        ->nivel->toBe(NivelCurricular::Avanzado)
        ->nombre->toBe('Avanzado XII')
        ->horas_lectivas->toBe(64)
        ->activo->toBeTrue();
});

it('validates the cycle number and teaching hours', function (array $datos, array $errores): void {
    $this->actingAs($this->coordinador);

    ciclosDe($this->ingles)
        ->callAction(TestAction::make('create')->table(), data: [
            'numero' => 2, 'nivel' => NivelCurricular::Basico->value, 'nombre' => 'Básico II', 'horas_lectivas' => 60, ...$datos,
        ])
        ->assertHasFormErrors($errores);

    expect(Ciclo::query()->count())->toBe(1);
})->with([
    'number below range' => [['numero' => 0], ['numero']],
    'number above range' => [['numero' => 13], ['numero']],
    'number repeated in the language' => [['numero' => 1], ['numero']],
    'no teaching hours' => [['horas_lectivas' => 0], ['horas_lectivas']],
]);

it('allows the same cycle number in another language', function (): void {
    $frances = Idioma::factory()->create(['codigo' => 'FRA', 'nombre' => 'Francés']);
    $this->actingAs($this->coordinador);

    ciclosDe($frances)
        ->callAction(TestAction::make('create')->table(), data: [
            'numero' => 1, 'nivel' => NivelCurricular::Basico->value, 'nombre' => 'Básico I', 'horas_lectivas' => 60,
        ])
        ->assertHasNoFormErrors();

    expect(Ciclo::query()->where('idioma_id', $frances->id)->where('numero', 1)->exists())->toBeTrue();
});

it('reports a cycle number taken by someone else at the same time as a form error', function (): void {
    $this->actingAs($this->coordinador);
    Ciclo::creating(function (Ciclo $nuevo): void {
        DB::table('aca_ciclos')->insert([
            'idioma_id' => $nuevo->idioma_id, 'numero' => $nuevo->numero, 'nivel' => 'basico', 'nombre' => 'Simultáneo', 'horas_lectivas' => 60,
        ]);
    });

    ciclosDe($this->ingles)
        ->callAction(TestAction::make('create')->table(), data: [
            'numero' => 2, 'nivel' => NivelCurricular::Basico->value, 'nombre' => 'Básico II', 'horas_lectivas' => 60,
        ])
        ->assertHasFormErrors(['numero']);

    expect(Ciclo::query()->count())->toBe(1);
});

it('edits and deactivates a cycle, refusing a number already used in the language', function (): void {
    $basicoDos = Ciclo::factory()->for($this->ingles)->create(['numero' => 2, 'nombre' => 'Básico II']);
    $this->actingAs($this->coordinador);

    ciclosDe($this->ingles)
        ->callAction(TestAction::make('edit')->table($basicoDos), data: ['numero' => 1])
        ->assertHasFormErrors(['numero']);

    ciclosDe($this->ingles)
        ->callAction(TestAction::make('edit')->table($basicoDos), data: ['nombre' => 'Básico II (2027)', 'horas_lectivas' => 72, 'activo' => false])
        ->assertHasNoFormErrors();

    expect($basicoDos->refresh())
        ->numero->toBe(2)
        ->nombre->toBe('Básico II (2027)')
        ->horas_lectivas->toBe(72)
        ->activo->toBeFalse();
});

it('lets a reader see the study plan but not change it', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value]);
    $this->actingAs($lector);

    ciclosDe($this->ingles)
        ->assertCanSeeTableRecords([$this->basicoUno])
        ->assertActionHidden(TestAction::make('create')->table())
        ->assertActionHidden(TestAction::make('edit')->table($this->basicoUno));
});

it('locks the number, level and teaching hours of a cycle with groups and still renames it', function (): void {
    Grupo::factory()->create(['ciclo_id' => $this->basicoUno->id]);
    $this->actingAs($this->coordinador);

    ciclosDe($this->ingles)
        ->mountAction(TestAction::make('edit')->table($this->basicoUno))
        ->assertFormFieldDisabled('numero')
        ->assertFormFieldDisabled('nivel')
        ->assertFormFieldDisabled('horas_lectivas')
        ->assertFormFieldEnabled('nombre');

    ciclosDe($this->ingles)
        ->callAction(TestAction::make('edit')->table($this->basicoUno), data: ['nombre' => 'Básico I (2027)', 'numero' => 5, 'horas_lectivas' => 90])
        ->assertHasNoFormErrors();

    expect($this->basicoUno->refresh())
        ->nombre->toBe('Básico I (2027)')
        ->numero->toBe(1)
        ->horas_lectivas->toBe(60);
});
