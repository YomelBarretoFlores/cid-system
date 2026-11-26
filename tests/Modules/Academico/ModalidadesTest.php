<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\GuardarGrupo;
use App\Modules\Academico\Actions\GuardarModalidad;
use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Data\DatosDeGrupo;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\CreateGrupo;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\EditGrupo;
use App\Modules\Academico\Filament\Resources\Modalidades\ModalidadResource;
use App\Modules\Academico\Filament\Resources\Modalidades\Pages\CreateModalidad;
use App\Modules\Academico\Filament\Resources\Modalidades\Pages\EditModalidad;
use App\Modules\Academico\Filament\Resources\Modalidades\Pages\ListModalidades;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Modalidad;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Academico\Models\Programa;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoAcademico::valores()]);
    $this->periodo = Periodo::factory()->create();
    $this->ciclo = Ciclo::factory()->create();
    $this->aula = Aula::factory()->create(['capacidad' => 30]);
});

function grupoDeModalidad(string $modalidad, ?int $aulaId, ?string $enlace = null): DatosDeGrupo
{
    return new DatosDeGrupo(test()->periodo->id, test()->ciclo->id, 'ING-M1', $modalidad, false, 25, 10, (int) Programa::query()->where('por_defecto', true)->value('id'), $aulaId, $enlace);
}

function modalidadHibrida(bool $activa = true): Modalidad
{
    return app(GuardarModalidad::class)(null, ['nombre' => 'Híbrida', 'descripcion' => 'Algunas semanas en aula y otras en línea.', 'orden' => 4, 'activo' => $activa, 'usa_aula' => true, 'usa_enlace' => true]);
}

it('ships the three modalities groups had before, in order, with their public descriptions', function (): void {
    expect(Modalidad::query()->activos()->get(['codigo', 'nombre', 'descripcion', 'usa_aula', 'usa_enlace'])->toArray())->toBe([
        ['codigo' => 'presencial', 'nombre' => 'Presencial', 'descripcion' => 'En las aulas del CILN, con su grupo y su docente.', 'usa_aula' => true, 'usa_enlace' => false],
        ['codigo' => 'virtual', 'nombre' => 'Virtual', 'descripcion' => 'Clases en línea con el enlace del grupo.', 'usa_aula' => false, 'usa_enlace' => true],
        ['codigo' => 'semipresencial', 'nombre' => 'Semipresencial', 'descripcion' => 'Sesiones en aula y sesiones en línea.', 'usa_aula' => true, 'usa_enlace' => true],
    ]);
});

it('saves a group in a new modality and shows its name to other modules and on the public page', function (): void {
    $hibrida = modalidadHibrida();
    $this->travelTo(CarbonImmutable::parse('2027-03-01 10:00', 'America/Lima'));
    $periodo = Periodo::factory()->create(['estado' => EstadoPeriodo::MatriculaAbierta, 'matricula_inicio' => '2027-02-20', 'matricula_fin' => '2027-03-10', 'fecha_inicio' => '2027-03-15', 'fecha_fin' => '2027-07-15']);

    $grupo = app(GuardarGrupo::class)(new DatosDeGrupo($periodo->id, $this->ciclo->id, 'ING-H1', $hibrida->codigo, false, 25, 10, (int) Programa::query()->where('por_defecto', true)->value('id'), $this->aula->id));
    DB::table('aca_grupos')->where('id', $grupo->id)->update(['estado' => EstadoGrupo::Abierto->value]);

    expect($grupo->refresh()->modalidad)->toBe('hibrida')
        ->and(app(DetalleDeGrupos::class)([$grupo->id])[$grupo->id]->modalidad)->toBe('Híbrida');

    $this->get('/')->assertOk()->assertSeeText('Híbrida')->assertSeeText('Algunas semanas en aula y otras en línea.');
});

it('asks a modality without classroom for the class link and refuses a classroom', function (): void {
    app(GuardarModalidad::class)(null, ['nombre' => 'A distancia', 'descripcion' => null, 'orden' => 5, 'activo' => true, 'usa_aula' => false, 'usa_enlace' => true]);

    expect(fn (): Grupo => app(GuardarGrupo::class)(grupoDeModalidad('a_distancia', null)))->toThrow(ReglaDeNegocioIncumplida::class, 'Un grupo a distancia necesita el enlace de la clase.')
        ->and(fn (): Grupo => app(GuardarGrupo::class)(grupoDeModalidad('a_distancia', $this->aula->id, 'https://meet.test/x')))->toThrow(ReglaDeNegocioIncumplida::class, 'Un grupo a distancia no usa aula.')
        ->and(app(GuardarGrupo::class)(grupoDeModalidad('a_distancia', null, 'https://meet.test/x'))->aula_id)->toBeNull();
});

