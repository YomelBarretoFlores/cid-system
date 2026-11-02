<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Tablero\Actions\CalcularPagosPorValidar;
use App\Modules\Tablero\Actions\CalcularRecaudacion;
use App\Modules\Tablero\Data\FilaDeRecaudacion;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Modules\Tablero\Data\PagoPorValidar;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Tests\Support\DatosDelTablero;

beforeEach(function (): void {
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->quechua = Idioma::factory()->create(['nombre' => 'Quechua']);
    $this->marzo = DatosDelTablero::periodo('Marzo 2027', '2027-03-01', '2027-03-31', numero: 3);
    $this->abril = DatosDelTablero::periodo('Abril 2027', '2027-04-01', '2027-04-30', numero: 4);
    $this->basicoMarzo = DatosDelTablero::grupo($this->marzo, $this->ingles);
    $this->intermedioMarzo = DatosDelTablero::grupo($this->marzo, $this->ingles, 5, NivelCurricular::Intermedio);
    $this->quechuaMarzo = DatosDelTablero::grupo($this->marzo, $this->quechua);
    $this->basicoAbril = DatosDelTablero::grupo($this->abril, $this->ingles);
});

it('adds the validated payments of a period by language and level, placing unlinked payments by their date', function (): void {
    DatosDelTablero::matricula($this->basicoMarzo, DatosDelTablero::pago(TipoConcepto::Matricula, '80.00', '2027-02-20'));
    DatosDelTablero::matricula($this->basicoMarzo, DatosDelTablero::pago(TipoConcepto::Matricula, '80.10', '2027-02-21'), retirada: true);
    DatosDelTablero::matricula($this->intermedioMarzo, DatosDelTablero::pago(TipoConcepto::Matricula, '95.00', '2027-02-22'));
    DatosDelTablero::matricula($this->quechuaMarzo, DatosDelTablero::pago(TipoConcepto::Matricula, '60.00', '2027-02-23'));
    // Paid in March for an April group: it belongs to April.
    DatosDelTablero::matricula($this->basicoAbril, DatosDelTablero::pago(TipoConcepto::Matricula, '80.00', '2027-03-28'));
    DatosDelTablero::pago(TipoConcepto::Pension, '180.00', '2027-03-01');
    DatosDelTablero::pago(TipoConcepto::Pension, '180.00', '2027-03-31');
    DatosDelTablero::pago(TipoConcepto::Pension, '180.00', '2027-04-01');
    $examen = DatosDelTablero::pago(TipoConcepto::ExamenClasificacion, '40.00', '2027-03-10');
    SolicitudClasificacion::factory()->create(['persona_id' => $examen->persona_id, 'idioma_id' => $this->quechua->id, 'pago_id' => $examen->id]);
    DatosDelTablero::pago(TipoConcepto::Pension, '180.00', '2027-03-15', 'pendiente');
    DatosDelTablero::pago(TipoConcepto::Pension, '180.00', '2027-03-15', 'rechazado');
    DatosDelTablero::pago(TipoConcepto::Pension, '180.00', '2027-03-15', 'anulado');

    $recaudacion = app(CalcularRecaudacion::class)(new FiltrosDelTablero($this->marzo->id));

    expect($recaudacion->totalCentimos)->toBe(71510)
        ->and($recaudacion->operaciones)->toBe(7)
        ->and($recaudacion->filas)->toEqual([
            new FilaDeRecaudacion('Inglés', 'Básico', 2, 16010),
            new FilaDeRecaudacion('Inglés', 'Intermedio', 1, 9500),
            new FilaDeRecaudacion('Quechua', 'Básico', 1, 6000),
            new FilaDeRecaudacion('Quechua', 'Sin nivel', 1, 4000),
            new FilaDeRecaudacion('Sin idioma', 'Sin nivel', 2, 36000),
        ]);
});

it('keeps only the payments that carry the chosen language and level', function (): void {
    DatosDelTablero::matricula($this->basicoMarzo, DatosDelTablero::pago(TipoConcepto::Matricula, '80.00', '2027-02-20'));
    DatosDelTablero::matricula($this->intermedioMarzo, DatosDelTablero::pago(TipoConcepto::Matricula, '95.00', '2027-02-22'));
    DatosDelTablero::matricula($this->quechuaMarzo, DatosDelTablero::pago(TipoConcepto::Matricula, '60.00', '2027-02-23'));
    DatosDelTablero::pago(TipoConcepto::Pension, '180.00', '2027-03-10');

    $calcular = app(CalcularRecaudacion::class);

    expect($calcular(new FiltrosDelTablero($this->marzo->id, $this->ingles->id))->totalCentimos)->toBe(17500)
        ->and($calcular(new FiltrosDelTablero($this->marzo->id, null, NivelCurricular::Basico))->totalCentimos)->toBe(14000)
        ->and($calcular(new FiltrosDelTablero($this->marzo->id, $this->ingles->id, NivelCurricular::Avanzado))->filas)->toBe([]);
});

it('measures the payments still waiting for validation in the period and the current backlog without personal data', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-20 15:00', 'America/Lima'));
    DatosDelTablero::pago(TipoConcepto::Matricula, '80.00', '2027-03-02');
    DatosDelTablero::pago(TipoConcepto::Pension, '180.00', '2027-03-03', 'rechazado');
    DatosDelTablero::pago(TipoConcepto::Pension, '180.00', '2027-03-04', 'anulado');
    $antiguo = DatosDelTablero::pago(TipoConcepto::Pension, '180.50', '2027-03-05', 'pendiente');
    Pago::query()->whereKey($antiguo->id)->update(['created_at' => CarbonImmutable::parse('2027-03-12 23:30', 'America/Lima')]);
    DatosDelTablero::pago(TipoConcepto::Matricula, '80.00', '2027-03-19', 'pendiente');
    // Outside March: it is in the backlog, not in the March rate.
    DatosDelTablero::pago(TipoConcepto::Matricula, '80.00', '2027-02-27', 'pendiente');

    $pagos = app(CalcularPagosPorValidar::class)(new FiltrosDelTablero($this->marzo->id));

    expect($pagos->registrados)->toBe(4)
        ->and($pagos->pendientes)->toBe(2)
        ->and($pagos->tasa())->toBe(50.0)
        ->and($pagos->porValidar)->toBe(3)
        ->and($pagos->centimosPorValidar)->toBe(34050)
        ->and($pagos->diasDelMasAntiguo)->toBe(8)
        ->and(array_map(fn (PagoPorValidar $pago): array => [$pago->concepto, $pago->centimos, $pago->dias], $pagos->masAntiguos))->toBe([
            ['Concepto pension', 18050, 8],
            ['Concepto matricula', 8000, 0],
            ['Concepto matricula', 8000, 0],
        ])
        ->and(array_keys(get_object_vars($pagos->masAntiguos[0])))->toBe(['pagoId', 'pagoUlid', 'concepto', 'centimos', 'fechaPago', 'dias']);
});

it('reports no rate and no backlog age when nothing was registered', function (): void {
    $pagos = app(CalcularPagosPorValidar::class)(new FiltrosDelTablero($this->marzo->id));

    expect($pagos->tasa())->toBeNull()
        ->and($pagos->porValidar)->toBe(0)
        ->and($pagos->diasDelMasAntiguo)->toBeNull()
        ->and($pagos->masAntiguos)->toBe([]);
});

it('refuses a period that does not exist', function (): void {
    app(CalcularRecaudacion::class)(new FiltrosDelTablero(999999));
})->throws(ReglaDeNegocioIncumplida::class, 'Elige un periodo válido.');
