<?php

declare(strict_types=1);

// Probes from the independent verification of M5, kept as regression tests.

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tablero\Actions\ArmarTablero;
use App\Modules\Tablero\Actions\CalcularDemandaHoraria;
use App\Modules\Tablero\Actions\CalcularDesercion;
use App\Modules\Tablero\Actions\CalcularMatriculas;
use App\Modules\Tablero\Actions\CalcularPagosPorValidar;
use App\Modules\Tablero\Actions\CalcularRecaudacion;
use App\Modules\Tablero\Actions\CalcularRentabilidadPorGrupo;
use App\Modules\Tablero\Actions\ExportarTableroExcel;
use App\Modules\Tablero\Actions\ExportarTableroPdf;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Modules\Tablero\Enums\PermisoTablero;
use App\Modules\Tablero\Filament\Pages\TableroGerencial;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\DatosDelTablero as T;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-20 10:00', 'America/Lima'));
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->quechua = Idioma::factory()->create(['nombre' => 'Quechua']);
    $this->febrero = T::periodo('Febrero 2027', '2027-02-01', '2027-02-28', numero: 2);
    $this->marzo = T::periodo('Marzo 2027', '2027-03-01', '2027-03-31', numero: 3, estado: EstadoPeriodo::EnCurso);
});

it('P-rev: excludes non-validated, places transfers, unlinked fees and placement payments, exact cents, inclusive window', function (): void {
    $g = T::grupo($this->marzo, $this->ingles, atributos: ['codigo' => 'ING-M']);
    $gFeb = T::grupo($this->febrero, $this->ingles, atributos: ['codigo' => 'ING-F']);
    T::matricula($g, T::pago(TipoConcepto::Matricula, '0.10', '2027-02-15'));
    T::matricula($g, T::pago(TipoConcepto::Matricula, '0.20', '2027-02-15'));
    T::matricula($g, T::pago(TipoConcepto::Matricula, '99.00', '2027-03-05', 'anulado'));
    T::matricula($g, T::pago(TipoConcepto::Matricula, '98.00', '2027-03-05', 'rechazado'));
    T::matricula($g, T::pago(TipoConcepto::Matricula, '97.00', '2027-03-05', 'pendiente'));
    // transfer: enrollment moved from Feb group to Mar group
    $m = T::matricula($gFeb, T::pago(TipoConcepto::Matricula, '50.00', '2027-01-20'));
    $m->update(['grupo_id' => $g->id]);
    T::pago(TipoConcepto::Matricula, '7.00', '2027-03-01'); // unlinked validated fee on start boundary
    T::pago(TipoConcepto::Pension, '3.00', '2027-03-31'); // end boundary
    T::pago(TipoConcepto::Pension, '1000.00', '2027-04-01'); // outside
    $exam = T::pago(TipoConcepto::ExamenClasificacion, '20.00', '2027-03-10');
    SolicitudClasificacion::factory()->create(['idioma_id' => $this->quechua->id, 'pago_id' => $exam->id, 'persona_id' => $exam->persona_id]);

    $r = app(CalcularRecaudacion::class)(new FiltrosDelTablero($this->marzo->id));
    $filas = collect($r->filas)->map(fn ($f): array => [$f->idioma, $f->nivel, $f->operaciones, $f->centimos])->all();

    expect($r->totalCentimos)->toBe(30 + 5000 + 700 + 300 + 2000)
        ->and($filas)->toBe([['Inglés', 'Básico', 3, 5030], ['Quechua', 'Sin nivel', 1, 2000], ['Sin idioma', 'Sin nivel', 2, 1000]]);

    $feb = app(CalcularRecaudacion::class)(new FiltrosDelTablero($this->febrero->id));
    expect($feb->totalCentimos)->toBe(0);

    $q = app(CalcularRecaudacion::class)(new FiltrosDelTablero($this->marzo->id, $this->quechua->id));
    expect($q->totalCentimos)->toBe(2000);
    $b = app(CalcularRecaudacion::class)(new FiltrosDelTablero($this->marzo->id, null, NivelCurricular::Basico));
    expect($b->totalCentimos)->toBe(5030);
});

