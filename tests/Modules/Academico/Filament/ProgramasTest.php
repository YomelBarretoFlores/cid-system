<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Enums\RegimenDePrograma;
use App\Modules\Academico\Filament\Resources\Programas\Pages\CreatePrograma;
use App\Modules\Academico\Filament\Resources\Programas\Pages\EditPrograma;
use App\Modules\Academico\Filament\Resources\Programas\Pages\ListProgramas;
use App\Modules\Academico\Filament\Resources\Programas\ProgramaResource;
use App\Modules\Academico\Models\Programa;
use App\Shared\Auth\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->coordinador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value, PermisoAcademico::Gestionar->value]);
    $this->posgrado = Programa::query()->where('codigo', 'POSGRADO')->sole();
});

it('keeps the program screens away from users without academic permissions', function (): void {
    $sinPermiso = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($sinPermiso)->get(ProgramaResource::getUrl('index'))->assertForbidden();
    $this->actingAs($sinPermiso)->get(ProgramaResource::getUrl('edit', ['record' => $this->posgrado]))->assertForbidden();
});

it('lets a reader list programs but not create or edit them', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value]);

    $this->actingAs($lector)->get(ProgramaResource::getUrl('index'))->assertOk();
    $this->actingAs($lector)->get(ProgramaResource::getUrl('create'))->assertForbidden();
    $this->actingAs($lector)->get(ProgramaResource::getUrl('edit', ['record' => $this->posgrado]))->assertForbidden();

    Livewire::actingAs($lector)->test(ListProgramas::class)
        ->assertCanSeeTableRecords(Programa::query()->get())
        ->assertTableColumnFormattedStateSet('regimen', 'Solo acelerado', $this->posgrado)
        ->assertActionHidden('create');
});

it('creates a program with a normalized code', function (): void {
    $this->actingAs($this->coordinador);

    Livewire::test(CreatePrograma::class)
        ->fillForm(['codigo' => ' ext ', 'nombre' => 'Extensión', 'regimen' => RegimenDePrograma::Regular->value])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Programa::query()->where('codigo', 'EXT')->sole())
        ->nombre->toBe('Extensión')
        ->regimen->toBe(RegimenDePrograma::Regular)
        ->activo->toBeTrue();
});

it('refuses a repeated program code or name', function (array $datos, array $errores): void {
    $this->actingAs($this->coordinador);

    Livewire::test(CreatePrograma::class)
        ->fillForm(['codigo' => 'EXT', 'nombre' => 'Extensión', 'regimen' => RegimenDePrograma::Ambos->value, ...$datos])
        ->call('create')
        ->assertHasFormErrors($errores);

    expect(Programa::query()->count())->toBe(3);
})->with([
    'repeated code' => [['codigo' => ' ja '], ['codigo']],
    'repeated name' => [['nombre' => 'Niños'], ['nombre']],
]);

it('edits a program keeping its code and deactivates it instead of deleting it', function (): void {
    $this->actingAs($this->coordinador);

    Livewire::test(EditPrograma::class, ['record' => $this->posgrado->getRouteKey()])
        ->assertSchemaStateSet(['codigo' => 'POSGRADO', 'regimen' => RegimenDePrograma::Acelerado->value])
        ->fillForm(['nombre' => 'Posgrado UNASAM', 'regimen' => RegimenDePrograma::Ambos->value, 'activo' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->posgrado->refresh())
        ->codigo->toBe('POSGRADO')
        ->nombre->toBe('Posgrado UNASAM')
        ->regimen->toBe(RegimenDePrograma::Ambos)
        ->activo->toBeFalse();
});
