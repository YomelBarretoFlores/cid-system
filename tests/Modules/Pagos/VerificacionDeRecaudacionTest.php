<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Actions\AnularPago;
use App\Modules\Pagos\Actions\ExportarRecaudacionPdf;
use App\Modules\Pagos\Actions\GenerarReporteDeRecaudacion;
use App\Modules\Pagos\Actions\ValidarPago;
use App\Modules\Pagos\Data\DetalleDeRecaudacion;
use App\Modules\Pagos\Data\FilaDeConcepto;
use App\Modules\Pagos\Data\FilaDeIdioma;
use App\Modules\Pagos\Data\FiltrosDeRecaudacion;
use App\Modules\Pagos\Data\ReporteDeRecaudacion as Reporte;
use App\Modules\Pagos\Enums\FuenteConcepto;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Filament\Pages\ReporteDeRecaudacion;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:00', 'America/Lima'));
    $this->tesorero = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Reportar->value]);
    $this->mat = Concepto::factory()->create(['codigo' => 'MAT-01', 'nombre' => 'Derecho de matrícula', 'tipo' => TipoConcepto::Matricula, 'fuente' => FuenteConcepto::Tupa]);
    $this->pen = Concepto::factory()->create(['codigo' => 'PEN-01', 'nombre' => 'Pensión', 'tipo' => TipoConcepto::Pension, 'fuente' => FuenteConcepto::Tusne]);
    $this->exa = Concepto::factory()->create(['codigo' => 'EXC-01', 'nombre' => 'Examen', 'tipo' => TipoConcepto::ExamenClasificacion, 'fuente' => FuenteConcepto::Tupa]);
    $this->persona = Persona::factory()->create(['numero_documento' => '41234567', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Rosa']);
});

function recaudacionPago(Concepto $c, Persona $p, string $monto, string $fecha, string $op): Pago
{
    return Pago::factory()->validado()->for($c)->create(['persona_id' => $p->id, 'monto' => $monto, 'fecha_pago' => $fecha, 'numero_operacion' => $op, 'medio_pago_id' => MedioDePago::idDe('caja_presencial')]);
}

function recaudacionReporte(string $desde = '2026-10-01', string $hasta = '2026-10-31', ?int $c = null, ?int $p = null): Reporte
{
    return app(GenerarReporteDeRecaudacion::class)(new FiltrosDeRecaudacion(CarbonImmutable::parse($desde), CarbonImmutable::parse($hasta), $c, $p));
}

function recaudacionCsv($test): string
{
    $c = $test->effects['download']['content'] ?? '';

    return base64_decode($c, true) ?: $c;
}

it('sums 1000 payments of 0.10 to exactly 100.00', function (): void {
    foreach (range(1, 1000) as $i) {
        recaudacionPago($this->mat, $this->persona, '0.10', '2026-10-05', "OP-{$i}");
    }
    $r = recaudacionReporte();
    expect($r->totalCentimos)->toBe(10000)->and($r->operaciones)->toBe(1000)
        ->and(Reporte::soles($r->totalCentimos))->toBe('100.00')->and($r->porConcepto[0]->centimos)->toBe(10000);
});

it('sums amounts near the numeric(12,2) ceiling exactly', function (): void {
    recaudacionPago($this->mat, $this->persona, '99999999.99', '2026-10-05', 'A');
    recaudacionPago($this->mat, $this->persona, '9999999.99', '2026-10-05', 'B');
    recaudacionPago($this->pen, $this->persona, '0.01', '2026-10-05', 'C');
    $r = recaudacionReporte();
    expect($r->totalCentimos)->toBe(9999999999 + 999999999 + 1)
        ->and(Reporte::soles($r->totalCentimos))->toBe('109,999,999.99')
        ->and(Reporte::decimal($r->totalCentimos))->toBe('109999999.99')
        ->and(Reporte::soles(5))->toBe('0.05');
});

