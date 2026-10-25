<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Tablero\Actions\CalcularDemandaHoraria;
use App\Modules\Tablero\Actions\CalcularDesercion;
use App\Modules\Tablero\Actions\CalcularMatriculas;
use App\Modules\Tablero\Data\FilaDeDesercion;
use App\Modules\Tablero\Data\FilaDeMatriculas;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Modules\Tablero\Data\FranjaHoraria;
use App\Modules\Tablero\Data\PuntoDeMatriculas;
use Tests\Support\DatosDelTablero;

beforeEach(function (): void {
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->quechua = Idioma::factory()->create(['nombre' => 'Quechua']);
    $this->marzo2026 = DatosDelTablero::periodo('Marzo 2026', '2026-03-01', '2026-03-31', 2026, 3);
    $this->febrero = DatosDelTablero::periodo('Febrero 2027', '2027-02-01', '2027-02-28', 2027, 2);
    $this->marzo = DatosDelTablero::periodo('Marzo 2027', '2027-03-01', '2027-03-31', 2027, 3);
    $abril = DatosDelTablero::periodo('Abril 2027', '2027-04-01', '2027-04-30', 2027, 4);

    $this->basico = DatosDelTablero::grupo($this->marzo, $this->ingles, atributos: ['cupo_maximo' => 25]);
    $this->intermedio = DatosDelTablero::grupo($this->marzo, $this->ingles, 5, NivelCurricular::Intermedio, ['cupo_maximo' => 20]);
    $this->quechuaBasico = DatosDelTablero::grupo($this->marzo, $this->quechua, atributos: ['cupo_maximo' => 30]);
    $cancelado = DatosDelTablero::grupo($this->marzo, $this->ingles, 9, NivelCurricular::Avanzado, ['estado' => EstadoGrupo::Cancelado]);

    collect([[$this->basico, false], [$this->basico, false], [$this->basico, true], [$this->intermedio, true], [$this->quechuaBasico, false]])
        ->each(fn (array $matricula): Matricula => DatosDelTablero::matricula($matricula[0], retirada: $matricula[1]));
    collect(range(1, 4))->each(fn (): Matricula => DatosDelTablero::matricula(DatosDelTablero::grupo($this->febrero, $this->ingles)));
    collect(range(1, 2))->each(fn (): Matricula => DatosDelTablero::matricula(DatosDelTablero::grupo($this->marzo2026, $this->ingles)));
    collect(range(1, 7))->each(fn (): Matricula => DatosDelTablero::matricula(DatosDelTablero::grupo($abril, $this->ingles)));

    DatosDelTablero::horario($this->basico, [1, 2, 3, 4, 5], '08:00', '10:00');
    DatosDelTablero::horario($this->intermedio, [1, 3, 5], '08:00', '10:00');
    DatosDelTablero::horario($this->intermedio, [2, 4], '08:00', '10:00');
    DatosDelTablero::horario($this->quechuaBasico, [6], '15:00', '18:00');
    DatosDelTablero::horario($cancelado, [1, 2, 3, 4, 5], '08:00', '10:00');
});

it('counts the enrollments of the period and compares them with the previous period and the same period a year before', function (): void {
    $matriculas = app(CalcularMatriculas::class)(new FiltrosDelTablero($this->marzo->id));

    expect($matriculas->total)->toBe(5)
        ->and($matriculas->anterior)->toEqual(new PuntoDeMatriculas($this->febrero->id, 'Febrero 2027', 4))
        ->and($matriculas->delAnioAnterior)->toEqual(new PuntoDeMatriculas($this->marzo2026->id, 'Marzo 2026', 2))
        ->and($matriculas->crecimientoMensual())->toBe(25.0)
        ->and($matriculas->crecimientoAnual())->toBe(150.0)
        ->and($matriculas->serie)->toEqual([
            new PuntoDeMatriculas($this->marzo2026->id, 'Marzo 2026', 2),
            new PuntoDeMatriculas($this->febrero->id, 'Febrero 2027', 4),
            new PuntoDeMatriculas($this->marzo->id, 'Marzo 2027', 5),
        ])
        ->and($matriculas->porIdioma)->toEqual([new FilaDeMatriculas('Inglés', 4), new FilaDeMatriculas('Quechua', 1)]);
});

it('reports no growth when there is no period to compare with, and filters the enrollments by language and level', function (): void {
    $calcular = app(CalcularMatriculas::class);
    $primero = $calcular(new FiltrosDelTablero($this->marzo2026->id));
    $quechua = $calcular(new FiltrosDelTablero($this->marzo->id, $this->quechua->id));

    expect($primero->anterior)->toBeNull()
        ->and($primero->crecimientoMensual())->toBeNull()
        ->and($primero->crecimientoAnual())->toBeNull()
        ->and($quechua->total)->toBe(1)
        ->and($quechua->anterior?->total)->toBe(0)
        ->and($quechua->crecimientoMensual())->toBeNull()
        ->and($calcular(new FiltrosDelTablero($this->marzo->id, null, NivelCurricular::Intermedio))->total)->toBe(1);
});

it('measures desertion as withdrawn over enrolled, by level and by language', function (): void {
    $desercion = app(CalcularDesercion::class)(new FiltrosDelTablero($this->marzo->id));

    expect($desercion->total)->toEqual(new FilaDeDesercion('Total', 5, 2))
        ->and($desercion->total->tasa())->toBe(40.0)
        ->and($desercion->porNivel)->toEqual([new FilaDeDesercion('Básico', 4, 1), new FilaDeDesercion('Intermedio', 1, 1)])
        ->and($desercion->porIdioma)->toEqual([new FilaDeDesercion('Inglés', 4, 2), new FilaDeDesercion('Quechua', 1, 0)])
        ->and($desercion->porIdioma[1]->tasa())->toBe(0.0)
        ->and(app(CalcularDesercion::class)(new FiltrosDelTablero($this->marzo2026->id, $this->quechua->id))->total->tasa())->toBeNull();
});

it('ranks the schedule slots by active enrollments, counting each group once per slot', function (): void {
    $franjas = app(CalcularDemandaHoraria::class)(new FiltrosDelTablero($this->marzo->id));

    expect($franjas)->toEqual([
        new FranjaHoraria('08:00', '10:00', [1, 2, 3, 4, 5], 2, 2, 45),
        new FranjaHoraria('15:00', '18:00', [6], 1, 1, 30),
    ])
        ->and($franjas[0]->ocupacion())->toBe(4.4)
        ->and(app(CalcularDemandaHoraria::class)(new FiltrosDelTablero($this->marzo->id, $this->quechua->id)))->toEqual([new FranjaHoraria('15:00', '18:00', [6], 1, 1, 30)]);
});

it('groups desertion by a language whose name is only digits', function (): void {
    $this->quechua->update(['nombre' => '2027']);

    expect(app(CalcularDesercion::class)(new FiltrosDelTablero($this->marzo->id))->porIdioma)
        ->toEqual([new FilaDeDesercion('2027', 1, 0), new FilaDeDesercion('Inglés', 4, 2)]);
});