it('P-pend: boundary dates included, voided excluded, Lima days', function (): void {
    T::pago(TipoConcepto::Pension, '10.00', '2027-03-01', 'pendiente');
    T::pago(TipoConcepto::Pension, '10.00', '2027-03-31', 'pendiente');
    T::pago(TipoConcepto::Pension, '10.00', '2027-03-15', 'validado');
    T::pago(TipoConcepto::Pension, '10.00', '2027-03-15', 'rechazado');
    T::pago(TipoConcepto::Pension, '10.00', '2027-03-15', 'anulado');
    T::pago(TipoConcepto::Pension, '10.00', '2027-02-28', 'pendiente');
    $tarde = T::pago(TipoConcepto::Pension, '1.00', '2027-03-19', 'pendiente');
    // 2027-03-20 03:00 UTC = 2027-03-19 22:00 Lima -> 1 day
    DB::table('pag_pagos')->where('id', $tarde->id)->update(['created_at' => '2027-03-20 03:00:00+00']);

    $p = app(CalcularPagosPorValidar::class)(new FiltrosDelTablero($this->marzo->id));

    expect([$p->registrados, $p->pendientes, $p->porValidar, $p->centimosPorValidar])->toBe([5, 3, 4, 3100])
        ->and($p->diasDelMasAntiguo)->toBe(1)
        ->and($p->masAntiguos[0]->pagoId)->toBe($tarde->id)
        ->and($p->masAntiguos[0]->dias)->toBe(1);
});

it('P-rent: cost change on class date, two categories, cancelled group, no cost', function (): void {
    $g = T::grupo($this->marzo, $this->ingles, atributos: ['codigo' => 'ING-M']);
    $cancelado = T::grupo($this->marzo, $this->ingles, atributos: ['codigo' => 'ING-X', 'estado' => EstadoGrupo::Cancelado]);
    T::costo('contratado', '40.00', '2027-01-01', '2027-03-09');
    T::costo('contratado', '50.00', '2027-03-10');
    T::costo('nombrado', '60.00', '2027-01-01');
    $c = Docente::factory()->create(['condicion_laboral' => CondicionLaboral::Contratado]);
    $n = Docente::factory()->create(['condicion_laboral' => CondicionLaboral::Nombrado]);
    $t = Docente::factory()->create(['condicion_laboral' => CondicionLaboral::Tercero]);
    T::clase($g, $c, '2027-03-09', '07:00', '09:00'); // 2h x 40
    T::clase($g, $c, '2027-03-10', '07:00', '09:00'); // 2h x 50
    T::clase($g, $n, '2027-03-11', '07:00', '08:30'); // 1.5h x 60
    T::clase($g, $t, '2027-03-12', '07:00', '07:45'); // 0.75h no cost
    T::clase($cancelado, $c, '2027-03-12', '07:00', '09:00');
    T::matricula($g, T::pago(TipoConcepto::Matricula, '80.00', '2027-02-20'), retirada: true);
    T::matricula($g, T::pago(TipoConcepto::Pension, '180.00', '2027-02-20'));

    $r = app(CalcularRentabilidadPorGrupo::class)(new FiltrosDelTablero($this->marzo->id));

    expect(count($r->filas))->toBe(1)
        ->and($r->filas[0]->codigo)->toBe('ING-M')
        ->and($r->filas[0]->horasCentesimas)->toBe(550)
        ->and($r->filas[0]->costoCentimos)->toBe(8000 + 10000 + 9000)
        ->and($r->filas[0]->horasSinCostoCentesimas)->toBe(75)
        ->and($r->filas[0]->ingresosCentimos)->toBe(8000)
        ->and($r->filas[0]->matriculas)->toBe(2);
});

it('P-growth: same start date periods and missing previous-year', function (): void {
    $marzoB = T::periodo('Marzo 2027 B', '2027-03-01', '2027-03-31', numero: 4);
    T::matricula(T::grupo($this->marzo, $this->ingles));
    T::matricula(T::grupo($marzoB, $this->ingles, 2));
    T::matricula(T::grupo($marzoB, $this->ingles, 2));

    $m = app(CalcularMatriculas::class)(new FiltrosDelTablero($marzoB->id));
    expect($m->total)->toBe(2)
        ->and($m->anterior?->periodo)->toBe('Marzo 2027')
        ->and($m->crecimientoMensual())->toBe(100.0)
        ->and($m->delAnioAnterior)->toBeNull()
        ->and($m->crecimientoAnual())->toBeNull();

    T::periodo('Marzo 2026', '2026-03-01', '2026-03-31', anio: 2026, numero: 3);
    $m2 = app(CalcularMatriculas::class)(new FiltrosDelTablero($this->marzo->id));
    expect($m2->delAnioAnterior?->periodo)->toBe('Marzo 2026')
        ->and($m2->crecimientoAnual())->toBeNull();
});