it('counts a payment validated and drops it once voided; pending and rejected never count', function (): void {
    $pago = Pago::factory()->for($this->mat)->create(['persona_id' => $this->persona->id, 'monto' => '80.00', 'fecha_pago' => '2026-10-05', 'numero_operacion' => 'X1']);
    Pago::factory()->rechazado()->for($this->mat)->create(['fecha_pago' => '2026-10-05', 'numero_operacion' => 'R1']);
    expect(recaudacionReporte()->operaciones)->toBe(0);
    app(ValidarPago::class)($pago->id, $this->tesorero, boletaOriginalVerificada: true);
    expect(recaudacionReporte()->totalCentimos)->toBe(8000);
    app(AnularPago::class)($pago->id, $this->tesorero, 'Error de registro');
    expect(recaudacionReporte()->operaciones)->toBe(0)->and(recaudacionReporte()->totalCentimos)->toBe(0);
});

it('includes both boundary days and a single-day range', function (): void {
    recaudacionPago($this->mat, $this->persona, '1.00', '2026-10-01', 'D1');
    recaudacionPago($this->mat, $this->persona, '2.00', '2026-10-31', 'D31');
    recaudacionPago($this->mat, $this->persona, '4.00', '2026-09-30', 'D0');
    recaudacionPago($this->mat, $this->persona, '8.00', '2026-11-01', 'D32');
    expect(recaudacionReporte()->totalCentimos)->toBe(300)->and(recaudacionReporte('2026-10-31', '2026-10-31')->totalCentimos)->toBe(200);
});

it('period filter keeps only enrollment payments of that period; concept filter keeps only that concept', function (): void {
    $ing = Idioma::factory()->create(['nombre' => 'Inglés']);
    $a = Periodo::factory()->create(['nombre' => 'A']);
    $b = Periodo::factory()->create(['nombre' => 'B']);
    $ga = Grupo::factory()->for($a)->for(Ciclo::factory()->for($ing)->state(['numero' => 1, 'nivel' => NivelCurricular::Basico]))->create();
    $gb = Grupo::factory()->for($b)->for(Ciclo::factory()->for($ing)->state(['numero' => 2, 'nivel' => NivelCurricular::Basico]))->create();
    $p1 = recaudacionPago($this->mat, $this->persona, '10.00', '2026-10-05', 'M-A');
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => $ga->id, 'pago_id' => $p1->id]);
    $p2 = recaudacionPago($this->mat, $this->persona, '20.00', '2026-10-05', 'M-B');
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => $gb->id, 'pago_id' => $p2->id]);
    $p3 = recaudacionPago($this->exa, $this->persona, '40.00', '2026-10-05', 'EX');
    SolicitudClasificacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => $ing->id, 'pago_id' => $p3->id]);
    recaudacionPago($this->pen, $this->persona, '80.00', '2026-10-05', 'PEN');

    $r = recaudacionReporte(p: $a->id);
    expect($r->totalCentimos)->toBe(1000)->and(array_map(fn (DetalleDeRecaudacion $d): string => $d->numeroOperacion, $r->detalle))->toBe(['M-A'])
        ->and(array_map(fn (FilaDeConcepto $c): string => $c->codigo, $r->porConcepto))->toBe(['MAT-01'])
        ->and(recaudacionReporte(c: $this->pen->id)->totalCentimos)->toBe(8000)
        ->and(recaudacionReporte(c: $this->mat->id, p: $b->id)->totalCentimos)->toBe(2000)
        ->and(recaudacionReporte(c: $this->pen->id, p: $a->id)->operaciones)->toBe(0);
});

