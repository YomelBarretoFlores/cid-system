<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Docencia\Actions\ConsolidarHorasDictadas;
use App\Modules\Docencia\Data\ExpedienteDeHoras;
use App\Modules\Docencia\Data\LineaDeHoras;
use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->usuario = User::factory()->create();
    $this->ingles = Grupo::factory()->create(['ciclo_id' => Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Inglés']))->create(['nivel' => NivelCurricular::Basico])->id]);
    $this->quechua = Grupo::factory()->create(['ciclo_id' => Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Quechua']))->create(['nivel' => NivelCurricular::Intermedio])->id]);
    $this->docente = Docente::factory()->create([
        'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Rímac', 'apellido_materno' => 'Huaylas', 'nombres' => 'Rosa'])->id,
        'condicion_laboral' => CondicionLaboral::Contratado,
    ]);
    costoDe('contratado', '40.00', '2027-01-01');
});

function costoDe(string $categoria, string $costo, string $desde, ?string $hasta = null): CostoPorHora
{
    return CostoPorHora::query()->create(['condicion_laboral' => $categoria, 'costo_hora' => $costo, 'vigente_desde' => $desde, 'vigente_hasta' => $hasta, 'registrado_por' => test()->usuario->id]);
}

function claseDictada(Grupo $grupo, string $fecha, string $inicio, string $fin, ?Docente $docente = null): ClaseDictada
{
    return ClaseDictada::query()->create(['grupo_id' => $grupo->id, 'fecha' => $fecha, 'hora_inicio' => $inicio, 'hora_fin' => $fin, 'docente_id' => ($docente ?? test()->docente)->id, 'registrado_por' => test()->usuario->id]);
}

function expedienteDe(int $anio, int $mes, ?Docente $docente = null): ExpedienteDeHoras
{
    $expedientes = app(ConsolidarHorasDictadas::class)($anio, $mes, ($docente ?? test()->docente)->id);

    return $expedientes[0];
}

it('adds up the hours the teacher took in the month by language and level, with the cost and the gross amount', function (): void {
    claseDictada($this->ingles, '2027-03-01', '18:00', '20:00');
    claseDictada($this->ingles, '2027-03-03', '18:00', '19:30');
    claseDictada($this->quechua, '2027-03-31', '08:00', '10:00');
    claseDictada($this->ingles, '2027-02-28', '18:00', '20:00');
    claseDictada($this->ingles, '2027-04-01', '18:00', '20:00');
    claseDictada($this->ingles, '2027-03-05', '18:00', '20:00', Docente::factory()->create());

    $expediente = expedienteDe(2027, 3);

    expect([$expediente->docenteId, $expediente->codigo, $expediente->nombre, $expediente->categoria])->toBe([$this->docente->id, $this->docente->codigo, 'Rímac Huaylas, Rosa', 'Contratado'])
        ->and($expediente->periodo())->toBe('Marzo 2027')
        ->and($expediente->lineas)->toEqual([
            new LineaDeHoras('Inglés', 'Básico', 350, 4000, 14000),
            new LineaDeHoras('Quechua', 'Intermedio', 200, 4000, 8000),
        ])
        ->and([$expediente->horasTotales(), $expediente->montoTotal(), $expediente->tieneHorasSinCosto()])->toBe(['5.50', '220.00', false]);
});

it('splits the hours when the cost changes within the month', function (): void {
    CostoPorHora::query()->update(['vigente_hasta' => '2027-03-15']);
    costoDe('contratado', '45.00', '2027-03-16');
    claseDictada($this->ingles, '2027-03-15', '18:00', '20:00');
    claseDictada($this->ingles, '2027-03-16', '18:00', '20:00');

    expect(expedienteDe(2027, 3)->lineas)->toEqual([
        new LineaDeHoras('Inglés', 'Básico', 200, 4000, 8000),
        new LineaDeHoras('Inglés', 'Básico', 200, 4500, 9000),
    ]);
});

