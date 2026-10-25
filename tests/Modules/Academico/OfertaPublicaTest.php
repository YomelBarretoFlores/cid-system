<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\OfertaPublica;
use App\Modules\Academico\Data\GrupoOfertado;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00', 'America/Lima'));
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->inglesI = Ciclo::factory()->for($this->ingles)->create(['numero' => 1]);
});

function periodoPublico(string $nombre, EstadoPeriodo $estado, string $matriculaInicio, string $matriculaFin): Periodo
{
    $inicio = CarbonImmutable::parse($matriculaFin)->addDays(2);

    return Periodo::factory()->create([
        'nombre' => $nombre, 'estado' => $estado, 'matricula_inicio' => $matriculaInicio, 'matricula_fin' => $matriculaFin,
        'fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => $inicio->addDays(27)->toDateString(),
    ]);
}

it('shows the period whose enrollment is open, with its dates and its open and scheduled groups', function (): void {
    $periodo = periodoPublico('2026-II', EstadoPeriodo::MatriculaAbierta, '2026-10-15', '2026-10-31');
    periodoPublico('2026-III', EstadoPeriodo::Planificado, '2026-11-15', '2026-11-30');
    $quechua = Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Quechua']))->create(['numero' => 2]);
    $abierto = Grupo::factory()->for($periodo)->for($this->inglesI)->create([
        'estado' => EstadoGrupo::Abierto, 'modalidad' => Modalidad::Virtual, 'cupo_maximo' => 20, 'matriculados' => 18,
        'enlace_virtual' => 'https://meet.example.test/privado',
    ]);
    Horario::factory()->for($abierto)->create(['dia_semana' => 1, 'hora_inicio' => '07:00', 'hora_fin' => '08:30']);
    Horario::factory()->for($abierto)->create(['dia_semana' => 3, 'hora_inicio' => '07:00', 'hora_fin' => '08:30']);
    Grupo::factory()->for($periodo)->for($quechua)->create(['estado' => EstadoGrupo::Programado]);
    Grupo::factory()->for($periodo)->for($this->inglesI)->create(['estado' => EstadoGrupo::Cerrado]);
    Grupo::factory()->for($periodo)->for($this->inglesI)->create(['estado' => EstadoGrupo::Cancelado]);

    $oferta = app(OfertaPublica::class)();

    expect($oferta)
        ->periodo->toBe('2026-II')
        ->matriculaAbierta->toBeTrue()
        ->and($oferta?->matriculaInicio->toDateString())->toBe('2026-10-15')
        ->and($oferta?->matriculaFin->toDateString())->toBe('2026-10-31')
        ->and($oferta?->clasesInicio->toDateString())->toBe('2026-11-02')
        ->and($oferta?->clasesFin->toDateString())->toBe('2026-11-29')
        ->and($oferta?->grupos)->toHaveCount(2)
        ->and($oferta?->grupos[0])->toEqual(new GrupoOfertado(
            idioma: 'Inglés', cicloNumero: 1, nivel: $this->inglesI->nivel->etiqueta(), modalidad: 'Virtual',
            esAcelerado: false, horario: ['Lun · Mié, 07:00–08:30'], vacantes: 2, abierto: true,
        ))
        ->and($oferta?->grupos[1])->idioma->toBe('Quechua')->abierto->toBeFalse();
});

it('shows the next planned period while no enrollment is open', function (): void {
    periodoPublico('2026-I', EstadoPeriodo::Cerrado, '2026-03-01', '2026-03-10');
    periodoPublico('2027-I', EstadoPeriodo::Planificado, '2027-01-10', '2027-01-20');
    $siguiente = periodoPublico('2026-III', EstadoPeriodo::Planificado, '2026-11-15', '2026-11-30');
    Grupo::factory()->for($siguiente)->for($this->inglesI)->create(['estado' => EstadoGrupo::Programado]);

    $oferta = app(OfertaPublica::class)();

    expect($oferta)->periodo->toBe('2026-III')->matriculaAbierta->toBeFalse()
        ->and($oferta?->grupos)->toHaveCount(1);
});

it('reports a full group with no vacancies and hides inactive languages', function (): void {
    $periodo = periodoPublico('2026-II', EstadoPeriodo::MatriculaAbierta, '2026-10-15', '2026-10-31');
    Grupo::factory()->for($periodo)->for($this->inglesI)->create(['estado' => EstadoGrupo::Abierto, 'cupo_maximo' => 20, 'matriculados' => 20]);
    $inactivo = Ciclo::factory()->for(Idioma::factory()->create(['activo' => false]))->create(['numero' => 1]);
    Grupo::factory()->for($periodo)->for($inactivo)->create(['estado' => EstadoGrupo::Abierto]);

    expect(app(OfertaPublica::class)()?->grupos)->toHaveCount(1)
        ->sequence(fn ($grupo) => $grupo->vacantes->toBe(0));
});

it('has nothing to show when no period is open or planned ahead', function (): void {
    periodoPublico('2026-I', EstadoPeriodo::Cerrado, '2026-03-01', '2026-03-10');
    periodoPublico('2026-II', EstadoPeriodo::MatriculaAbierta, '2026-10-01', '2026-10-10');

    expect(app(OfertaPublica::class)())->toBeNull();
});