it('orders language/level rows by language, then curricular level, Sin idioma last', function (): void {
    $de = Idioma::factory()->create(['nombre' => 'Alemán']);
    $en = Idioma::factory()->create(['nombre' => 'Inglés']);
    $per = Periodo::factory()->create();
    $mk = function (Idioma $i, int $n, NivelCurricular $nv, string $op) use ($per): void {
        $g = Grupo::factory()->for($per)->for(Ciclo::factory()->for($i)->state(['numero' => $n, 'nivel' => $nv]))->create();
        $p = recaudacionPago($this->mat, $this->persona, '1.00', '2026-10-05', $op);
        Matricula::factory()->create(['persona_id' => Persona::factory()->create()->id, 'grupo_id' => $g->id, 'pago_id' => $p->id]);
    };
    recaudacionPago($this->pen, $this->persona, '1.00', '2026-10-05', 'PEN');
    $mk($en, 9, NivelCurricular::Avanzado, 'E3');
    $mk($en, 1, NivelCurricular::Basico, 'E1');
    $mk($de, 5, NivelCurricular::Intermedio, 'D2');
    $mk($en, 5, NivelCurricular::Intermedio, 'E2');
    $mk($en, 2, NivelCurricular::Basico, 'E1B');
    $ex = recaudacionPago($this->exa, $this->persona, '1.00', '2026-10-05', 'EX');
    SolicitudClasificacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => $en->id, 'pago_id' => $ex->id]);

    expect(array_map(fn (FilaDeIdioma $f): string => "{$f->idioma}|{$f->nivel}|{$f->operaciones}", recaudacionReporte()->porIdiomaYNivel))->toBe([
        'Alemán|Intermedio|1', 'Inglés|Básico|2', 'Inglés|Intermedio|1', 'Inglés|Avanzado|1', 'Inglés|Sin nivel|1', 'Sin idioma|Sin nivel|1',
    ]);
});

it('forbids the page to staff without pagos.reportar, to students and redirects guests', function (): void {
    $this->get(ReporteDeRecaudacion::getUrl())->assertRedirect();
    $cajero = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Validar->value, PermisoPago::Registrar->value, PermisoPago::Anular->value]);
    $this->actingAs($cajero)->get(ReporteDeRecaudacion::getUrl())->assertForbidden();
    $estudiante = User::factory()->create();
    Persona::factory()->create(['user_id' => $estudiante->id]);
    $this->actingAs($estudiante)->get(ReporteDeRecaudacion::getUrl())->assertForbidden();
    $soloReporte = User::factory()->create()->givePermissionTo(PermisoPago::Reportar->value);
    $this->actingAs($soloReporte)->get(ReporteDeRecaudacion::getUrl())->assertForbidden();
});

it('a user without pagos.reportar cannot mount the component nor run the downloads', function (): void {
    recaudacionPago($this->mat, $this->persona, '80.00', '2026-10-05', 'OP-SECRET');
    $cajero = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value]);
    Livewire::actingAs($cajero)->test(ReporteDeRecaudacion::class)->assertForbidden();
});

