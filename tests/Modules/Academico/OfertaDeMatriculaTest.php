<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Contracts\OfertaDeMatricula;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Academico\Data\SesionDeClase;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->inglesI = Ciclo::factory()->for($this->ingles)->create(['numero' => 1]);
    $this->inglesII = Ciclo::factory()->for($this->ingles)->create(['numero' => 2]);
    $this->periodo = Periodo::factory()->conMatriculaAbierta()->create(['nombre' => '2026-II']);
});

/**
 * @param  array<int, int>  $habilitados
 * @return list<string>
 */
function codigosOfertados(array $habilitados = []): array
{
    return array_map(fn (FichaDeGrupo $grupo): string => $grupo->codigo, app(OfertaDeMatricula::class)($habilitados));
}

it('offers the open groups of the first cycle to a student without enablement', function (): void {
    Grupo::factory()->for($this->periodo)->for($this->inglesI)->create(['codigo' => 'ING-I-A']);
    Grupo::factory()->for($this->periodo)->for($this->inglesII)->create(['codigo' => 'ING-II-A']);

    expect(codigosOfertados())->toBe(['ING-I-A']);
});

it('offers only the enabled cycle of a language and the first cycle of the others', function (): void {
    $quechua = Idioma::factory()->create(['nombre' => 'Quechua']);
    $quechuaI = Ciclo::factory()->for($quechua)->create(['numero' => 1]);
    Grupo::factory()->for($this->periodo)->for($this->inglesI)->create(['codigo' => 'ING-I-A']);
    Grupo::factory()->for($this->periodo)->for($this->inglesII)->create(['codigo' => 'ING-II-A']);
    Grupo::factory()->for($this->periodo)->for($quechuaI)->create(['codigo' => 'QUE-I-A']);

    expect(codigosOfertados([$this->ingles->id => $this->inglesII->id]))->toBe(['ING-II-A', 'QUE-I-A']);
});

it('hides groups that are not open for enrollment and languages that are inactive', function (Closure $preparar): void {
    $grupo = Grupo::factory()->for($this->periodo)->for($this->inglesI)->create();

    $preparar($grupo);

    expect(codigosOfertados())->toBe([]);
})->with([
    'group scheduled' => [fn (Grupo $grupo) => $grupo->update(['estado' => EstadoGrupo::Programado])],
    'group closed' => [fn (Grupo $grupo) => $grupo->update(['estado' => EstadoGrupo::Cerrado])],
    'group cancelled' => [fn (Grupo $grupo) => $grupo->update(['estado' => EstadoGrupo::Cancelado])],
    'period in progress' => [fn () => test()->periodo->update(['estado' => EstadoPeriodo::EnCurso])],
    'window already over' => [fn () => test()->travelTo(test()->periodo->matricula_fin->addDays(2))],
    'language inactive' => [fn () => test()->ingles->update(['activo' => false])],
]);

it('describes each offered group with its schedule, classroom and live vacancies', function (): void {
    $this->inglesI->update(['nivel' => NivelCurricular::Basico]);
    $this->inglesII->update(['nivel' => NivelCurricular::Basico]);
    $aula = Aula::factory()->create(['codigo' => 'A-101', 'nombre' => 'Laboratorio 1']);
    $grupo = Grupo::factory()->for($this->periodo)->for($this->inglesI)->create([
        'codigo' => 'ING-I-A', 'aula_id' => $aula->id, 'cupo_maximo' => 20, 'es_acelerado' => true, 'ciclo_hasta_id' => $this->inglesII->id,
    ]);
    $grupo->forceFill(['matriculados' => 18])->save();
    Horario::factory()->for($grupo)->create(['dia_semana' => 3, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    Horario::factory()->for($grupo)->create(['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);

    expect(app(OfertaDeMatricula::class)([]))->toEqual([new FichaDeGrupo(
        id: $grupo->id,
        codigo: 'ING-I-A',
        idioma: 'Inglés',
        cicloNumero: 1,
        nivel: 'Básico',
        modalidad: 'Presencial',
        esAcelerado: true,
        periodoId: $this->periodo->id,
        periodo: '2026-II',
        horarios: ['Lunes de 08:00 a 10:00', 'Miércoles de 18:00 a 20:00'],
        aula: 'A-101 · Laboratorio 1',
        enlaceVirtual: null,
        vacantesDisponibles: 2,
        sesiones: [new SesionDeClase(1, '08:00:00', '10:00:00'), new SesionDeClase(3, '18:00:00', '20:00:00')],
        aulaCodigo: 'A-101',
        programa: 'Jóvenes y Adultos',
        cicloNumeroHasta: 2,
    )]);
});

it('keeps a full group in the offer with no vacancies left', function (): void {
    $grupo = Grupo::factory()->for($this->periodo)->for($this->inglesI)->create(['cupo_maximo' => 10]);
    $grupo->forceFill(['matriculados' => 10])->save();

    expect(app(OfertaDeMatricula::class)([])[0]->vacantesDisponibles)->toBe(0);
});

it('loads the offer with a fixed number of queries', function (): void {
    foreach (range(1, 3) as $indice) {
        $grupo = Grupo::factory()->for($this->periodo)->for($this->inglesI)->create(['aula_id' => Aula::factory()]);
        Horario::factory()->for($grupo)->create(['dia_semana' => 1]);
        Horario::factory()->for($grupo)->create(['dia_semana' => 3]);
    }

    DB::enableQueryLog();
    app(OfertaDeMatricula::class)([]);

    expect(DB::getQueryLog())->toHaveCount(8);
});

it('describes groups by id, including virtual ones, whatever their state', function (): void {
    $virtual = Grupo::factory()->for(Periodo::factory()->create(['nombre' => '2025-II']))->for($this->inglesII)->create([
        'codigo' => 'ING-II-V', 'modalidad' => 'virtual', 'enlace_virtual' => 'https://meet.example.test/ing2', 'estado' => EstadoGrupo::Finalizado,
    ]);
    Grupo::factory()->for($this->periodo)->for($this->inglesI)->create();

    $detalle = app(DetalleDeGrupos::class)([$virtual->id]);

    expect($detalle)->toHaveKeys([$virtual->id])->toHaveCount(1)
        ->and($detalle[$virtual->id])
        ->codigo->toBe('ING-II-V')
        ->modalidad->toBe('Virtual')
        ->periodo->toBe('2025-II')
        ->aula->toBeNull()
        ->enlaceVirtual->toBe('https://meet.example.test/ing2');
});
