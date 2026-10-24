<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\Aulas\AulaResource;
use App\Modules\Academico\Filament\Resources\Aulas\Pages\CreateAula;
use App\Modules\Academico\Filament\Resources\Aulas\Pages\EditAula;
use App\Modules\Academico\Filament\Resources\Aulas\Pages\ListAulas;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->coordinador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value, PermisoAcademico::Gestionar->value]);
    $this->aula = Aula::factory()->create(['codigo' => 'A-101', 'nombre' => 'Laboratorio de idiomas', 'capacidad' => 30]);
});

it('keeps the classroom screens away from users without academic permissions', function (): void {
    $sinPermiso = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($sinPermiso)->get(AulaResource::getUrl('index'))->assertForbidden();
    $this->actingAs($sinPermiso)->get(AulaResource::getUrl('edit', ['record' => $this->aula]))->assertForbidden();
});

it('lets a reader list classrooms but not create or edit them', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value]);

    $this->actingAs($lector)->get(AulaResource::getUrl('index'))->assertOk();
    $this->actingAs($lector)->get(AulaResource::getUrl('create'))->assertForbidden();
    $this->actingAs($lector)->get(AulaResource::getUrl('edit', ['record' => $this->aula]))->assertForbidden();

    Livewire::actingAs($lector)->test(ListAulas::class)
        ->assertCanSeeTableRecords([$this->aula])
        ->assertActionHidden('create');
});

it('creates a classroom with a normalized code', function (): void {
    $this->actingAs($this->coordinador);

    Livewire::test(CreateAula::class)
        ->fillForm(['codigo' => ' b-202 ', 'nombre' => 'Aula 202', 'capacidad' => 25, 'ubicacion' => 'Pabellón B, segundo piso'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Aula::query()->where('codigo', 'B-202')->sole())
        ->nombre->toBe('Aula 202')
        ->capacidad->toBe(25)
        ->ubicacion->toBe('Pabellón B, segundo piso')
        ->activo->toBeTrue();
});

it('refuses a repeated code or a capacity that is not positive', function (array $datos, array $errores): void {
    $this->actingAs($this->coordinador);

    Livewire::test(CreateAula::class)
        ->fillForm(['codigo' => 'C-303', 'nombre' => 'Aula 303', 'capacidad' => 20, ...$datos])
        ->call('create')
        ->assertHasFormErrors($errores);

    expect(Aula::query()->count())->toBe(1);
})->with([
    'repeated code' => [['codigo' => ' a-101 '], ['codigo']],
    'zero capacity' => [['capacidad' => 0], ['capacidad']],
]);

it('reports a classroom code created by someone else at the same time as a form error', function (): void {
    $this->actingAs($this->coordinador);
    Aula::creating(function (Aula $nueva): void {
        DB::table('aca_aulas')->insert(['codigo' => $nueva->codigo, 'nombre' => 'Simultánea', 'capacidad' => 20]);
    });

    Livewire::test(CreateAula::class)
        ->fillForm(['codigo' => 'd-404', 'nombre' => 'Aula 404', 'capacidad' => 20])
        ->call('create')
        ->assertHasFormErrors(['codigo']);

    expect(Aula::query()->count())->toBe(1);
});

it('deactivates a classroom instead of deleting it, keeping its code', function (): void {
    $this->actingAs($this->coordinador);

    Livewire::test(EditAula::class, ['record' => $this->aula->getRouteKey()])
        ->assertActionDoesNotExist('delete')
        ->assertSchemaComponentStateSet('codigo', 'A-101')
        ->assertFormFieldDisabled('codigo')
        ->fillForm(['nombre' => 'Laboratorio 1', 'capacidad' => 28, 'activo' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->aula->refresh())
        ->codigo->toBe('A-101')
        ->nombre->toBe('Laboratorio 1')
        ->capacidad->toBe(28)
        ->activo->toBeFalse();
});

it('refuses a capacity below the quota of a group still using the classroom', function (): void {
    $grupo = Grupo::factory()->create(['aula_id' => $this->aula->id, 'codigo' => 'ING-01', 'cupo_maximo' => 25, 'cupo_minimo' => 10]);
    Grupo::factory()->create(['aula_id' => $this->aula->id, 'cupo_maximo' => 30, 'cupo_minimo' => 10, 'estado' => EstadoGrupo::Finalizado]);
    $this->actingAs($this->coordinador);

    Livewire::test(EditAula::class, ['record' => $this->aula->getRouteKey()])
        ->fillForm(['capacidad' => 20])
        ->call('save')
        ->assertNotified("El grupo {$grupo->codigo} usa esta aula con un cupo de 25; la capacidad no puede ser menor.");
    expect($this->aula->refresh()->capacidad)->toBe(30);

    Livewire::test(EditAula::class, ['record' => $this->aula->getRouteKey()])
        ->fillForm(['capacidad' => 25])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($this->aula->refresh()->capacidad)->toBe(25);
});