it('revoking pagos.reportar mid-session blocks both downloads', function (): void {
    recaudacionPago($this->mat, $this->persona, '80.00', '2026-10-05', 'OP-SECRET');
    $this->actingAs($this->tesorero);
    $lw = Livewire::test(ReporteDeRecaudacion::class)->assertSee('OP-SECRET');
    $this->tesorero->revokePermissionTo(PermisoPago::Reportar->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->tesorero->unsetRelation('permissions');
    expect($this->tesorero->can('reportar', Pago::class))->toBeFalse();
    $lw->call('mountAction', 'descargarCsv')->assertForbidden()->assertNoFileDownloaded();
});

it('after revocation the next Livewire update still renders the report detail', function (): void {
    recaudacionPago($this->mat, $this->persona, '80.00', '2026-10-05', 'OP-SECRET');
    $this->actingAs($this->tesorero);
    $lw = Livewire::test(ReporteDeRecaudacion::class)->assertSee('OP-SECRET');
    $this->tesorero->revokePermissionTo(PermisoPago::Reportar->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->tesorero->unsetRelation('permissions');
    $lw->set('data.hasta', '2026-10-23')->assertForbidden()->assertDontSee('OP-SECRET');
});

it('neutralizes formula cells and escapes commas and quotes in the CSV', function (): void {
    $c = Concepto::factory()->create(['codigo' => '=CMD', 'nombre' => 'Raro', 'tipo' => TipoConcepto::Pension, 'fuente' => FuenteConcepto::Tusne]);
    $leading = ['+Suma', '-Resta', '@Arroba', "\tTab", "\rCr"];
    foreach ($leading as $i => $ap) {
        $p = Persona::factory()->create(['apellido_paterno' => $ap, 'apellido_materno' => 'X', 'nombres' => 'Y']);
        recaudacionPago($c, $p, '1.00', '2026-10-05', "OK-{$i}");
    }
    $q = Persona::factory()->create(['apellido_paterno' => 'O"Brien', 'apellido_materno' => 'De, la', 'nombres' => 'Ana']);
    recaudacionPago($this->mat, $q, '1.00', '2026-10-06', '=HYPERLINK("HTTP://X")');
    recaudacionPago($this->mat, $q, '1.00', '2026-10-06', '-2+3');
    $this->actingAs($this->tesorero);
    $csv = recaudacionCsv(Livewire::test(ReporteDeRecaudacion::class)->callAction('descargarCsv'));
    $rows = array_map(fn ($l): array => str_getcsv($l, ',', '"', ''), preg_split('/\n(?=\d\d\/|Total)/', ltrim($csv, "\u{FEFF}")));
    $cells = array_merge(...array_slice($rows, 1));
    foreach ($cells as $cell) {
        expect(preg_match('/^[=+\-@\t\r]/', $cell))->toBe(0, 'unsafe cell: '.json_encode($cell));
    }
    expect($csv)->toStartWith("\u{FEFF}")
        ->and($csv)->toContain('"\'=HYPERLINK(""HTTP://X"")"')
        ->and($csv)->toContain('"O""Brien De, la, Ana"')
        ->and($csv)->toContain("'=CMD · Raro")
        ->and(end($rows))->toBe(['Total', '', '', '', '', '', '', '', '', '7.00']);
});

it('renders the PDF with real DOMPDF and the HTML carries period and totals', function (): void {
    recaudacionPago($this->mat, $this->persona, '1234.56', '2026-10-05', 'OP-1');
    recaudacionPago($this->mat, $this->persona, '0.44', '2026-10-06', 'OP-2');
    $r = recaudacionReporte();
    $pdf = app(ExportarRecaudacionPdf::class)($r);
    $bin = $pdf->generatePdfContent();
    $html = view('pagos::pdf.recaudacion', ['reporte' => $r])->render();
    expect($bin)->toStartWith('%PDF')->and(strlen($bin))->toBeGreaterThan(1000)
        ->and($html)->toContain('Del 01/10/2026 al 31/10/2026')->and($html)->toContain('S/ 1,235.00')->and($html)->toContain('DNI 41234567');
});

it('tampered filters do not crash', function (string $campo, mixed $valor): void {
    recaudacionPago($this->mat, $this->persona, '80.00', '2026-10-05', 'OP-1');
    $this->actingAs($this->tesorero);
    Livewire::test(ReporteDeRecaudacion::class)->set("data.{$campo}", $valor)->assertOk()->assertDontSee('Internal Server Error');
})->with([
    'concept text' => ['concepto_id', 'abc'],
    'concept sql' => ['concepto_id', '1 OR 1=1'],
    'concept array' => ['concepto_id', ['1']],
    'period text' => ['periodo_id', 'x'],
    'period float' => ['periodo_id', '1.5'],
    'period huge' => ['periodo_id', '99999999999999999999'],
    'desde garbage' => ['desde', 'not-a-date'],
    'hasta impossible' => ['hasta', '2026-13-45'],
    'desde array' => ['desde', ['2026-10-01']],
    'desde empty' => ['desde', ''],
]);

it('tampered filters do not crash the CSV download', function (string $campo, mixed $valor): void {
    $this->actingAs($this->tesorero);
    Livewire::test(ReporteDeRecaudacion::class)->set("data.{$campo}", $valor)->callAction('descargarCsv')->assertOk();
})->with([
    'desde garbage' => ['desde', 'not-a-date'],
    'concept text' => ['concepto_id', 'abc'],
]);
