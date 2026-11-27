<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\GuardarEsquemaDeEvaluacion;
use App\Modules\Academico\Data\DatosDeEsquema;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Enums\RegimenDeEsquema;
use App\Modules\Academico\Enums\TipoDeComponente;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\EsquemaDeEvaluacionResource;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Pages\CreateEsquemaDeEvaluacion;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Pages\EditEsquemaDeEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Programa;
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
            ['codigo' => ' Oral ', 'nombre' => 'Expresión oral', 'tipo' => 'ingresada'],
            ['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'tipo' => 'ingresada'],
            ['codigo' => 'final', 'nombre' => 'Examen final', 'tipo' => 'ingresada'],
            ['codigo' => 'examenes', 'nombre' => 'Promedio de exámenes', 'tipo' => 'calculada', 'calculo' => 'promedio', 'fuentes' => [['codigo' => 'parcial'], ['codigo' => 'final']]],
            ['codigo' => 'nota', 'nombre' => 'Nota final', 'tipo' => 'calculada', 'calculo' => 'ponderado', 'redondeo' => 'entero', 'ciclo' => '1',
                'fuentes' => [['codigo' => 'oral', 'peso' => '40'], ['codigo' => 'examenes', 'peso' => '60']]],
        ],
        ...$cambios,
    ]);
}

/**
 * @return list<array<string, mixed>>
 */
function columnasSimples(string ...$codigos): array
{
    return [
        ...array_map(fn (string $codigo): array => ['codigo' => $codigo, 'nombre' => ucfirst($codigo), 'tipo' => 'ingresada'], $codigos),
        ['codigo' => 'nota', 'nombre' => 'Nota final', 'tipo' => 'calculada', 'calculo' => 'promedio', 'ciclo' => '1', 'fuentes' => array_map(fn (string $codigo): array => ['codigo' => $codigo], $codigos)],
    ];
}

function guardarEsquema(DatosDeEsquema $datos, ?int $id = null): EsquemaEvaluacion
{
    return app(GuardarEsquemaDeEvaluacion::class)($datos, $id);
}

it('creates a scheme with its entered and calculated columns in order and normalized codes', function (): void {
    $esquema = guardarEsquema(datosDeEsquema());

    expect([$esquema->nombre, $esquema->nota_maxima, $esquema->nota_minima_aprobatoria, $esquema->vigente_desde->toDateString()])->toBe(['Esquema general 2027', '20.00', '11.00', '2027-03-01'])
        ->and($esquema->componentes()->get()->map(fn ($c): array => [$c->codigo, $c->tipo, $c->calculo?->value, $c->redondeo->value, $c->fuentes, $c->ciclo, $c->orden])->all())->toEqual([
            ['oral', TipoDeComponente::Ingresada, null, 'ninguno', [], null, 1],
            ['parcial', TipoDeComponente::Ingresada, null, 'ninguno', [], null, 2],
            ['final', TipoDeComponente::Ingresada, null, 'ninguno', [], null, 3],
            ['examenes', TipoDeComponente::Calculada, 'promedio', 'ninguno', [['codigo' => 'parcial', 'peso' => 1], ['codigo' => 'final', 'peso' => 1]], null, 4],
            ['nota', TipoDeComponente::Calculada, 'ponderado', 'entero', [['codigo' => 'oral', 'peso' => 40], ['codigo' => 'examenes', 'peso' => 60]], 1, 5],
        ]);
});

