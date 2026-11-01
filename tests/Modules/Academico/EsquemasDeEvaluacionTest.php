<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\GuardarEsquemaDeEvaluacion;
use App\Modules\Academico\Data\DatosDeEsquema;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\EsquemaDeEvaluacionResource;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Pages\CreateEsquemaDeEvaluacion;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Pages\EditEsquemaDeEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Idioma;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-02-10 10:00', 'America/Lima'));
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
});

/**
 * @param  array<string, mixed>  $cambios
 */
function datosDeEsquema(array $cambios = []): DatosDeEsquema
{
    return new DatosDeEsquema(...[
        'nombre' => 'Esquema general 2027',
        'idiomaId' => null,
        'nivel' => null,
        'notaMaxima' => '20',
        'notaMinimaAprobatoria' => '11',
        'vigenteDesde' => CarbonImmutable::parse('2027-03-01'),
        'vigenteHasta' => null,
        'componentes' => [
            ['codigo' => ' Parcial ', 'nombre' => 'Examen parcial', 'peso' => '40'],
            ['codigo' => 'final', 'nombre' => 'Examen final', 'peso' => '40'],
            ['codigo' => 'participacion', 'nombre' => 'Participación', 'peso' => '20'],
        ],
        ...$cambios,
    ]);
}

function guardarEsquema(DatosDeEsquema $datos, ?int $id = null): EsquemaEvaluacion
{
    return app(GuardarEsquemaDeEvaluacion::class)($datos, $id);
}

it('creates a scheme with its components in order and normalized codes', function (): void {
    $esquema = guardarEsquema(datosDeEsquema());

    expect([$esquema->nombre, $esquema->nota_maxima, $esquema->nota_minima_aprobatoria, $esquema->vigente_desde->toDateString()])->toBe(['Esquema general 2027', '20.00', '11.00', '2027-03-01'])
        ->and($esquema->componentes()->get()->map(fn ($c): array => [$c->codigo, $c->nombre, $c->peso, $c->orden])->all())->toBe([
            ['parcial', 'Examen parcial', '40.00', 1],
            ['final', 'Examen final', '40.00', 2],
            ['participacion', 'Participación', '20.00', 3],
        ]);
});

it('refuses a scheme that breaks a rule and saves nothing', function (array $cambios, string $mensaje): void {
    expect(fn (): EsquemaEvaluacion => guardarEsquema(datosDeEsquema($cambios)))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(EsquemaEvaluacion::query()->count())->toBe(0);
})->with([
    'weights that do not sum 100' => [['componentes' => [['codigo' => 'parcial', 'nombre' => 'Parcial', 'peso' => '50'], ['codigo' => 'final', 'nombre' => 'Final', 'peso' => '40']]], 'Los pesos de los componentes deben sumar 100; ahora suman 90.'],
    'no components' => [['componentes' => []], 'Agrega al menos un componente de evaluación.'],
    'a repeated code' => [['componentes' => [['codigo' => 'parcial', 'nombre' => 'Parcial', 'peso' => '50'], ['codigo' => 'PARCIAL', 'nombre' => 'Otro', 'peso' => '50']]], 'El código «parcial» se repite.'],
    'a passing grade above the maximum' => [['notaMinimaAprobatoria' => '21'], 'La nota mínima aprobatoria debe estar entre 0 y la nota máxima.'],
    'an end before the start' => [['vigenteHasta' => CarbonImmutable::parse('2027-02-01')], 'La vigencia debe terminar en o después de su inicio.'],
]);

it('refuses a scheme whose validity overlaps another for the same language and level', function (): void {
    guardarEsquema(datosDeEsquema(['nombre' => 'Esquema de inglés', 'idiomaId' => $this->ingles->id, 'nivel' => NivelCurricular::Basico]));

    expect(fn (): EsquemaEvaluacion => guardarEsquema(datosDeEsquema(['nombre' => 'Otro de inglés', 'idiomaId' => $this->ingles->id, 'nivel' => NivelCurricular::Basico, 'vigenteDesde' => CarbonImmutable::parse('2027-06-01')])))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Se cruza con la vigencia del sistema «Esquema de inglés»; cierra su vigencia antes de crear otro.');

    expect(guardarEsquema(datosDeEsquema(['nombre' => 'Esquema general']))->exists)->toBeTrue();
});