it('lists hours without a valid cost apart and leaves them out of the total', function (): void {
    CostoPorHora::query()->update(['vigente_hasta' => '2027-03-10']);
    claseDictada($this->ingles, '2027-03-10', '18:00', '20:00');
    claseDictada($this->ingles, '2027-03-11', '18:00', '20:00');

    $expediente = expedienteDe(2027, 3);

    expect($expediente->lineas)->toEqual([
        new LineaDeHoras('Inglés', 'Básico', 200, 4000, 8000),
        new LineaDeHoras('Inglés', 'Básico', 200, null, null),
    ])
        ->and([$expediente->horasTotales(), $expediente->montoTotal(), $expediente->tieneHorasSinCosto()])->toBe(['4.00', '80.00', true]);
});

it('uses the cost of the current category of the teacher', function (): void {
    costoDe('nombrado', '60.00', '2027-01-01');
    $this->docente->update(['condicion_laboral' => CondicionLaboral::Nombrado]);
    claseDictada($this->ingles, '2027-03-01', '18:00', '19:00');

    expect(expedienteDe(2027, 3))->categoria->toBe('Nombrado')
        ->and(expedienteDe(2027, 3)->lineas[0]->costoCentimos)->toBe(6000);
});

it('rounds the hours of each line to hundredths and the amount to cents from those hours', function (): void {
    CostoPorHora::query()->update(['costo_hora' => '33.33']);
    claseDictada($this->ingles, '2027-03-01', '18:00', '18:50');

    $linea = expedienteDe(2027, 3)->lineas[0];

    expect([$linea->horas(), $linea->costoPorHora(), $linea->monto()])->toBe(['0.83', '33.33', '27.66']);
});

it('lists every teacher with hours in the month in alphabetical order', function (): void {
    $otro = Docente::factory()->create(['persona_id' => Persona::factory()->create(['apellido_paterno' => 'Alva', 'nombres' => 'Luis'])->id]);
    claseDictada($this->ingles, '2027-03-01', '18:00', '20:00');
    claseDictada($this->quechua, '2027-03-02', '08:00', '10:00', $otro);
    Docente::factory()->create();

    $expedientes = app(ConsolidarHorasDictadas::class)(2027, 3);

    expect(array_map(fn (ExpedienteDeHoras $expediente): int => $expediente->docenteId, $expedientes))->toBe([$otro->id, $this->docente->id]);
});

it('gives a teacher without classes in the month an empty report', function (): void {
    $expediente = expedienteDe(2027, 3);

    expect($expediente->lineas)->toBe([])
        ->and([$expediente->horasTotales(), $expediente->montoTotal()])->toBe(['0.00', '0.00']);
});

it('refuses a month that does not exist', function (int $anio, int $mes): void {
    expect(fn () => app(ConsolidarHorasDictadas::class)($anio, $mes))->toThrow(ReglaDeNegocioIncumplida::class, 'Elige un mes y un año válidos.');
})->with([[2027, 0], [2027, 13], [1999, 5], [2101, 5]]);

it('indexes the class date the report filters on', function (): void {
    $indices = collect(DB::select("select indexdef from pg_indexes where tablename = 'doc_clases_dictadas'"))->pluck('indexdef');

    expect($indices->contains(fn (string $definicion): bool => preg_match('/\\(fecha[,)]/', $definicion) === 1))->toBeTrue();
});

it('counts the classes the delegate confirmed and the observed or unconfirmed ones without changing the hours', function (): void {
    [$usuario, $docente] = PortalDocente::docente();
    [$grupo, $estudiantes] = PortalDocente::grupoConEstudiantes($docente, 'Quispe', 'Álvarez');
    PortalDocente::clasesEnCadaEstado($grupo, $docente, $usuario, $estudiantes);

    $this->travelTo(CarbonImmutable::parse('2027-03-20 12:00', 'America/Lima'));
    $expediente = expedienteDe(2027, 3, $docente);

    expect([$expediente->clasesConfirmadas(), $expediente->clasesNoConfirmadas(), $expediente->horasTotales()])->toBe([1, 2, '12.00'])
        ->and(array_map(fn (LineaDeHoras $linea): array => [$linea->clasesConfirmadas, $linea->clasesNoConfirmadas], $expediente->lineas))->toBe([[1, 2]]);

    $this->travelTo(CarbonImmutable::parse('2027-03-08 21:00', 'America/Lima'));

    expect([expedienteDe(2027, 3, $docente)->clasesConfirmadas(), expedienteDe(2027, 3, $docente)->clasesNoConfirmadas()])->toBe([1, 1]);
});
