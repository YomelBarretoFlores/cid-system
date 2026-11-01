<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Docencia\Actions\ConsolidarHorasDictadas;
use App\Modules\Docencia\Actions\RegistrarCostoPorHora;
use App\Modules\Docencia\Data\LineaDeHoras;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Resources\GradosAcademicos\GradoAcademicoResource;
use App\Modules\Docencia\Filament\Resources\GradosAcademicos\Pages\CreateGradoAcademico;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\CondicionLaboral;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\GradoAcademico;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-02-10 10:00', 'America/Lima'));
    $this->usuario = User::factory()->create();
    $this->grupo = Grupo::factory()->create(['ciclo_id' => Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Inglés']))->create(['nivel' => NivelCurricular::Basico])->id]);
});

function migracionDeCatalogosDocentes(): Migration
{
    return require base_path('app/Modules/Docencia/Database/Migrations/2026_11_01_120100_turn_teacher_categories_into_catalogs_and_rate_by_degree.php');
}

function claseDelDocente(Docente $docente, string $fecha): ClaseDictada
{
    return ClaseDictada::query()->create(['grupo_id' => test()->grupo->id, 'fecha' => $fecha, 'hora_inicio' => '18:00', 'hora_fin' => '20:00', 'docente_id' => $docente->id, 'registrado_por' => test()->usuario->id]);
}

it('starts with the employment conditions of the TdR and the academic degrees', function (): void {
    expect(CondicionLaboral::query()->activos()->pluck('nombre', 'codigo')->all())->toBe(['nombrado' => 'Nombrado', 'contratado' => 'Contratado', 'tercero' => 'Tercero'])
        ->and(GradoAcademico::query()->activos()->pluck('nombre', 'codigo')->all())->toBe(['certificado' => 'Certificado', 'bachiller' => 'Bachiller', 'titulado' => 'Titulado', 'magister' => 'Magíster', 'doctor' => 'Doctor']);
});

it('lets teaching managers add a degree from the panel and keeps it from other users', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Ver->value]);
    $gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Ver->value, PermisoDocencia::Gestionar->value]);

    $this->actingAs($lector)->get(GradoAcademicoResource::getUrl('index'))->assertForbidden();

    Livewire::actingAs($gestor)->test(CreateGradoAcademico::class)
        ->fillForm(['nombre' => 'Segunda especialidad', 'orden' => 6])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(GradoAcademico::query()->where('codigo', 'segunda_especialidad')->exists())->toBeTrue();
});

it('registers the hourly rate by academic degree and refuses an overlap within that degree', function (): void {
    $magister = GradoAcademico::idDe('magister');

    $costo = app(RegistrarCostoPorHora::class)($magister, '60.00', CarbonImmutable::parse('2027-03-01'), null, $this->usuario);

    expect([$costo->grado_academico_id, $costo->condicion_laboral_id, $costo->costo_hora])->toBe([$magister, null, '60.00'])
        ->and(fn () => app(RegistrarCostoPorHora::class)($magister, '65.00', CarbonImmutable::parse('2027-01-01'), CarbonImmutable::parse('2027-03-15'), $this->usuario))
        ->toThrow(ReglaDeNegocioIncumplida::class, RegistrarCostoPorHora::SUPERPOSICION)
        ->and(app(RegistrarCostoPorHora::class)(GradoAcademico::idDe('bachiller'), '45.00', CarbonImmutable::parse('2027-03-01'), null, $this->usuario)->exists)->toBeTrue();
});

it('pays a class by the degree rate in force on its date, and before it by the earlier rate of the employment condition', function (): void {
    $docente = Docente::factory()->create(['condicion_laboral_id' => CondicionLaboral::idDe('contratado'), 'grado_academico_id' => GradoAcademico::idDe('magister')]);
    CostoPorHora::query()->create(['condicion_laboral_id' => CondicionLaboral::idDe('contratado'), 'costo_hora' => '40.00', 'vigente_desde' => '2027-01-01', 'registrado_por' => $this->usuario->id]);
    app(RegistrarCostoPorHora::class)(GradoAcademico::idDe('magister'), '60.00', CarbonImmutable::parse('2027-03-15'), null, $this->usuario);
    claseDelDocente($docente, '2027-03-10');
    claseDelDocente($docente, '2027-03-20');

    $expediente = app(ConsolidarHorasDictadas::class)(2027, 3, $docente->id)[0];

    expect($expediente->categoria)->toBe('Magíster')
        ->and(array_map(fn (LineaDeHoras $linea): ?int => $linea->costoCentimos, $expediente->lineas))->toBe([4000, 6000])
        ->and($expediente->montoTotal())->toBe('200.00');
});

it('keeps paying a teacher without a degree by the rate of the employment condition', function (): void {
    $docente = Docente::factory()->create(['condicion_laboral_id' => CondicionLaboral::idDe('nombrado'), 'grado_academico_id' => null]);
    CostoPorHora::query()->create(['condicion_laboral_id' => CondicionLaboral::idDe('nombrado'), 'costo_hora' => '50.00', 'vigente_desde' => '2027-01-01', 'registrado_por' => $this->usuario->id]);
    claseDelDocente($docente, '2027-03-10');

    $expediente = app(ConsolidarHorasDictadas::class)(2027, 3, $docente->id)[0];

    expect([$expediente->categoria, $expediente->lineas[0]->costoCentimos])->toBe(['Nombrado', 5000]);
});

it('keeps the condition of every teacher and the rates already registered when the catalogs are created', function (): void {
    $docente = Docente::factory()->create(['condicion_laboral_id' => CondicionLaboral::idDe('tercero')]);
    $costo = CostoPorHora::query()->create(['condicion_laboral_id' => CondicionLaboral::idDe('tercero'), 'costo_hora' => '35.00', 'vigente_desde' => '2027-01-01', 'registrado_por' => $this->usuario->id]);
    migracionDeCatalogosDocentes()->down();

    expect([DB::table('doc_docentes')->where('id', $docente->id)->value('condicion_laboral'), DB::table('doc_costos_hora')->where('id', $costo->id)->value('condicion_laboral')])->toBe(['tercero', 'tercero']);

    migracionDeCatalogosDocentes()->up();

    expect([$docente->refresh()->condicionLaboral->codigo, $costo->refresh()->condicionLaboral?->codigo, $costo->grado_academico_id])->toBe(['tercero', 'tercero', null]);
});