it('refuses an inactive or unknown modality for a new group but keeps it on the groups that have it', function (): void {
    $hibrida = modalidadHibrida();
    $grupo = app(GuardarGrupo::class)(grupoDeModalidad('hibrida', $this->aula->id));
    $hibrida->update(['activo' => false]);

    expect(fn (): Grupo => app(GuardarGrupo::class)(new DatosDeGrupo($this->periodo->id, $this->ciclo->id, 'ING-M2', 'hibrida', false, 25, 10, $grupo->programa_id, $this->aula->id)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La modalidad elegida no está disponible para nuevos grupos.')
        ->and(fn (): Grupo => app(GuardarGrupo::class)(grupoDeModalidad('itinerante', $this->aula->id)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La modalidad elegida no está disponible para nuevos grupos.')
        ->and(app(GuardarGrupo::class)(new DatosDeGrupo($this->periodo->id, $this->ciclo->id, 'ING-M1', 'hibrida', false, 20, 10, $grupo->programa_id, $this->aula->id), $grupo->id)->cupo_maximo)->toBe(20);
});

it('makes the database refuse a group with a code outside the catalog and a modality with nowhere to meet', function (): void {
    expect(fn () => DB::transaction(fn () => Grupo::factory()->create(['modalidad' => 'itinerante'])))->toThrow(QueryException::class, 'aca_grupos_modalidad_foreign')
        ->and(fn () => app(GuardarModalidad::class)(null, ['nombre' => 'Ninguna', 'descripcion' => null, 'orden' => 6, 'activo' => true, 'usa_aula' => false, 'usa_enlace' => false]))
        ->toThrow(ReglaDeNegocioIncumplida::class, GuardarModalidad::SIN_LUGAR);
});

it('offers only active modalities in the group form but keeps the inactive one already stored', function (): void {
    $this->actingAs($this->gestor);
    $hibrida = modalidadHibrida();
    $grupo = app(GuardarGrupo::class)(grupoDeModalidad('hibrida', $this->aula->id));
    $hibrida->update(['activo' => false]);

    Livewire::test(EditGrupo::class, ['record' => $grupo->getRouteKey()])
        ->assertFormFieldExists('modalidad', fn ($campo): bool => array_key_exists('hibrida', $campo->getOptions()))
        ->call('save')
        ->assertHasNoFormErrors();

    Livewire::test(CreateGrupo::class)
        ->assertFormFieldExists('modalidad', fn ($campo): bool => ! array_key_exists('hibrida', $campo->getOptions()) && array_key_exists('virtual', $campo->getOptions()));

    expect($grupo->refresh()->modalidad)->toBe('hibrida');
});

it('lets academic managers create and rename a modality whose code and meeting place never change', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreateModalidad::class)
        ->fillForm(['nombre' => 'Híbrida', 'descripcion' => 'Semanas alternas.', 'orden' => 4, 'usa_aula' => true, 'usa_enlace' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $modalidad = Modalidad::query()->where('codigo', 'hibrida')->sole();

    Livewire::test(EditModalidad::class, ['record' => $modalidad->getRouteKey()])
        ->fillForm(['nombre' => 'Mixta', 'activo' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($modalidad->refresh())->nombre->toBe('Mixta')->codigo->toBe('hibrida')->activo->toBeFalse()->usa_aula->toBeTrue()->usa_enlace->toBeTrue();

    Livewire::test(CreateModalidad::class)
        ->fillForm(['nombre' => 'Virtual', 'orden' => 9])
        ->call('create')
        ->assertHasFormErrors(['nombre']);

    Livewire::test(ListModalidades::class)->assertCanSeeTableRecords(Modalidad::query()->get())->assertTableActionDoesNotExist('delete');
});

it('lets staff with the academic read permission see the modalities but only managers change them', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value]);
    $ajeno = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($ajeno)->get(ModalidadResource::getUrl('index'))->assertForbidden();
    $this->actingAs($lector)->get(ModalidadResource::getUrl('index'))->assertOk()->assertSeeText('Semipresencial');
    $this->actingAs($lector)->get(ModalidadResource::getUrl('create'))->assertForbidden();
});

it('rolls the catalog back to the fixed modalities and forward again, keeping every stored code', function (): void {
    $migracion = require base_path('app/Modules/Academico/Database/Migrations/2026_12_01_110000_turn_modalidad_into_a_catalog.php');
    $grupo = Grupo::factory()->create(['modalidad' => 'virtual', 'aula_id' => null, 'enlace_virtual' => 'https://meet.test/v']);

    $migracion->down();

    expect(fn () => DB::transaction(fn () => DB::table('aca_grupos')->where('id', $grupo->id)->update(['modalidad' => 'itinerante'])))->toThrow(QueryException::class, 'aca_grupos_modalidad_check');

    $migracion->up();

    expect($grupo->refresh()->modalidadDeDictado->nombre)->toBe('Virtual');
});