it('refuses a scheme that breaks a rule and saves nothing', function (array $cambios, string $mensaje): void {
    expect(fn (): EsquemaEvaluacion => guardarEsquema(datosDeEsquema($cambios)))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(EsquemaEvaluacion::query()->count())->toBe(0);
})->with([
    'no entered column' => [['componentes' => [['codigo' => 'nota', 'nombre' => 'Nota', 'tipo' => 'calculada', 'calculo' => 'promedio', 'ciclo' => '1', 'fuentes' => []]]], 'Elige las columnas que usa «nota».'],
    'no cycle grade' => [['componentes' => [['codigo' => 'oral', 'nombre' => 'Oral', 'tipo' => 'ingresada']]], 'Marca una sola columna como nota del ciclo'],
    'two cycle grades' => [['componentes' => [['codigo' => 'oral', 'nombre' => 'Oral', 'tipo' => 'ingresada', 'ciclo' => '1'], ['codigo' => 'final', 'nombre' => 'Final', 'tipo' => 'ingresada', 'ciclo' => '1']]], 'Marca una sola columna como nota del ciclo'],
    'a second cycle grade outside the accelerated regime' => [['componentes' => [...columnasSimples('oral'), ['codigo' => 'segundo', 'nombre' => 'Segundo', 'tipo' => 'ingresada', 'ciclo' => '2']]], 'La nota del segundo ciclo solo existe en un sistema para el régimen acelerado.'],
    'a calculation using a later column' => [['componentes' => [['codigo' => 'nota', 'nombre' => 'Nota', 'tipo' => 'calculada', 'calculo' => 'promedio', 'ciclo' => '1', 'fuentes' => [['codigo' => 'oral']]], ['codigo' => 'oral', 'nombre' => 'Oral', 'tipo' => 'ingresada']]], 'La columna «nota» solo puede usar columnas que estén antes de ella, cada una una vez.'],
    'a weight out of range' => [['componentes' => [['codigo' => 'oral', 'nombre' => 'Oral', 'tipo' => 'ingresada'], ['codigo' => 'nota', 'nombre' => 'Nota', 'tipo' => 'calculada', 'calculo' => 'ponderado', 'ciclo' => '1', 'fuentes' => [['codigo' => 'oral', 'peso' => '0']]]]], 'Cada peso de la columna «nota» debe ser un número entero de 1 a 100.'],
    'a calculated column without calculation' => [['componentes' => [['codigo' => 'oral', 'nombre' => 'Oral', 'tipo' => 'ingresada'], ['codigo' => 'nota', 'nombre' => 'Nota', 'tipo' => 'calculada', 'ciclo' => '1', 'fuentes' => [['codigo' => 'oral']]]]], 'Elige cómo se calcula la columna «nota».'],
    'an unknown type' => [['componentes' => [['codigo' => 'oral', 'nombre' => 'Expresión oral', 'tipo' => 'participacion']]], 'Elige si la columna «oral» la ingresa el docente o es calculada.'],
    'no columns' => [['componentes' => []], 'Agrega al menos una columna.'],
    'a repeated code' => [['componentes' => [['codigo' => 'parcial', 'nombre' => 'Parcial', 'tipo' => 'ingresada'], ['codigo' => 'PARCIAL', 'nombre' => 'Otro', 'tipo' => 'ingresada']]], 'El código «parcial» se repite.'],
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

    expect(fn (): EsquemaEvaluacion => guardarEsquema(datosDeEsquema(['vigenteDesde' => CarbonImmutable::parse('2027-01-01'), 'componentes' => columnasSimples('oral', 'final')]), $esquema->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El sistema «Esquema general 2027» ya está vigente; para cambiar componentes o notas, cierra su vigencia y crea uno nuevo.');

    guardarEsquema(datosDeEsquema(['nombre' => 'Esquema general 2027 (cerrado)', 'vigenteDesde' => CarbonImmutable::parse('2027-01-01'), 'vigenteHasta' => CarbonImmutable::parse('2027-06-30')]), $esquema->id);
    $siguiente = guardarEsquema(datosDeEsquema(['nombre' => 'Esquema general julio', 'vigenteDesde' => CarbonImmutable::parse('2027-07-01'), 'componentes' => columnasSimples('oral', 'final')]));

    expect($esquema->refresh())->nombre->toBe('Esquema general 2027 (cerrado)')
        ->and($esquema->vigente_hasta?->toDateString())->toBe('2027-06-30')
        ->and($esquema->componentes()->count())->toBe(5)
        ->and($siguiente->componentes()->pluck('codigo')->all())->toBe(['oral', 'final', 'nota']);
});

it('makes the database refuse a scheme left without its cycle grade or entered columns and keeps an audit trail', function (array $cambio): void {
    $esquema = guardarEsquema(datosDeEsquema());
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    expect(fn () => DB::transaction(fn (): int => DB::table('aca_componentes_evaluacion')->where('esquema_id', $esquema->id)->update($cambio)))
        ->toThrow(QueryException::class)
        ->and(DB::table('aca_componentes_evaluacion')->where('esquema_id', $esquema->id)->where('ciclo', 1)->exists())->toBeTrue()
        ->and(DB::table('aud_registros')->where('tabla', 'aca_esquemas_evaluacion')->where('registro_id', $esquema->id)->where('operacion', 'INSERT')->exists())->toBeTrue()
        ->and(DB::table('aud_registros')->where('tabla', 'aca_componentes_evaluacion')->where('operacion', 'INSERT')->count())->toBe(5);
})->with([
    'no cycle grade' => [['ciclo' => null]],
    'a calculated column without sources' => [['tipo' => 'calculada', 'calculo' => 'promedio', 'fuentes' => '[]']],
    'an unknown rounding' => [['redondeo' => 'decimo']],
]);

it('applies a scheme to one program and regime, and refuses a regime the program does not have', function (): void {
    $posgrado = Programa::query()->where('codigo', 'POSGRADO')->sole();
    $acelerado = guardarEsquema(datosDeEsquema([
        'nombre' => 'Acelerado de posgrado', 'programaId' => $posgrado->id, 'regimen' => RegimenDeEsquema::Acelerado,
        'componentes' => [
            ['codigo' => 'primera', 'nombre' => 'Primera quincena', 'tipo' => 'ingresada', 'ciclo' => '1'],
            ['codigo' => 'segunda', 'nombre' => 'Segunda quincena', 'tipo' => 'ingresada', 'ciclo' => '2'],
        ],
    ]));

    expect([$acelerado->programa_id, $acelerado->regimen, $acelerado->componentes()->pluck('ciclo', 'codigo')->all()])->toBe([$posgrado->id, RegimenDeEsquema::Acelerado, ['primera' => 1, 'segunda' => 2]])
        ->and(guardarEsquema(datosDeEsquema(['nombre' => 'Regular para todos']))->exists)->toBeTrue()
        ->and(fn (): EsquemaEvaluacion => guardarEsquema(datosDeEsquema(['nombre' => 'Regular de posgrado', 'programaId' => $posgrado->id, 'regimen' => RegimenDeEsquema::Regular])))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El programa Posgrado no tiene grupos de régimen Regular.');
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
                ['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'tipo' => 'ingresada'],
                ['codigo' => 'final', 'nombre' => 'Examen final', 'tipo' => 'ingresada'],
            ],
        ])
        ->call('create')
        ->assertNotified('Marca una sola columna como nota del ciclo (y, en el acelerado, una sola como nota del segundo ciclo).');

    expect(EsquemaEvaluacion::query()->count())->toBe(0);

    Livewire::test(CreateEsquemaDeEvaluacion::class)
        ->fillForm([
            'nombre' => 'Esquema de inglés básico', 'idioma_id' => $this->ingles->id, 'nivel' => NivelCurricular::Basico->value,
            'nota_maxima' => '20', 'nota_minima_aprobatoria' => '11', 'vigente_desde' => '2027-03-01',
            'componentes' => [
                ['codigo' => 'auditiva', 'nombre' => 'Comprensión auditiva', 'tipo' => 'ingresada'],
                ['codigo' => 'final', 'nombre' => 'Examen final', 'tipo' => 'ingresada'],
                ['codigo' => 'nota', 'nombre' => 'Nota final', 'tipo' => 'calculada', 'calculo' => 'ponderado', 'redondeo' => 'entero', 'ciclo' => '1',
                    'fuentes' => [['codigo' => 'auditiva', 'peso' => '30'], ['codigo' => 'final', 'peso' => '70']]],
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
        ->and($esquema->componentes()->get()->map(fn ($c): array => [$c->codigo, $c->tipo->value, $c->fuentes, $c->ciclo])->all())->toEqual([
            ['auditiva', 'ingresada', [], null], ['final', 'ingresada', [], null],
            ['nota', 'calculada', [['codigo' => 'auditiva', 'peso' => 30], ['codigo' => 'final', 'peso' => 70]], 1],
        ]);
});

it('refuses to close a scheme in force before today, which would leave graded days without a scheme', function (): void {
    $esquema = guardarEsquema(datosDeEsquema(['vigenteDesde' => CarbonImmutable::parse('2027-01-01')]));

    expect(fn (): EsquemaEvaluacion => guardarEsquema(datosDeEsquema(['vigenteDesde' => CarbonImmutable::parse('2027-01-01'), 'vigenteHasta' => CarbonImmutable::parse('2027-02-09')]), $esquema->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Un sistema vigente no puede terminar antes de hoy (10/02/2027).')
        ->and($esquema->refresh()->vigente_hasta)->toBeNull();

    guardarEsquema(datosDeEsquema(['vigenteDesde' => CarbonImmutable::parse('2027-01-01'), 'vigenteHasta' => CarbonImmutable::parse('2027-02-10')]), $esquema->id);

    expect($esquema->refresh()->vigente_hasta?->toDateString())->toBe('2027-02-10');
});

it('still renames a scheme whose validity already ended', function (): void {
    $esquema = guardarEsquema(datosDeEsquema(['vigenteDesde' => CarbonImmutable::parse('2026-01-01'), 'vigenteHasta' => CarbonImmutable::parse('2026-12-31')]));

    guardarEsquema(datosDeEsquema(['nombre' => 'Esquema 2026', 'vigenteDesde' => CarbonImmutable::parse('2026-01-01'), 'vigenteHasta' => CarbonImmutable::parse('2026-12-31')]), $esquema->id);

    expect($esquema->refresh()->nombre)->toBe('Esquema 2026');
});
