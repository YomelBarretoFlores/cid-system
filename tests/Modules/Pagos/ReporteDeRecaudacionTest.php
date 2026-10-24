<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Contracts\ContextoAcademicoDePagos;
use App\Modules\Matricula\Data\ContextoAcademico;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Actions\GenerarReporteDeRecaudacion;
use App\Modules\Pagos\Data\FilaDeConcepto;
use App\Modules\Pagos\Data\FilaDeIdioma;
use App\Modules\Pagos\Data\FiltrosDeRecaudacion;
use App\Modules\Pagos\Data\ReporteDeRecaudacion;
use App\Modules\Pagos\Enums\FuenteConcepto;
use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->matricula = Concepto::factory()->create(['codigo' => 'MAT-01', 'nombre' => 'Derecho de matrícula', 'tipo' => TipoConcepto::Matricula, 'fuente' => FuenteConcepto::Tupa]);
    $this->pension = Concepto::factory()->create(['codigo' => 'PEN-01', 'nombre' => 'Pensión mensual', 'tipo' => TipoConcepto::Pension, 'fuente' => FuenteConcepto::Tusne]);
    $this->examen = Concepto::factory()->create(['codigo' => 'EXC-01', 'nombre' => 'Examen de clasificación', 'tipo' => TipoConcepto::ExamenClasificacion, 'fuente' => FuenteConcepto::Tupa]);

    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->enero = Periodo::factory()->create(['nombre' => 'Enero 2027']);
    $this->febrero = Periodo::factory()->create(['nombre' => 'Febrero 2027']);
    $this->grupoEnero = Grupo::factory()->for($this->enero)->for(Ciclo::factory()->for($this->ingles)->state(['numero' => 1, 'nivel' => NivelCurricular::Basico]))->create();
    $this->grupoFebrero = Grupo::factory()->for($this->febrero)->for(Ciclo::factory()->for($this->ingles)->state(['numero' => 5, 'nivel' => NivelCurricular::Intermedio]))->create();

    $this->rosa = Persona::factory()->create(['numero_documento' => '41234567', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Rosa']);
    $this->luis = Persona::factory()->create(['numero_documento' => '42345678', 'apellido_paterno' => 'Rojas', 'apellido_materno' => 'Flores', 'nombres' => 'Luis']);
});

function pagoValidado(Concepto $concepto, Persona $persona, string $monto, string $fecha, string $operacion): Pago
{
    return Pago::factory()->validado()->for($concepto)->create([
        'persona_id' => $persona->id, 'monto' => $monto, 'fecha_pago' => $fecha, 'numero_operacion' => $operacion, 'medio_pago' => MedioPago::CajaPresencial,
    ]);
}

function reporte(string $desde = '2026-10-01', string $hasta = '2026-10-31', ?int $conceptoId = null, ?int $periodoId = null): ReporteDeRecaudacion
{
    return app(GenerarReporteDeRecaudacion::class)(new FiltrosDeRecaudacion(CarbonImmutable::parse($desde), CarbonImmutable::parse($hasta), $conceptoId, $periodoId));
}

it('describes the academic context of enrollment and placement payments', function (): void {
    $pagoMatricula = pagoValidado($this->matricula, $this->rosa, '80.00', '2026-10-05', 'OP-1');
    Matricula::factory()->create(['persona_id' => $this->rosa->id, 'grupo_id' => $this->grupoEnero->id, 'pago_id' => $pagoMatricula->id]);
    $pagoExamen = pagoValidado($this->examen, $this->luis, '40.00', '2026-10-06', 'OP-2');
    SolicitudClasificacion::factory()->create(['persona_id' => $this->luis->id, 'idioma_id' => $this->ingles->id, 'pago_id' => $pagoExamen->id]);
    $pagoPension = pagoValidado($this->pension, $this->rosa, '180.00', '2026-10-07', 'OP-3');

    expect(app(ContextoAcademicoDePagos::class)([$pagoMatricula->id, $pagoExamen->id, $pagoPension->id]))->toEqual([
        $pagoMatricula->id => new ContextoAcademico(periodoId: $this->enero->id, periodo: 'Enero 2027', idioma: 'Inglés', nivel: 'Básico'),
        $pagoExamen->id => new ContextoAcademico(periodoId: null, periodo: null, idioma: 'Inglés', nivel: null),
    ]);
});