it('lets a scheme in force change only its name and end date', function (): void {
    $esquema = guardarEsquema(datosDeEsquema(['vigenteDesde' => CarbonImmutable::parse('2027-01-01')]));

    expect(fn (): EsquemaEvaluacion => guardarEsquema(datosDeEsquema(['vigenteDesde' => CarbonImmutable::parse('2027-01-01'), 'componentes' => [['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'peso' => '50'], ['codigo' => 'final', 'nombre' => 'Examen final', 'peso' => '50']]]), $esquema->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El sistema «Esquema general 2027» ya está vigente; para cambiar componentes, pesos o notas, cierra su vigencia y crea uno nuevo.');

    guardarEsquema(datosDeEsquema(['nombre' => 'Esquema general 2027 (cerrado)', 'vigenteDesde' => CarbonImmutable::parse('2027-01-01'), 'vigenteHasta' => CarbonImmutable::parse('2027-06-30')]), $esquema->id);
    $siguiente = guardarEsquema(datosDeEsquema(['nombre' => 'Esquema general julio', 'vigenteDesde' => CarbonImmutable::parse('2027-07-01'), 'componentes' => [['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'peso' => '50'], ['codigo' => 'final', 'nombre' => 'Examen final', 'peso' => '50']]]));

    expect($esquema->refresh())->nombre->toBe('Esquema general 2027 (cerrado)')
        ->and($esquema->vigente_hasta?->toDateString())->toBe('2027-06-30')
        ->and($esquema->componentes()->count())->toBe(3)
        ->and($siguiente->componentes()->sum('peso'))->toEqual(100);
});

it('makes the database refuse weights that do not sum 100 and keeps an audit trail', function (): void {
    $esquema = guardarEsquema(datosDeEsquema());
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    expect(fn () => DB::transaction(fn (): int => DB::table('aca_componentes_evaluacion')->where('esquema_id', $esquema->id)->where('codigo', 'final')->update(['peso' => 30])))
        ->toThrow(QueryException::class, 'los pesos del sistema de evaluación')
        ->and(DB::table('aud_registros')->where('tabla', 'aca_esquemas_evaluacion')->where('registro_id', $esquema->id)->where('operacion', 'INSERT')->exists())->toBeTrue()
        ->and(DB::table('aud_registros')->where('tabla', 'aca_componentes_evaluacion')->where('operacion', 'INSERT')->count())->toBe(3);
});

it('keeps the scheme screens away from users without the academic permissions', function (): void {
    $sinPermiso = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION]);
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value]);

    $this->actingAs($sinPermiso)->get(EsquemaDeEvaluacionResource::getUrl('index'))->assertForbidden();
    $this->actingAs($lector)->get(EsquemaDeEvaluacionResource::getUrl('index'))->assertOk();
    $this->actingAs($lector)->get(EsquemaDeEvaluacionResource::getUrl('create'))->assertForbidden();
});

it('creates and edits a scheme from the panel', function (): void {
    $gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoAcademico::valores()]);
    $this->actingAs($gestor);

    Livewire::test(CreateEsquemaDeEvaluacion::class)
        ->fillForm([
            'nombre' => 'Esquema de inglés básico', 'idioma_id' => $this->ingles->id, 'nivel' => NivelCurricular::Basico->value,
            'nota_maxima' => '20', 'nota_minima_aprobatoria' => '11', 'vigente_desde' => '2027-03-01',
            'componentes' => [
                ['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'peso' => '50'],
                ['codigo' => 'final', 'nombre' => 'Examen final', 'peso' => '40'],
            ],
        ])
        ->call('create')
        ->assertNotified('Los pesos de los componentes deben sumar 100; ahora suman 90.');

    expect(EsquemaEvaluacion::query()->count())->toBe(0);

    Livewire::test(CreateEsquemaDeEvaluacion::class)
        ->fillForm([
            'nombre' => 'Esquema de inglés básico', 'idioma_id' => $this->ingles->id, 'nivel' => NivelCurricular::Basico->value,
            'nota_maxima' => '20', 'nota_minima_aprobatoria' => '11', 'vigente_desde' => '2027-03-01',
            'componentes' => [
                ['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'peso' => '60'],
                ['codigo' => 'final', 'nombre' => 'Examen final', 'peso' => '40'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $esquema = EsquemaEvaluacion::query()->sole();

    Livewire::test(EditEsquemaDeEvaluacion::class, ['record' => $esquema->getRouteKey()])
        ->assertSchemaStateSet(['nombre' => 'Esquema de inglés básico'])
        ->fillForm(['nombre' => 'Inglés básico 2027'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($esquema->refresh()->nombre)->toBe('Inglés básico 2027')
        ->and($esquema->componentes()->pluck('peso', 'codigo')->all())->toBe(['parcial' => '60.00', 'final' => '40.00']);
});
