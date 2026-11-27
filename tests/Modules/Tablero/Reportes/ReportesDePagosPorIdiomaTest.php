<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Tablero\Actions\GenerarReporteAdministrativo;
use App\Modules\Tablero\Actions\GenerarReporteFinanciero;
use App\Modules\Tablero\Data\ColumnaDeReporte;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Filament\Pages\ReporteAdministrativo;
use App\Modules\Tablero\Filament\Pages\ReporteFinanciero;
use App\Shared\Auth\Models\User;
use Filament\Facades\Filament;
use Tests\Support\ActasFinales;
use Tests\Support\ReportesDelEstudiante;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->escenario = ReportesDelEstudiante::escenario();
    $this->grupo = $this->escenario['curso']['grupo'];
    $this->programa = $this->grupo->programa->nombre;
    $this->filtros = new FiltrosDeReporte(periodoId: $this->grupo->periodo_id);
    $concepto = Concepto::factory()->create(['tipo' => TipoConcepto::Matricula]);
    $tarifa = Tarifa::factory()->for($concepto)->create(['tipo_vinculo' => 'estudiante_unasam', 'monto' => '120.00', 'vigente_desde' => '2026-01-01']);
    $pago = Pago::factory()->validado()->for($concepto)->create([
        'persona_id' => $this->escenario['persona']->id, 'tarifa_id' => $tarifa->id, 'monto' => '120.00',
        'medio_pago_id' => MedioDePago::idDe('banco_nacion'), 'numero_operacion' => '00012345',
    ]);
    $this->escenario['matricula']->update(['pago_id' => $pago->id]);
    ActasFinales::estudiante($this->grupo, 'Bravo');
});

it('counts the enrollments of each group by the link type of the fee paid and by the place of payment', function (): void {
    $reporte = app(GenerarReporteAdministrativo::class)($this->filtros);
    $seccion = $reporte->secciones[0];

    expect($reporte->formal)->toBeTrue()
        ->and($seccion->titulo)->toBe("Inglés · {$this->programa}")
        ->and(array_map(fn (ColumnaDeReporte $columna): array => [$columna->grupo, $columna->corto ?? $columna->nombre], $seccion->columnas))->toBe([
            [null, 'Nivel'], [null, 'Ciclo'], [null, 'Docente'], [null, 'Grupo'],
            ['Vínculo', 'Estudiante UNASAM'], ['Vínculo', 'Sin pago'], ['Lugar de pago', 'Banco de la Nación'], ['Lugar de pago', 'Sin pago'], [null, 'Total'],
        ])
        ->and($seccion->filas)->toBe([['Básico', 1, 'Vega Ríos, Carlos', 'ING-1-M', 1, 1, 1, 1, 2]])
        ->and($seccion->totales)->toBe([['Total', null, null, null, 1, 1, 1, 1, 2]]);
});

it('lists each enrollment payment with the link type, place, receipt number and amount, by language, program and level', function (): void {
    $seccion = app(GenerarReporteFinanciero::class)($this->filtros)->secciones[0];

    expect($seccion->titulo)->toBe("Inglés · {$this->programa} · Básico")
        ->and(array_map(fn (ColumnaDeReporte $columna): string => $columna->nombre, $seccion->columnas))->toBe(['Apellidos y nombres', 'Grupo', 'Vínculo', 'Lugar de pago', 'N.° de comprobante', 'Monto (S/)'])
        ->and($seccion->filas)->toBe([['Quispe Mamani, Rosa', 'ING-1-M', 'Estudiante UNASAM', 'Banco de la Nación', '00012345', 120.0]])
        ->and($seccion->totales)->toBe([['Total', null, null, null, null, 120.0]]);
});

it('opens the administrative and financial reports only to staff who report payments', function (string $pagina): void {
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value]));
    expect($pagina::canAccess())->toBeFalse();

    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Reportar->value]));
    expect($pagina::canAccess())->toBeTrue();
})->with([ReporteAdministrativo::class, ReporteFinanciero::class]);