it('counts only validated payments in the date range, by concept, with exact totals', function (): void {
    pagoValidado($this->matricula, $this->rosa, '80.00', '2026-10-01', 'OP-1');
    pagoValidado($this->matricula, $this->luis, '0.10', '2026-10-31', 'OP-2');
    pagoValidado($this->pension, $this->rosa, '180.20', '2026-10-15', 'OP-3');
    pagoValidado($this->pension, $this->rosa, '180.00', '2026-11-01', 'FUERA-1');
    pagoValidado($this->pension, $this->rosa, '180.00', '2026-09-30', 'FUERA-2');
    Pago::factory()->for($this->pension)->create(['fecha_pago' => '2026-10-10', 'numero_operacion' => 'PENDIENTE']);
    Pago::factory()->rechazado()->for($this->pension)->create(['fecha_pago' => '2026-10-10', 'numero_operacion' => 'RECHAZADO']);
    Pago::factory()->anulado()->for($this->pension)->create(['fecha_pago' => '2026-10-10', 'numero_operacion' => 'ANULADO']);

    $reporte = reporte();

    expect($reporte->totalCentimos)->toBe(26030)
        ->and($reporte->operaciones)->toBe(3)
        ->and(array_map(fn (FilaDeConcepto $fila): array => [$fila->codigo, $fila->nombre, $fila->fuente, $fila->centimos, $fila->operaciones], $reporte->porConcepto))->toBe([
            ['MAT-01', 'Derecho de matrícula', 'TUPA', 8010, 2],
            ['PEN-01', 'Pensión mensual', 'TUSNE', 18020, 1],
        ])
        ->and(collect($reporte->detalle)->pluck('numeroOperacion')->all())->toBe(['OP-1', 'OP-3', 'OP-2'])
        ->and(ReporteDeRecaudacion::soles(26030))->toBe('260.30')
        ->and(ReporteDeRecaudacion::soles(123456789))->toBe('1,234,567.89');
});

it('groups by language and level, leaving unrelated payments without a language', function (): void {
    $enero = pagoValidado($this->matricula, $this->rosa, '80.00', '2026-10-05', 'OP-1');
    Matricula::factory()->create(['persona_id' => $this->rosa->id, 'grupo_id' => $this->grupoEnero->id, 'pago_id' => $enero->id]);
    $febrero = pagoValidado($this->matricula, $this->luis, '80.00', '2026-10-06', 'OP-2');
    Matricula::factory()->create(['persona_id' => $this->luis->id, 'grupo_id' => $this->grupoFebrero->id, 'pago_id' => $febrero->id]);
    $examen = pagoValidado($this->examen, $this->luis, '40.00', '2026-10-07', 'OP-3');
    SolicitudClasificacion::factory()->create(['persona_id' => $this->luis->id, 'idioma_id' => $this->ingles->id, 'pago_id' => $examen->id]);
    pagoValidado($this->pension, $this->rosa, '180.00', '2026-10-08', 'OP-4');

    expect(array_map(fn (FilaDeIdioma $fila): array => [$fila->idioma, $fila->nivel, $fila->centimos, $fila->operaciones], reporte()->porIdiomaYNivel))->toBe([
        ['Inglés', 'Básico', 8000, 1],
        ['Inglés', 'Intermedio', 8000, 1],
        ['Inglés', 'Sin nivel', 4000, 1],
        ['Sin idioma', 'Sin nivel', 18000, 1],
    ]);
});

it('lists the detail per student with document, name, concept, language, level, method and amount', function (): void {
    $pago = pagoValidado($this->matricula, $this->rosa, '80.00', '2026-10-05', 'OP-1');
    Matricula::factory()->create(['persona_id' => $this->rosa->id, 'grupo_id' => $this->grupoEnero->id, 'pago_id' => $pago->id]);

    $fila = reporte()->detalle[0];

    expect([$fila->fechaPago->toDateString(), $fila->documento, $fila->estudiante, $fila->concepto, $fila->idioma, $fila->nivel, $fila->periodo, $fila->medioPago, $fila->numeroOperacion, $fila->centimos])
        ->toBe(['2026-10-05', 'DNI 41234567', 'Quispe Mamani, Rosa', 'MAT-01 · Derecho de matrícula', 'Inglés', 'Básico', 'Enero 2027', 'Caja presencial', 'OP-1', 8000]);
});

it('filters by concept and by academic period', function (): void {
    $enero = pagoValidado($this->matricula, $this->rosa, '80.00', '2026-10-05', 'OP-1');
    Matricula::factory()->create(['persona_id' => $this->rosa->id, 'grupo_id' => $this->grupoEnero->id, 'pago_id' => $enero->id]);
    $febrero = pagoValidado($this->matricula, $this->luis, '80.00', '2026-10-06', 'OP-2');
    Matricula::factory()->create(['persona_id' => $this->luis->id, 'grupo_id' => $this->grupoFebrero->id, 'pago_id' => $febrero->id]);
    pagoValidado($this->pension, $this->rosa, '180.00', '2026-10-08', 'OP-3');

    $delPeriodo = reporte(periodoId: $this->enero->id);

    expect(collect(reporte(conceptoId: $this->pension->id)->detalle)->pluck('numeroOperacion')->all())->toBe(['OP-3'])
        ->and(collect($delPeriodo->detalle)->pluck('numeroOperacion')->all())->toBe(['OP-1'])
        ->and([$delPeriodo->totalCentimos, $delPeriodo->operaciones])->toBe([8000, 1])
        ->and(array_map(fn (FilaDeConcepto $fila): array => [$fila->codigo, $fila->centimos, $fila->operaciones], $delPeriodo->porConcepto))->toBe([['MAT-01', 8000, 1]])
        ->and(array_map(fn (FilaDeIdioma $fila): array => [$fila->idioma, $fila->nivel, $fila->centimos], $delPeriodo->porIdiomaYNivel))->toBe([['Inglés', 'Básico', 8000]]);
});

it('refuses a date range that ends before it starts', function (): void {
    expect(fn (): ReporteDeRecaudacion => reporte('2026-10-31', '2026-10-01'))->toThrow(ReglaDeNegocioIncumplida::class, 'La fecha inicial no puede ser posterior a la final.');
});
