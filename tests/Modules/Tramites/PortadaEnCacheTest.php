<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\FuenteDeConcepto;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:30', 'America/Lima'));
    $this->periodo = Periodo::factory()->create([
        'nombre' => '2026-II', 'estado' => EstadoPeriodo::MatriculaAbierta,
        'matricula_inicio' => '2026-10-06', 'matricula_fin' => '2026-10-24', 'fecha_inicio' => '2026-11-03', 'fecha_fin' => '2026-12-20',
    ]);
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->grupo = Grupo::factory()->for($this->periodo)->for(Ciclo::factory()->for($this->ingles)->state(['numero' => 1]))->create(['estado' => EstadoGrupo::Abierto, 'cupo_maximo' => 20]);
    $this->concepto = Concepto::factory()->create(['codigo' => 'TUSNE-012', 'nombre' => 'Matrícula por ciclo', 'fuente_id' => FuenteDeConcepto::idDe('tusne')]);
});

it('builds the public page once and serves the same content for a few minutes', function (): void {
    $this->get('/')->assertOk()->assertSeeText('20 vacantes');

    // A write that fires no model event, like the enrollment counter, shows once the short cache expires.
    DB::table('aca_grupos')->where('id', $this->grupo->id)->update(['matriculados' => 5]);

    $this->get('/')->assertSeeText('20 vacantes');
    $this->travel(2)->minutes();
    $this->get('/')->assertSeeText('20 vacantes');
    $this->travel(2)->minutes();
    $this->get('/')->assertSeeText('15 vacantes');
});

it('rebuilds the public page at once when the offer or a fee changes', function (): void {
    $this->get('/')->assertSeeText('Inglés')->assertDontSeeText('S/ 180.00');

    Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '180.00', 'vigente_desde' => '2026-03-01']);
    $this->get('/')->assertSeeText('S/ 180.00');

    $this->ingles->update(['nombre' => 'Inglés británico']);
    $this->get('/')->assertSeeText('Inglés británico');

    $this->grupo->update(['estado' => EstadoGrupo::Cancelado]);
    $this->get('/')->assertDontSeeText('20 vacantes');
});

it('rebuilds the public page when the day changes in Lima, so the dates move on', function (): void {
    $this->periodo->update(['matricula_fin' => '2026-10-25', 'fecha_inicio' => '2026-10-25']);
    $this->travelTo(CarbonImmutable::parse('2026-10-24 23:59:30', 'America/Lima'));
    $this->get('/')->assertSeeTextInOrder(['En curso', '6 – 25 oct', 'Próximo', '25 oct']);

    $this->travel(1)->minutes();

    $this->get('/')->assertSeeTextInOrder(['En curso', '6 – 25 oct', 'Hoy', '25 oct']);
});

it('rebuilds the public page when any model it shows is saved or deleted', function (Closure $cambiar): void {
    $this->get('/');
    DB::table('aca_grupos')->where('id', $this->grupo->id)->update(['matriculados' => 5]);

    $cambiar($this);

    $this->get('/')->assertSeeText('15 vacantes');
})->with([
    'period' => [fn ($prueba) => $prueba->periodo->update(['nombre' => '2026-II B'])],
    'cycle' => [fn ($prueba) => $prueba->grupo->ciclo->update(['horas_lectivas' => 70])],
    'schedule saved' => [fn ($prueba) => Horario::factory()->for($prueba->grupo)->create(['dia_semana' => 2, 'hora_inicio' => '19:00', 'hora_fin' => '21:00'])],
    'schedule deleted' => [fn ($prueba) => Horario::factory()->for($prueba->grupo)->createQuietly(['dia_semana' => 3, 'hora_inicio' => '19:00', 'hora_fin' => '21:00'])->delete()],
    'concept' => [fn ($prueba) => $prueba->concepto->update(['nombre' => 'Matrícula ordinaria'])],
]);

it('serves the cached page from a store that serializes it, as production does', function (): void {
    config(['cache.default' => 'file']);
    Cache::flush();
    Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '180.00', 'vigente_desde' => '2026-03-01']);

    $this->get('/')->assertOk()->assertSeeText('20 vacantes');
    DB::table('aca_grupos')->where('id', $this->grupo->id)->update(['matriculados' => 5]);

    // Still the stored page, read back from the serialized store with its value objects intact.
    $this->get('/')->assertOk()->assertSeeText(['20 vacantes', 'Inglés', 'S/ 180.00', 'Oferta 2026-II']);

    Cache::flush();
});
