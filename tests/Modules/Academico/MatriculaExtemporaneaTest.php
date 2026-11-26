<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\MatriculaExtemporanea;
use App\Modules\Academico\Contracts\OfertaDeMatricula;
use App\Modules\Academico\Contracts\OfertaPublica;
use App\Modules\Academico\Contracts\VentanasDeMatricula;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-03 10:00', 'America/Lima'));
    $this->ciclo = Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Inglés']))->create(['numero' => 1]);
    $this->periodo = Periodo::factory()->create([
        'nombre' => 'Marzo 2027', 'estado' => EstadoPeriodo::MatriculaAbierta,
        'fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-03-31',
        'matricula_inicio' => '2027-02-15', 'matricula_fin' => '2027-03-05', 'matricula_extemporanea_fin' => '2027-03-12',
    ]);
    $this->grupo = Grupo::factory()->for($this->periodo)->for($this->ciclo)->create(['codigo' => 'ING-I-A']);
});

/**
 * @return list<string>
 */
function codigosEnOferta(): array
{
    return array_map(fn (FichaDeGrupo $grupo): string => $grupo->codigo, app(OfertaDeMatricula::class)([]));
}

it('keeps the groups open during the late enrollment window and closes them after it', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-12 23:00', 'America/Lima'));
    expect(codigosEnOferta())->toBe(['ING-I-A']);

    $this->travelTo(CarbonImmutable::parse('2027-03-13 08:00', 'America/Lima'));
    expect(codigosEnOferta())->toBe([]);
});

it('tells whether a date falls in the late enrollment window of an open period', function (string $fecha, bool $esperado): void {
    expect(app(MatriculaExtemporanea::class)(CarbonImmutable::parse($fecha)))->toBe($esperado);
})->with([
    'regular window' => ['2027-03-05', false],
    'first late day' => ['2027-03-06', true],
    'last late day' => ['2027-03-12', true],
    'after the late window' => ['2027-03-13', false],
]);

it('also counts the late window of a period already in progress, but not of a planned or closed one', function (EstadoPeriodo $estado, bool $esperado): void {
    $this->periodo->update(['estado' => $estado]);

    expect(app(MatriculaExtemporanea::class)(CarbonImmutable::parse('2027-03-08')))->toBe($esperado);
})->with([
    'in progress' => [EstadoPeriodo::EnCurso, true],
    'planned' => [EstadoPeriodo::Planificado, false],
    'closed' => [EstadoPeriodo::Cerrado, false],
]);

it('gives the regular window of another open period priority over a late window on the same day', function (): void {
    Periodo::factory()->create([
        'estado' => EstadoPeriodo::MatriculaAbierta, 'fecha_inicio' => '2027-03-15', 'fecha_fin' => '2027-04-15',
        'matricula_inicio' => '2027-03-08', 'matricula_fin' => '2027-03-14',
    ]);

    expect(app(MatriculaExtemporanea::class)(CarbonImmutable::parse('2027-03-10')))->toBeFalse();
});

it('lists the enrollment window with its late end while the late window lasts', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-10 10:00', 'America/Lima'));

    $ventana = app(VentanasDeMatricula::class)()[0];

    expect($ventana->abierta)->toBeTrue()
        ->and($ventana->fin->toDateString())->toBe('2027-03-05')
        ->and($ventana->extemporaneaFin?->toDateString())->toBe('2027-03-12')
        ->and($ventana->cierre()->toDateString())->toBe('2027-03-12');
});

it('shows the public offer as open until the end of the late window', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-10 10:00', 'America/Lima'));

    $oferta = app(OfertaPublica::class)();

    expect($oferta?->matriculaAbierta)->toBeTrue()
        ->and($oferta?->matriculaFin->toDateString())->toBe('2027-03-12');
});
