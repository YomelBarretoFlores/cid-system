<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\GuardarPrograma;
use App\Modules\Academico\Contracts\ProgramaPorDefecto;
use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\CreateGrupo;
use App\Modules\Academico\Filament\Resources\Programas\Pages\CreatePrograma;
use App\Modules\Academico\Filament\Resources\Programas\Pages\EditPrograma;
use App\Modules\Academico\Models\Programa;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoAcademico::valores()]);
});

function programaPorDefecto(): ?string
{
    $codigo = Programa::query()->where('por_defecto', true)->value('codigo');

    return is_string($codigo) ? $codigo : null;
}

function datosDePrograma(Programa $programa, array $cambios): array
{
    return [...$programa->only(['nombre', 'activo', 'por_defecto']), 'regimen' => $programa->regimen->value, ...$cambios];
}

it('starts with Jóvenes y Adultos as the default program, as the documents printed before', function (): void {
    expect(programaPorDefecto())->toBe('JA')
        ->and(app(ProgramaPorDefecto::class)())->toBe('Jóvenes y Adultos');
});

it('moves the default to the program marked and prints its name for groups without a program', function (): void {
    $ninos = Programa::query()->where('codigo', 'NINOS')->sole();

    app(GuardarPrograma::class)($ninos->id, datosDePrograma($ninos, ['por_defecto' => true]));

    expect(programaPorDefecto())->toBe('NINOS')
        ->and(Programa::query()->where('por_defecto', true)->count())->toBe(1)
        ->and(app(ProgramaPorDefecto::class)())->toBe('Niños');
});

it('refuses to leave the programs without a default or with an inactive one', function (): void {
    $ja = Programa::query()->where('codigo', 'JA')->sole();

    expect(fn () => app(GuardarPrograma::class)($ja->id, datosDePrograma($ja, ['por_defecto' => false])))
        ->toThrow(ReglaDeNegocioIncumplida::class, GuardarPrograma::SIN_POR_DEFECTO)
        ->and(fn () => app(GuardarPrograma::class)($ja->id, datosDePrograma($ja, ['activo' => false])))
        ->toThrow(ReglaDeNegocioIncumplida::class, GuardarPrograma::POR_DEFECTO_INACTIVO)
        ->and($ja->refresh())->activo->toBeTrue()->por_defecto->toBeTrue()
        ->and(fn () => DB::transaction(fn () => DB::table('aca_programas')->where('codigo', 'NINOS')->update(['por_defecto' => true])))
        ->toThrow(QueryException::class, 'aca_programas_por_defecto_unique');
});

it('lets staff mark a new program as the default from the panel and proposes it for new groups', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreatePrograma::class)
        ->fillForm(['codigo' => 'adultos', 'nombre' => 'Adultos mayores', 'regimen' => 'regular', 'activo' => true, 'por_defecto' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $nuevo = Programa::query()->where('codigo', 'ADULTOS')->sole();

    expect(programaPorDefecto())->toBe('ADULTOS');

    Livewire::test(CreateGrupo::class)->assertSchemaStateSet(['programa_id' => $nuevo->id]);

    Livewire::test(EditPrograma::class, ['record' => $nuevo->getRouteKey()])
        ->fillForm(['por_defecto' => false])
        ->call('save')
        ->assertNotified(GuardarPrograma::SIN_POR_DEFECTO);

    expect($nuevo->refresh()->por_defecto)->toBeTrue();
});

it('keeps editing a program that is not the default as before', function (): void {
    $this->actingAs($this->gestor);
    $posgrado = Programa::query()->where('codigo', 'POSGRADO')->sole();

    Livewire::test(EditPrograma::class, ['record' => $posgrado->getRouteKey()])
        ->fillForm(['nombre' => 'Posgrado UNASAM', 'activo' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($posgrado->refresh())->nombre->toBe('Posgrado UNASAM')->activo->toBeFalse()->por_defecto->toBeFalse()
        ->and(programaPorDefecto())->toBe('JA');
});

it('rolls the flag back and forward again, marking Jóvenes y Adultos', function (): void {
    $migracion = require base_path('app/Modules/Academico/Database/Migrations/2026_12_01_110100_add_por_defecto_to_aca_programas_table.php');

    $migracion->down();

    expect(DB::getSchemaBuilder()->hasColumn('aca_programas', 'por_defecto'))->toBeFalse();

    $migracion->up();

    expect(programaPorDefecto())->toBe('JA');
});