it('P-des: zero enrollments gives null rate', function (): void {
    T::grupo($this->marzo, $this->ingles);
    $d = app(CalcularDesercion::class)(new FiltrosDelTablero($this->marzo->id));
    expect($d->total->matriculas)->toBe(0)->and($d->total->tasa())->toBeNull();
});

it('P-dem: one group with two slots', function (): void {
    $g = T::grupo($this->marzo, $this->ingles, atributos: ['cupo_maximo' => 20]);
    T::horario($g, [1, 3], '07:00', '09:00');
    T::horario($g, [5], '18:00', '20:00');
    T::matricula($g);
    T::matricula($g, retirada: true);

    $f = app(CalcularDemandaHoraria::class)(new FiltrosDelTablero($this->marzo->id));
    expect(collect($f)->map(fn ($x): array => [$x->horaInicio, $x->horaFin, $x->dias, $x->grupos, $x->matriculados, $x->cupo])->all())
        ->toBe([['07:00', '09:00', [1, 3], 1, 1, 20], ['18:00', '20:00', [5], 1, 1, 20]]);
});

it('P-access: panel user without tablero.ver gets 403 and cannot mount nor export', function (): void {
    $u = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Reportar->value]);
    $this->actingAs($u)->get(TableroGerencial::getUrl())->assertForbidden();
    Livewire::test(TableroGerencial::class)->assertForbidden();
    $this->actingAs(User::factory()->create())->get(TableroGerencial::getUrl())->assertForbidden();
});

it('P-excel: names starting with = + - @ stay text', function (): void {
    $this->ingles->update(['nombre' => '+cmd|x']);
    $g = T::grupo($this->marzo, $this->ingles, atributos: ['codigo' => '-2+3']);
    T::matricula($g, T::pago(TipoConcepto::Matricula, '80.00', '2027-02-20'));
    T::pago(TipoConcepto::Pension, '5.00', '2027-03-10', 'pendiente');
    DB::table('pag_conceptos')->where('codigo', 'T-PENSION')->update(['nombre' => '@SUM(A1)']);

    $libro = IOFactory::load(app(ExportarTableroExcel::class)(app(ArmarTablero::class)(new FiltrosDelTablero($this->marzo->id)))->getFile()->getPathname());
    $rent = $libro->getSheetByName('Rentabilidad');
    $pv = $libro->getSheetByName('Por validar');

    expect($rent->getCell('A2')->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($rent->getCell('A2')->getValue())->toBe('-2+3')
        ->and($rent->getCell('B2')->getValue())->toBe('+cmd|x')
        ->and($rent->getCell('B2')->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($pv->getCell('A2')->getValue())->toBe('@SUM(A1)')
        ->and($pv->getCell('A2')->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($pv->getCell('B2')->getValue())->toEqual(5);
});

it('P-pdf: real content', function (): void {
    T::matricula(T::grupo($this->marzo, $this->ingles), T::pago(TipoConcepto::Matricula, '80.00', '2027-02-20'));
    $c = app(ExportarTableroPdf::class)(app(ArmarTablero::class)(new FiltrosDelTablero($this->marzo->id)))->generatePdfContent();
    expect($c)->toStartWith('%PDF')->and(strlen($c))->toBeGreaterThan(2000);
});

it('P-inv: invalid filters are refused and change no data', function (): void {
    $g = T::grupo($this->marzo, $this->ingles, atributos: ['codigo' => 'ING-M']);
    T::matricula($g, T::pago(TipoConcepto::Matricula, '80.00', '2027-02-20'));
    T::pago(TipoConcepto::Pension, '5.00', '2027-03-10', 'pendiente');
    $u = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTablero::Ver->value]);
    $hash = fn (): string => md5(collect(['pag_pagos', 'mat_matriculas', 'aca_grupos', 'aca_periodos', 'aca_idiomas', 'doc_clases_dictadas', 'permissions', 'model_has_permissions'])
        ->map(fn ($t): string|false => json_encode(DB::table($t)->orderBy(DB::raw('1'))->get()))->implode('|'));
    $antes = $hash();
    $this->actingAs($u);

    Livewire::test(TableroGerencial::class)
        ->set('data.periodo_id', 999999)->assertSee('Elige un periodo válido.')
        ->set('data.periodo_id', $this->marzo->id)
        ->set('data.idioma_id', 999999)->assertSee('Elige un idioma válido.')
        ->set('data.idioma_id')
        ->set('data.nivel', 'bogus')->assertSee('Elige un nivel válido.')
        ->set('data.nivel')
        ->set('data.periodo_id', 999999)->callAction('descargarPdf')->assertNoFileDownloaded();

    expect($hash())->toBe($antes);
});
